<?php

namespace App\Services;

use App\Models\ExtractionJob;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadCsvExporter
{
    public function download(ExtractionJob $job, ?array $ids = null): StreamedResponse
    {
        $filename = 'awt-phone-leads-'.$job->uuid.'.csv';

        return response()->streamDownload(function () use ($job, $ids): void {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Business Name',
                'Address',
                'Email(s)',
                'Phone',
                'Website',
                'Category',
                'Rating',
                'Reviews',
                'Google Maps URL',
                'Source',
            ]);

            $query = $job->leads()->orderBy('id');
            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }

            foreach ($query->cursor() as $lead) {
                fputcsv($handle, [
                    $lead->business_name,
                    $lead->address,
                    implode('; ', $lead->emails ?? []),
                    $lead->phone,
                    $lead->website,
                    $lead->category,
                    $lead->rating,
                    $lead->review_count,
                    $lead->google_maps_url,
                    $lead->source,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
