<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Services\ExtractorClient;
use App\Services\LeadCsvExporter;
use App\Support\PromptNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ExtractorController extends Controller
{
    public function __construct(
        private readonly ExtractorClient $client,
        private readonly LeadCsvExporter $csvExporter,
    ) {}

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:2', 'max:500'],
            'limit' => ['nullable', 'integer', 'min:'.config('extractor.min_limit'), 'max:'.config('extractor.max_limit')],
            'mode' => ['nullable', Rule::in(['live', 'mock'])],
            'simulate_verification' => ['sometimes', 'boolean'],
        ]);

        $mode = $validated['mode'] ?? 'live';
        $simulate = (bool) ($validated['simulate_verification'] ?? false);

        if (($mode === 'mock' || $simulate) && ! config('extractor.allow_mock')) {
            return response()->json([
                'message' => 'Development simulation is not available.',
            ], 403);
        }

        $prompt = trim($validated['prompt']);
        $limit = (int) ($validated['limit'] ?? config('extractor.default_limit'));
        $query = PromptNormalizer::toSearchQuery($prompt);

        try {
            $started = $this->client->start($prompt, $limit, $mode, $simulate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $job = ExtractionJob::create([
            'uuid' => $started['job_id'],
            'prompt' => $prompt,
            'query' => $started['query'] ?? $query,
            'status' => $started['status'] ?? ExtractionJob::STATUS_STARTING,
            'limit' => $limit,
            'mode' => $mode,
            'started_at' => now(),
        ]);

        Log::info('job created', ['job_id' => $job->uuid, 'query' => $job->query, 'limit' => $limit]);
        Log::info('job started', ['job_id' => $job->uuid]);

        return response()->json([
            'job_id' => $job->uuid,
            'query' => $job->query,
            'status' => $job->status,
        ]);
    }

    public function status(ExtractionJob $job): JsonResponse
    {
        try {
            $remote = $this->client->status($job->uuid);
            $this->syncJob($job, $remote);
        } catch (Throwable $exception) {
            Log::warning('Python service errors', [
                'action' => 'status',
                'job_id' => $job->uuid,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json($job->fresh()->toStatusArray());
    }

    public function stop(ExtractionJob $job): JsonResponse
    {
        try {
            $remote = $this->client->stop($job->uuid);
            $this->syncJob($job, $remote);
        } catch (Throwable $exception) {
            Log::error('Python service errors', [
                'action' => 'stop',
                'job_id' => $job->uuid,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $job->refresh();
        if (! $job->isTerminal()) {
            $job->forceFill([
                'status' => ExtractionJob::STATUS_CANCELLED,
                'completed_at' => $job->completed_at ?? now(),
                'current_activity' => 'Extraction stopped.',
            ])->save();
        }

        Log::info('job stopped', ['job_id' => $job->uuid]);

        return response()->json($job->fresh()->toStatusArray());
    }

    public function focus(ExtractionJob $job): JsonResponse
    {
        return response()->json($this->client->focus($job->uuid));
    }

    public function verifyComplete(ExtractionJob $job): JsonResponse
    {
        if (! config('extractor.allow_mock')) {
            return response()->json(['message' => 'Development simulation is not available.'], 403);
        }

        try {
            return response()->json($this->client->completeMockVerification($job->uuid));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }
    }

    public function export(ExtractionJob $job): StreamedResponse
    {
        return $this->csvExporter->download($job);
    }

    public function stream(ExtractionJob $job): StreamedResponse
    {
        $url = $this->client->streamUrl($job->uuid);

        return response()->stream(function () use ($job, $url): void {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            if (! app()->environment('testing')) {
                while (ob_get_level() > 0) {
                    @ob_end_flush();
                }
            }
            echo "retry: 2000\n\n";
            $this->flush();

            $handle = @fopen($url, 'r');
            if ($handle === false) {
                Log::error('stream errors', ['job_id' => $job->uuid, 'error' => 'Unable to open Python event stream']);
                echo 'data: '.json_encode([
                    'type' => 'error',
                    'job_id' => $job->uuid,
                    'message' => 'Extractor service is unavailable. Please start the Python extractor service.',
                ])."\n\n";
                $this->flush();

                return;
            }

            stream_set_blocking($handle, true);
            $buffer = '';

            while (! feof($handle)) {
                $chunk = fread($handle, 2048);
                if ($chunk === false || $chunk === '') {
                    if (connection_aborted()) {
                        break;
                    }
                    usleep(50000);

                    continue;
                }

                $buffer .= $chunk;
                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $block = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);
                    $this->handleSseBlock($job, $block);
                    echo $block."\n\n";
                    $this->flush();
                }
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function handleSseBlock(ExtractionJob $job, string $block): void
    {
        foreach (preg_split("/\r\n|\n|\r/", $block) as $line) {
            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $payload = trim(substr($line, 5));
            $event = json_decode($payload, true);
            if (! is_array($event) || ! isset($event['type'])) {
                continue;
            }

            $this->applyEvent($job, $event);
        }
    }

    private function applyEvent(ExtractionJob $job, array $event): void
    {
        $job->refresh();

        match ($event['type']) {
            'started' => $job->forceFill([
                'status' => $event['status'] ?? ExtractionJob::STATUS_STARTING,
                'started_at' => $job->started_at ?? now(),
            ])->save(),
            'searching' => $job->forceFill([
                'status' => ExtractionJob::STATUS_SEARCHING,
                'current_activity' => $event['message'] ?? 'Searching Google Maps',
                'query' => $event['query'] ?? $job->query,
            ])->save(),
            'progress' => $job->forceFill([
                'status' => $event['status'] ?? $job->status,
                'businesses_seen' => $event['businesses_seen'] ?? $job->businesses_seen,
                'leads_extracted' => $event['leads_extracted'] ?? $job->leads_extracted,
                'emails_found' => $event['emails_found'] ?? $job->emails_found,
                'websites_found' => $event['websites_found'] ?? $job->websites_found,
                'current_activity' => $event['current_activity'] ?? $job->current_activity,
            ])->save(),
            'lead' => $this->storeLead($job, $event),
            'human_verification_required' => $job->forceFill([
                'status' => ExtractionJob::STATUS_WAITING_FOR_HUMAN_VERIFICATION,
                'current_activity' => $event['message'] ?? 'Waiting for human verification',
            ])->save(),
            'verification_completed' => $job->forceFill([
                'status' => ExtractionJob::STATUS_EXTRACTING,
                'current_activity' => $event['message'] ?? 'Extraction resumed',
            ])->save(),
            'completed' => $this->complete($job, ExtractionJob::STATUS_COMPLETED, $event, 'job completed'),
            'cancelled' => $this->complete($job, ExtractionJob::STATUS_CANCELLED, $event, 'job stopped'),
            'error' => $this->complete($job, ExtractionJob::STATUS_ERROR, $event, 'Python service errors'),
            'verification_timeout' => $this->complete($job, ExtractionJob::STATUS_VERIFICATION_TIMEOUT, $event, 'job stopped'),
            default => null,
        };
    }

    private function storeLead(ExtractionJob $job, array $event): void
    {
        $lead = $event['lead'] ?? null;
        if (! is_array($lead) || empty($lead['business_name'])) {
            return;
        }

        $placeId = $lead['place_id'] ?? null;
        $exists = $job->leads()
            ->when($placeId, fn ($query) => $query->where('place_id', $placeId))
            ->when(! $placeId, fn ($query) => $query
                ->where('business_name', $lead['business_name'])
                ->where('address', $lead['address'] ?? null))
            ->exists();

        if (! $exists) {
            $job->leads()->create(ExtractedLead::fromPayload($lead));
        }

        $job->forceFill([
            'status' => $event['status'] ?? ExtractionJob::STATUS_EXTRACTING,
            'businesses_seen' => $event['businesses_seen'] ?? $job->businesses_seen,
            'leads_extracted' => $event['leads_extracted'] ?? $job->leads()->count(),
            'emails_found' => $event['emails_found'] ?? $job->emails_found,
            'websites_found' => $event['websites_found'] ?? $job->websites_found,
            'current_activity' => $lead['business_name'],
        ])->save();
    }

    private function complete(ExtractionJob $job, string $status, array $event, string $log): void
    {
        $job->forceFill([
            'status' => $status,
            'businesses_seen' => $event['businesses_seen'] ?? $job->businesses_seen,
            'leads_extracted' => $event['leads_extracted'] ?? $job->leads_extracted,
            'emails_found' => $event['emails_found'] ?? $job->emails_found,
            'websites_found' => $event['websites_found'] ?? $job->websites_found,
            'current_activity' => $event['message'] ?? $job->current_activity,
            'error' => $event['error'] ?? ($status === ExtractionJob::STATUS_ERROR ? ($event['message'] ?? null) : $job->error),
            'completed_at' => now(),
        ])->save();

        Log::info($log, ['job_id' => $job->uuid, 'status' => $status]);
    }

    private function syncJob(ExtractionJob $job, array $remote): void
    {
        $status = $remote['status'] ?? $job->status;
        $job->forceFill([
            'status' => $status,
            'businesses_seen' => $remote['businesses_seen'] ?? $job->businesses_seen,
            'leads_extracted' => $remote['leads_extracted'] ?? $job->leads_extracted,
            'emails_found' => $remote['emails_found'] ?? $job->emails_found,
            'websites_found' => $remote['websites_found'] ?? $job->websites_found,
            'current_activity' => $remote['current_activity'] ?? $job->current_activity,
            'error' => $remote['error'] ?? $job->error,
            'completed_at' => in_array($status, [
                ExtractionJob::STATUS_COMPLETED,
                ExtractionJob::STATUS_CANCELLED,
                ExtractionJob::STATUS_ERROR,
                ExtractionJob::STATUS_VERIFICATION_TIMEOUT,
                ExtractionJob::STATUS_BLOCKED,
            ], true) ? ($job->completed_at ?? now()) : $job->completed_at,
        ])->save();
    }

    private function flush(): void
    {
        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        flush();
    }
}
