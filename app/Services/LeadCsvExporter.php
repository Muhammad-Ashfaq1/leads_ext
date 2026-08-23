<?php

namespace App\Services;

use App\Models\ExtractionJob;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadCsvExporter
{
    public function download(ExtractionJob $job, ?array $ids = null, string $format = 'csv'): StreamedResponse
    {
        if ($format === 'excel' || $format === 'xlsx' || $format === 'xls') {
            return $this->downloadExcelXml($job, $ids);
        }

        return $this->downloadCsv($job, $ids);
    }

    public function downloadCsv(ExtractionJob $job, ?array $ids = null): StreamedResponse
    {
        $filename = 'awt-phone-leads-'.$job->uuid.'.csv';

        return response()->streamDownload(function () use ($job, $ids): void {
            $handle = fopen('php://output', 'w');
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

    public function downloadExcelXml(ExtractionJob $job, ?array $ids = null): StreamedResponse
    {
        $filename = 'awt-phone-leads-'.$job->uuid.'.xls';

        return response()->streamDownload(function () use ($job, $ids): void {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $xml .= '<?mso-application progid="Excel.Sheet"?>'."\n";
            $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'."\n";
            $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"'."\n";
            $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"'."\n";
            $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"'."\n";
            $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">'."\n";
            $xml .= ' <Styles>'."\n";
            $xml .= '  <Style ss:ID="Header">'."\n";
            $xml .= '   <Font ss:Bold="1" ss:Color="#FFFFFF"/>'."\n";
            $xml .= '   <Interior ss:Color="#696CFF" ss:Pattern="Solid"/>'."\n";
            $xml .= '   <Alignment ss:Vertical="Center"/>'."\n";
            $xml .= '  </Style>'."\n";
            $xml .= '  <Style ss:ID="Default">'."\n";
            $xml .= '   <Alignment ss:Vertical="Center"/>'."\n";
            $xml .= '  </Style>'."\n";
            $xml .= ' </Styles>'."\n";
            $xml .= ' <Worksheet ss:Name="Extracted Leads">'."\n";
            $xml .= '  <Table>'."\n";

            $headers = ['Business Name', 'Address', 'Email(s)', 'Phone', 'Website', 'Category', 'Rating', 'Reviews', 'Google Maps URL', 'Source'];
            $xml .= '   <Row ss:StyleID="Header">'."\n";
            foreach ($headers as $h) {
                $xml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars($h, ENT_XML1).'</Data></Cell>'."\n";
            }
            $xml .= '   </Row>'."\n";
            echo $xml;

            $query = $job->leads()->orderBy('id');
            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }

            foreach ($query->cursor() as $lead) {
                $emails = implode('; ', $lead->emails ?? []);
                $rowXml = "   <Row>\n";
                $rowXml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars((string) $lead->business_name, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars((string) $lead->address, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars((string) $emails, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars((string) $lead->phone, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars((string) $lead->website, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars((string) $lead->category, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= '    <Cell><Data ss:Type="'.(is_numeric($lead->rating) ? 'Number' : 'String').'">'.htmlspecialchars((string) $lead->rating, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= '    <Cell><Data ss:Type="'.(is_numeric($lead->review_count) ? 'Number' : 'String').'">'.htmlspecialchars((string) $lead->review_count, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars((string) $lead->google_maps_url, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars((string) $lead->source, ENT_XML1)."</Data></Cell>\n";
                $rowXml .= "   </Row>\n";
                echo $rowXml;
            }

            echo "  </Table>\n </Worksheet>\n</Workbook>\n";
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }
}

