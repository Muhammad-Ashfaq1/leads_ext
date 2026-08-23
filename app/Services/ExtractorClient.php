<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExtractorClient
{
    public function start(string $prompt, int $limit, string $mode = 'live', bool $simulateVerification = false): array
    {
        try {
            $response = $this->http()->post('/jobs', [
                'prompt' => $prompt,
                'limit' => $limit,
                'mode' => $mode,
                'simulate_verification' => $simulateVerification,
            ]);
        } catch (ConnectionException $exception) {
            Log::error('Python service errors', ['action' => 'start', 'error' => $exception->getMessage()]);
            throw new RuntimeException('Extractor service is unavailable. Please start the Python extractor service.');
        }

        if ($response->failed()) {
            Log::error('Python service errors', [
                'action' => 'start',
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $detail = $response->json('detail');
            throw new RuntimeException(is_string($detail) ? $detail : 'Extractor service rejected the request.');
        }

        return $response->json();
    }

    public function status(string $jobId): array
    {
        try {
            $response = $this->http()->get("/jobs/{$jobId}");
        } catch (ConnectionException $exception) {
            Log::error('Python service errors', ['action' => 'status', 'error' => $exception->getMessage()]);
            throw new RuntimeException('Extractor service is unavailable. Please start the Python extractor service.');
        }

        if ($response->status() === 404) {
            throw new RuntimeException('Unknown extraction job.');
        }

        if ($response->failed()) {
            throw new RuntimeException('Extractor service is unavailable. Please start the Python extractor service.');
        }

        return $response->json();
    }

    public function stop(string $jobId): array
    {
        try {
            $response = $this->http()->post("/jobs/{$jobId}/stop");
        } catch (ConnectionException $exception) {
            Log::error('Python service errors', ['action' => 'stop', 'error' => $exception->getMessage()]);
            throw new RuntimeException('Extractor service is unavailable. Please start the Python extractor service.');
        }

        if ($response->failed()) {
            throw new RuntimeException('Unable to stop the extraction job.');
        }

        return $response->json();
    }

    public function focus(string $jobId): array
    {
        try {
            $response = $this->http()->post("/jobs/{$jobId}/focus");
        } catch (ConnectionException $exception) {
            return ['ok' => false];
        }

        return $response->json() ?? ['ok' => false];
    }

    public function completeMockVerification(string $jobId): array
    {
        $response = $this->http()->post("/jobs/{$jobId}/verify-complete");

        if ($response->failed()) {
            throw new RuntimeException('Unable to complete simulated verification.');
        }

        return $response->json();
    }

    public function streamUrl(string $jobId): string
    {
        return rtrim((string) config('extractor.service_url'), '/')."/jobs/{$jobId}/events";
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('extractor.service_url'), '/'))
            ->timeout((int) config('extractor.timeout'))
            ->acceptJson();
    }
}
