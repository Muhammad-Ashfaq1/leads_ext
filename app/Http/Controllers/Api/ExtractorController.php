<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Services\EmailOutreachService;
use App\Services\ExtractorClient;
use App\Services\GooglePlacesService;
use App\Services\LeadCsvExporter;
use App\Support\PromptNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ExtractorController extends Controller
{
    public function __construct(
        private readonly ExtractorClient $client,
        private readonly LeadCsvExporter $csvExporter,
        private readonly GooglePlacesService $googlePlacesService,
        private readonly EmailOutreachService $emailOutreachService,
    ) {}

    public function start(Request $request): JsonResponse
    {
        $user = Auth::user();
        $email = strtolower((string) ($user?->email ?? ''));
        $maxLimit = ($user?->isAdmin() && (str_contains($email, 'obtainsolutions') || str_contains($email, 'obtain-solutions')))
            ? 2500
            : 500;

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:2', 'max:500'],
            'location' => ['nullable', 'string', 'max:300'],
            'city' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:50'],
            'api_key' => ['nullable', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:'.config('extractor.min_limit'), 'max:'.$maxLimit],
            'mode' => ['nullable', Rule::in(['live', 'mock', 'google_api'])],
            'simulate_verification' => ['sometimes', 'boolean'],
            'filters' => ['nullable', 'array'],
            'filters.require_website' => ['nullable', 'boolean'],
            'filters.without_website' => ['nullable', 'boolean'],
            'filters.require_no_website' => ['nullable', 'boolean'],
            'filters.website_status' => ['nullable', 'string', Rule::in(['all', 'has_website', 'without_website', 'no_website', 'yes', 'no'])],
            'filters.require_phone' => ['nullable', 'boolean'],
            'filters.require_email' => ['nullable', 'boolean'],
            'filters.min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'filters.min_reviews' => ['nullable', 'integer', 'min:0'],
        ]);

        $mode = $validated['mode'] ?? 'live';
        $simulate = (bool) ($validated['simulate_verification'] ?? false);
        $filters = $validated['filters'] ?? [];

        if (($mode === 'mock' || $simulate) && ! config('extractor.allow_mock')) {
            return response()->json([
                'message' => 'Development simulation is not available.',
            ], 403);
        }

        $prompt = trim($validated['prompt']);
        $location = trim($validated['location'] ?? '');
        $city = trim($validated['city'] ?? '');
        $state = trim($validated['state'] ?? '');
        $zipCode = trim($validated['zip_code'] ?? '');
        $apiKey = trim($validated['api_key'] ?? '');
        $limit = (int) ($validated['limit'] ?? config('extractor.default_limit'));

        if (empty($location)) {
            $locationParts = array_filter([$city, $state, $zipCode]);
            if (! empty($locationParts)) {
                $location = implode(', ', $locationParts);
            }
        }

        if ($location !== '') {
            $query = PromptNormalizer::toSearchQuery($prompt)." in {$location}";
        } else {
            $query = PromptNormalizer::toSearchQuery($prompt);
        }

        $tenantId = $user?->tenant_id ?? \App\Models\Tenant::first()?->id;
        $userId = $user?->id ?? \App\Models\User::where('email', 'admin@obtainsolutions.com')->value('id') ?? \App\Models\User::first()?->id;
        $tenantKey = $user?->tenant?->google_maps_api_key;

        if ($mode === 'google_api') {
            $configuredKey = $apiKey ?: ($tenantKey ?: config('services.google.maps_api_key'));
            if (empty($configuredKey)) {
                return response()->json([
                    'message' => 'Google Maps API key is required. Please provide an API key or configure GOOGLE_MAPS_API_KEY in .env.',
                ], 422);
            }

            $jobUuid = (string) Str::uuid();
            if (! empty($apiKey)) {
                session(['google_maps_api_key_'.$jobUuid => $apiKey]);
            }
            if (! empty($filters)) {
                session(['google_maps_filters_'.$jobUuid => $filters]);
            }
            if (! empty($location)) {
                session(['google_maps_location_'.$jobUuid => $location]);
            }

            $job = ExtractionJob::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'uuid' => $jobUuid,
                'prompt' => $prompt.($location ? " ({$location})" : ''),
                'query' => $query,
                'status' => ExtractionJob::STATUS_STARTING,
                'limit' => $limit,
                'mode' => $mode,
                'started_at' => now(),
            ]);

            Log::info('Google API job created', ['job_id' => $job->uuid, 'query' => $job->query, 'limit' => $limit]);

            return response()->json([
                'job_id' => $job->uuid,
                'query' => $job->query,
                'status' => $job->status,
            ]);
        }

        try {
            $started = $this->client->start($prompt, $limit, $mode, $simulate);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $job = ExtractionJob::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
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
        if ($job->mode === 'google_api' || $job->mode === 'mock') {
            if (! $job->isTerminal()) {
                $job->forceFill([
                    'status' => ExtractionJob::STATUS_CANCELLED,
                    'completed_at' => $job->completed_at ?? now(),
                    'current_activity' => 'Extraction stopped.',
                ])->save();
            }

            Log::info('job stopped', ['job_id' => $job->uuid, 'mode' => $job->mode]);

            return response()->json($job->fresh()->toStatusArray());
        }

        try {
            $remote = $this->client->stop($job->uuid);
            $this->syncJob($job, $remote);
        } catch (Throwable $exception) {
            Log::warning('Python service offline during stop, cancelling job locally', [
                'action' => 'stop',
                'job_id' => $job->uuid,
                'error' => $exception->getMessage(),
            ]);

            if (! $job->isTerminal()) {
                $job->forceFill([
                    'status' => ExtractionJob::STATUS_CANCELLED,
                    'completed_at' => $job->completed_at ?? now(),
                    'current_activity' => 'Extraction stopped.',
                ])->save();
            }

            return response()->json($job->fresh()->toStatusArray());
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

    public function export(Request $request, ExtractionJob $job): StreamedResponse
    {
        $ids = null;
        if ($request->filled('ids')) {
            $rawIds = $request->input('ids');
            $ids = is_array($rawIds) ? array_map('intval', $rawIds) : array_map('intval', explode(',', (string) $rawIds));
            $ids = array_values(array_filter($ids, fn (int $id) => $id > 0));
        }

        $format = $request->query('format', 'excel');

        return $this->csvExporter->download($job, $ids, $format);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['nullable', 'array'],
            'lead_ids.*' => ['integer', 'min:1'],
            'job_id' => ['nullable', 'string'],
            'action' => ['required', 'string', Rule::in(['save', 'save_all', 'discard', 'delete'])],
        ]);

        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $tenantId = $user?->tenant_id;

        $leadIds = $validated['lead_ids'] ?? [];
        $jobIdentifier = $validated['job_id'] ?? null;
        $action = $validated['action'];

        if (empty($leadIds) && empty($jobIdentifier)) {
            return response()->json([
                'message' => 'Either lead_ids or job_id must be provided.',
            ], 422);
        }

        if (! empty($leadIds)) {
            if ($tenantId && ! $isSuperAdmin) {
                $unauthorized = ExtractedLead::whereIn('id', $leadIds)
                    ->whereNotNull('tenant_id')
                    ->where('tenant_id', '!=', $tenantId)
                    ->exists();

                if ($unauthorized) {
                    return response()->json([
                        'message' => 'Unauthorized: One or more leads do not belong to your organization.',
                    ], 403);
                }
            }

            $query = ExtractedLead::whereIn('id', $leadIds);
        } else {
            $job = ExtractionJob::where('uuid', $jobIdentifier)
                ->orWhere('id', $jobIdentifier)
                ->first();

            if (! $job) {
                return response()->json(['message' => 'Extraction job not found.'], 404);
            }

            if ($tenantId && ! $isSuperAdmin && $job->tenant_id && $job->tenant_id !== $tenantId) {
                return response()->json([
                    'message' => 'Unauthorized: Job does not belong to your organization.',
                ], 403);
            }

            $query = $job->leads();
        }

        $affected = 0;
        $message = '';

        if ($action === 'save' || $action === 'save_all') {
            $updateData = [
                'status' => 'saved',
                'is_saved' => true,
            ];
            if ($tenantId) {
                $updateData['tenant_id'] = $tenantId;
            }
            $affected = $query->update($updateData);
            $message = "Successfully saved {$affected} lead(s) to the master database.";
        } elseif ($action === 'discard') {
            $affected = $query->update([
                'status' => 'discarded',
                'is_saved' => false,
            ]);
            $message = "Successfully discarded {$affected} lead(s).";
        } elseif ($action === 'delete') {
            $affected = $query->delete();
            $message = "Successfully deleted {$affected} lead(s).";
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'affected' => $affected,
            'message' => $message,
        ]);
    }

    public function exportSelected(Request $request): StreamedResponse|JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['integer', 'min:1'],
            'format' => ['nullable', 'string', Rule::in(['excel', 'xlsx', 'xls', 'csv', 'json'])],
        ]);

        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $tenantId = $user?->tenant_id;
        $leadIds = $validated['lead_ids'];
        $format = $validated['format'] ?? 'excel';

        if ($tenantId && ! $isSuperAdmin) {
            $unauthorized = ExtractedLead::whereIn('id', $leadIds)
                ->whereNotNull('tenant_id')
                ->where('tenant_id', '!=', $tenantId)
                ->exists();

            if ($unauthorized) {
                return response()->json([
                    'message' => 'Unauthorized: One or more leads do not belong to your organization.',
                ], 403);
            }
        }

        if ($format === 'json') {
            $leads = ExtractedLead::query()
                ->when(! $isSuperAdmin && $tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->whereIn('id', $leadIds)
                ->orderBy('id')
                ->get();

            return response()->json($leads);
        }

        return $this->csvExporter->downloadByIds($leadIds, $format, $tenantId, $isSuperAdmin);
    }

    public function sendEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => ['required_without:lead_id', 'nullable', 'array', 'min:1'],
            'lead_ids.*' => ['integer', 'min:1'],
            'lead_id' => ['required_without:lead_ids', 'nullable', 'integer', 'min:1'],
            'template_id' => ['nullable', 'integer', 'min:1'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $tenantId = $user?->tenant_id;

        $leadIds = $validated['lead_ids'] ?? [];
        if (! empty($validated['lead_id'])) {
            $leadIds[] = (int) $validated['lead_id'];
        }
        $leadIds = array_values(array_unique(array_filter($leadIds, fn ($id) => $id > 0)));

        if (empty($leadIds)) {
            return response()->json([
                'message' => 'Please provide at least one valid lead ID.',
            ], 422);
        }

        // Validate tenant isolation
        if ($tenantId && ! $isSuperAdmin) {
            $unauthorized = ExtractedLead::whereIn('id', $leadIds)
                ->whereNotNull('tenant_id')
                ->where('tenant_id', '!=', $tenantId)
                ->exists();

            if ($unauthorized) {
                return response()->json([
                    'message' => 'Unauthorized: One or more leads do not belong to your organization.',
                ], 403);
            }
        }

        $result = $this->emailOutreachService->sendBulk(
            $leadIds,
            $validated['subject'],
            $validated['body'],
            $validated['template_id'] ?? null,
            $user,
            $tenantId,
            $isSuperAdmin
        );

        $sentCount = $result['sent_count'];
        $skippedCount = $result['skipped_count'];
        $failedCount = $result['failed_count'];

        $message = "Successfully dispatched email to {$sentCount} lead(s).";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} skipped without email).";
        }
        if ($failedCount > 0) {
            $message .= " ({$failedCount} failed delivery).";
        }

        return response()->json([
            'success' => true,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'skipped_count' => $skippedCount,
            'message' => $message,
        ]);
    }

    public function stream(Request $request, ExtractionJob $job): StreamedResponse
    {
        if ($job->mode === 'google_api') {
            $apiKey = $request->query('api_key') ?: session('google_maps_api_key_'.$job->uuid);
            $filters = session('google_maps_filters_'.$job->uuid, []);
            $location = session('google_maps_location_'.$job->uuid);

            return $this->googlePlacesService->stream($job, $apiKey, $filters, $location);
        }

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
                Log::error('stream errors', ['job_id' => $job->uuid, 'error' => 'Unable to connect to crawler cluster']);
                echo 'data: '.json_encode([
                    'type' => 'error',
                    'job_id' => $job->uuid,
                    'message' => 'Lead Discovery Engine is temporarily unavailable. Please try again or switch extraction mode.',
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
            'error' => $this->complete($job, ExtractionJob::STATUS_ERROR, $event, 'Lead discovery engine error'),
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
            $tenantId = $job->tenant_id ?? \App\Models\Tenant::first()?->id;
            $userId = $job->user_id ?? \App\Models\User::where('email', 'admin@obtainsolutions.com')->value('id');
            $job->leads()->create(ExtractedLead::fromPayload($lead, $tenantId, $userId));
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
