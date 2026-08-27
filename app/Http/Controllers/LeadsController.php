<?php

namespace App\Http\Controllers;

use App\Models\ExtractedLead;
use App\Services\LeadCsvExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadsController extends Controller
{
    public function __construct(
        private readonly LeadCsvExporter $csvExporter,
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $tenantId = $user->tenant_id;

        $search = trim($request->input('search', ''));
        $category = $request->input('category');
        $hasEmail = $request->input('has_email');
        $verifiedEmail = $request->input('verified_email');
        $hasPhone = $request->input('has_phone');
        $hasWebsite = $request->input('has_website');
        $minRating = $request->input('min_rating');
        $source = $request->input('source');
        $jobId = $request->input('job_id');

        $query = ExtractedLead::query()
            ->with(['job', 'tenant'])
            ->when(! $isSuperAdmin && $tenantId, function ($q) use ($tenantId): void {
                $q->where(function ($sub) use ($tenantId): void {
                    $sub->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id');
                });
            })
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('business_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($hasEmail === 'yes', fn ($q) => $q->whereNotNull('emails')->where('emails', '!=', '[]'))
            ->when($hasEmail === 'no', fn ($q) => $q->where(fn ($sub) => $sub->whereNull('emails')->orWhere('emails', '[]')))
            ->when($hasEmail === 'verified' || $verifiedEmail === 'yes', function ($q): void {
                $q->whereNotNull('emails')
                    ->where('emails', '!=', '[]')
                    ->where(function ($sub): void {
                        $sub->where('email_verification_status', 'like', '%"is_valid":true%')
                            ->orWhere('email_verification_status', 'like', '%"is_valid": true%');
                    });
            })
            ->when($hasPhone === 'yes', fn ($q) => $q->whereNotNull('phone')->where('phone', '!=', ''))
            ->when($hasPhone === 'no', fn ($q) => $q->whereNull('phone')->orWhere('phone', ''))
            ->when($hasWebsite === 'yes', fn ($q) => $q->whereNotNull('website')->where('website', '!=', ''))
            ->when($hasWebsite === 'no', fn ($q) => $q->where(fn ($sub) => $sub->whereNull('website')->orWhere('website', '')))
            ->when($minRating, fn ($q) => $q->where('rating', '>=', (float) $minRating))
            ->when($source, fn ($q) => $q->where('source', $source))
            ->when($jobId, fn ($q) => $q->where('extraction_job_id', $jobId))
            ->latest('id');

        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $leads = $query->paginate($perPage)->withQueryString();

        // Available categories for filter dropdown
        $categories = ExtractedLead::query()
            ->when(! $isSuperAdmin && $tenantId, function ($q) use ($tenantId): void {
                $q->where(function ($sub) use ($tenantId): void {
                    $sub->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id');
                });
            })
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return view('leads.index', [
            'leads' => $leads,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'has_email' => $hasEmail,
                'verified_email' => $verifiedEmail,
                'has_phone' => $hasPhone,
                'has_website' => $hasWebsite,
                'min_rating' => $minRating,
                'source' => $source,
                'job_id' => $jobId,
            ],
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $tenantId = $user->tenant_id;

        $rawIds = $request->input('ids');
        $ids = null;
        if (! empty($rawIds)) {
            $ids = is_array($rawIds) ? array_map('intval', $rawIds) : array_map('intval', explode(',', (string) $rawIds));
            $ids = array_values(array_filter($ids, fn ($id) => $id > 0));
        }

        $query = ExtractedLead::query()
            ->when(! $isSuperAdmin && $tenantId, function ($q) use ($tenantId): void {
                $q->where(function ($sub) use ($tenantId): void {
                    $sub->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id');
                });
            })
            ->when(! empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('id');

        $filename = 'leads-export-'.date('Y-m-d-His').'.xls';

        return response()->streamDownload(function () use ($query): void {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $xml .= '<?mso-application progid="Excel.Sheet"?>'."\n";
            $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'."\n";
            $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"'."\n";
            $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"'."\n";
            $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'."\n";
            $xml .= ' <Styles>'."\n";
            $xml .= '  <Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#696CFF" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/></Style>'."\n";
            $xml .= ' </Styles>'."\n";
            $xml .= ' <Worksheet ss:Name="Leads">'."\n  <Table>\n";

            $headers = ['Business Name', 'Address', 'Email(s)', 'Phone', 'Website', 'Category', 'Rating', 'Reviews', 'Google Maps URL', 'Source'];
            $xml .= '   <Row ss:StyleID="Header">'."\n";
            foreach ($headers as $h) {
                $xml .= '    <Cell><Data ss:Type="String">'.htmlspecialchars($h, ENT_XML1).'</Data></Cell>'."\n";
            }
            $xml .= '   </Row>'."\n";
            echo $xml;

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

