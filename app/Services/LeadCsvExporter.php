<?php

namespace App\Services;

use App\Models\ExtractionJob;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadCsvExporter
{
    public function download(ExtractionJob $job): StreamedResponse
    {
        $filename = 'awt-phone-leads-'.$job->uuid.'.csv';

        return response()->streamDownload(function () use ($job): void {
            $handle = fopen('php://output', 'w');
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

            foreach ($job->leads()->orderBy('id')->cursor() as $lead) {
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
