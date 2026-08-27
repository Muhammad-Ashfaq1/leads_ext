<?php

namespace App\Jobs;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateLeadsExportJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;

    public function __construct(
        public ?int $tenantId = null,
        public ?int $userId = null,
        public ?string $jobUuid = null,
        public ?array $leadIds = null,
        public ?string $exportUuid = null,
        public string $format = 'xlsx',
        public array $filters = [],
        public ?string $destinationPath = null,
    ) {
        $this->exportUuid = $this->exportUuid ?: (string) Str::uuid();
    }

    /**
     * Execute the export job.
     */
    public function handle(): string
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $tenantFolder = $this->tenantId ? (string) $this->tenantId : 'global';
        $exportDir = storage_path("app/exports/{$tenantFolder}");
        File::ensureDirectoryExists($exportDir);

        $extension = strtolower($this->format) === 'csv' ? 'csv' : 'xlsx';
        $filePath = $this->destinationPath ?: "{$exportDir}/export-{$this->exportUuid}.{$extension}";

        $query = ExtractedLead::query()
            ->when($this->tenantId !== null, fn ($q) => $q->where('tenant_id', $this->tenantId))
            ->when(! empty($this->leadIds), fn ($q) => $q->whereIn('id', $this->leadIds))
            ->when(! empty($this->jobUuid), function ($q): void {
                $q->whereHas('job', fn ($jobQuery) => $jobQuery->where('uuid', $this->jobUuid));
            })
            ->orderBy('id');

        $headers = [
            'Business Name',
            'Address',
            'Email(s)',
            'Email Verification',
            'Phone',
            'Website',
            'LinkedIn',
            'Facebook',
            'Instagram',
            'Twitter/X',
            'YouTube',
            'Category',
            'Rating',
            'Reviews',
            'Google Maps URL',
            'Source',
            'Extracted At',
        ];

        $rowCount = 0;

        if ($extension === 'xlsx' && class_exists('\OpenSpout\Writer\XLSX\Writer')) {
            $rowCount = $this->writeWithOpenSpout($query, $headers, $filePath);
        } else {
            $rowCount = $this->writeWithStreamingCsv($query, $headers, $filePath);
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        Log::info('GenerateLeadsExportJob completed', [
            'export_uuid' => $this->exportUuid,
            'tenant_id' => $this->tenantId,
            'rows_exported' => $rowCount,
            'file_path' => $filePath,
            'duration_sec' => $elapsed,
            'peak_memory_mb' => $peakMemory,
        ]);

        return $filePath;
    }

    /**
     * Generate .xlsx file using OpenSpout streaming writer.
     */
    private function writeWithOpenSpout($query, array $headers, string $filePath): int
    {
        $optionsClass = '\OpenSpout\Writer\XLSX\Options';
        $writerClass = '\OpenSpout\Writer\XLSX\Writer';
        $rowClass = '\OpenSpout\Common\Entity\Row';
        $styleClass = '\OpenSpout\Common\Entity\Style\Style';
        $colorClass = '\OpenSpout\Common\Entity\Style\Color';

        if (class_exists($optionsClass)) {
            $options = new $optionsClass();
            $writer = new $writerClass($options);
        } else {
            $writer = new $writerClass();
        }

        $writer->openToFile($filePath);

        // Header row styling
        if (class_exists($styleClass)) {
            $headerStyle = (new $styleClass())
                ->setFontBold()
                ->setFontColor(class_exists($colorClass) ? $colorClass::WHITE : 'FFFFFF')
                ->setBackgroundColor('696CFF');

            $headerRow = $rowClass::fromValues($headers, $headerStyle);
        } else {
            $headerRow = $rowClass::fromValues($headers);
        }

        $writer->addRow($headerRow);

        $count = 0;
        foreach ($query->cursor() as $lead) {
            $rowValues = $this->mapLeadToRow($lead);
            $writer->addRow($rowClass::fromValues($rowValues));
            $count++;

            if ($count % 500 === 0 && function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $writer->close();

        return $count;
    }

    /**
     * Streaming CSV fallback writer.
     */
    private function writeWithStreamingCsv($query, array $headers, string $filePath): int
    {
        $handle = fopen($filePath, 'w');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($handle, $headers);

        $count = 0;
        foreach ($query->cursor() as $lead) {
            fputcsv($handle, $this->mapLeadToRow($lead));
            $count++;

            if ($count % 500 === 0 && function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        fclose($handle);

        return $count;
    }

    /**
     * Map ExtractedLead model attributes into an indexed array.
     */
    public function mapLeadToRow(ExtractedLead $lead): array
    {
        $emails = is_array($lead->emails) ? implode('; ', $lead->emails) : (string) $lead->emails;
        $socials = is_array($lead->social_links) ? $lead->social_links : [];
        $verification = $lead->email_verification_status;
        $verificationSummary = '';

        if (is_array($verification) && ! empty($verification)) {
            $summaryParts = [];
            foreach ($verification as $email => $status) {
                if (is_array($status)) {
                    $validText = ($status['is_valid'] ?? false) ? 'VALID (MX Verified)' : (($status['is_disposable'] ?? false) ? 'DISPOSABLE' : 'INVALID');
                    $summaryParts[] = "{$email}: {$validText}";
                }
            }
            $verificationSummary = implode('; ', $summaryParts);
        }

        return [
            $lead->business_name ?? '',
            $lead->address ?? '',
            $emails,
            $verificationSummary,
            $lead->phone ?? '',
            $lead->website ?? '',
            $socials['linkedin'] ?? '',
            $socials['facebook'] ?? '',
            $socials['instagram'] ?? '',
            $socials['twitter'] ?? '',
            $socials['youtube'] ?? '',
            $lead->category ?? '',
            $lead->rating !== null ? (string) $lead->rating : '',
            $lead->review_count !== null ? (string) $lead->review_count : '',
            $lead->google_maps_url ?? '',
            $lead->source ?? 'Google Maps',
            $lead->extracted_at ? $lead->extracted_at->toDateTimeString() : '',
        ];
    }
}

