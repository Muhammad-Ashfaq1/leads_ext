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

        $search = trim($request->input('search', ''));
        $category = $request->input('category');
        $hasEmail = $request->input('has_email');
        $verifiedEmail = $request->input('verified_email');
        $hasPhone = $request->input('has_phone');
        $hasWebsite = $request->input('has_website');
        $hasSocial = $request->input('has_social');
        $status = $request->input('status');
        $minRating = $request->input('min_rating');
        $minReviews = $request->input('min_reviews');
        $sort = $request->input('sort', 'newest');
        $source = $request->input('source');
        $jobId = $request->input('job_id');

        $query = ExtractedLead::query()
            ->with(['job', 'tenant', 'user'])
            ->visibleTo($user)
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('business_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('emails', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($hasEmail === 'yes', function ($q): void {
                $q->whereNotNull('emails')
                    ->where('emails', 'like', '%@%');
            })
            ->when($hasEmail === 'no', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNull('emails')
                        ->orWhere('emails', 'not like', '%@%')
                        ->orWhere('emails', '[]')
                        ->orWhere('emails', 'null')
                        ->orWhere('emails', '')
                        ->orWhere('emails', '[""]');
                });
            })
            ->when($hasEmail === 'verified' || $verifiedEmail === 'yes', function ($q): void {
                $q->whereNotNull('emails')
                    ->where('emails', 'like', '%@%')
                    ->where(function ($sub): void {
                        $sub->where('email_verification_status', 'like', '%"is_valid":true%')
                            ->orWhere('email_verification_status', 'like', '%"is_valid": true%');
                    });
            })
            ->when($hasPhone === 'yes', function ($q): void {
                $q->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->where('phone', '!=', 'null');
            })
            ->when($hasPhone === 'no', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNull('phone')
                        ->orWhere('phone', '')
                        ->orWhere('phone', 'null');
                });
            })
            ->when($hasWebsite === 'yes', function ($q): void {
                $q->whereNotNull('website')
                    ->where('website', '!=', '')
                    ->where('website', '!=', 'null');
            })
            ->when($hasWebsite === 'no', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNull('website')
                        ->orWhere('website', '')
                        ->orWhere('website', 'null');
                });
            })
            ->when($hasSocial === 'yes', function ($q): void {
                $q->whereNotNull('social_links')
                    ->where(function ($sub): void {
                        $sub->where('social_links', 'like', '%http%')
                            ->orWhere('social_links', 'like', '%linkedin%')
                            ->orWhere('social_links', 'like', '%facebook%')
                            ->orWhere('social_links', 'like', '%instagram%')
                            ->orWhere('social_links', 'like', '%twitter%')
                            ->orWhere('social_links', 'like', '%youtube%');
                    });
            })
            ->when($hasSocial === 'no', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNull('social_links')
                        ->orWhere('social_links', 'not like', '%http%')
                        ->orWhere('social_links', '[]')
                        ->orWhere('social_links', '{}')
                        ->orWhere('social_links', 'null')
                        ->orWhere('social_links', '');
                });
            })
            ->when($status === 'saved', fn ($q) => $q->where(fn ($sub) => $sub->where('status', 'saved')->orWhere('is_saved', true)))
            ->when($status === 'discarded', fn ($q) => $q->where('status', 'discarded'))
            ->when($status === 'new', fn ($q) => $q->where('status', 'new'))
            ->when($minRating === 'unrated', fn ($q) => $q->where(fn ($sub) => $sub->whereNull('rating')->orWhere('rating', '<=', 0)))
            ->when(is_numeric($minRating), fn ($q) => $q->where('rating', '>=', (float) $minRating))
            ->when($minReviews === '0', fn ($q) => $q->where(fn ($sub) => $sub->whereNull('review_count')->orWhere('review_count', 0)))
            ->when($minReviews === '1', fn ($q) => $q->where('review_count', '>=', 1))
            ->when($minReviews === '10', fn ($q) => $q->where('review_count', '>=', 10))
            ->when($minReviews === '50', fn ($q) => $q->where('review_count', '>=', 50))
            ->when($minReviews === '100', fn ($q) => $q->where('review_count', '>=', 100))
            ->when($source, fn ($q) => $q->where('source', $source))
            ->when($jobId, fn ($q) => $q->where('extraction_job_id', $jobId));

        // Sorting
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'rating_desc' => $query->orderByDesc('rating')->latest('id'),
            'rating_asc' => $query->orderBy('rating')->latest('id'),
            'reviews_desc' => $query->orderByDesc('review_count')->latest('id'),
            'name_asc' => $query->orderBy('business_name'),
            'name_desc' => $query->orderByDesc('business_name'),
            default => $query->latest('id'),
        };

        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100, 250], true)) {
            $perPage = 10;
        }

        $leads = $query->paginate($perPage)->withQueryString();

        // Available categories for filter dropdown
        $categories = ExtractedLead::query()
            ->visibleTo($user)
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
                'has_social' => $hasSocial,
                'status' => $status,
                'min_rating' => $minRating,
                'min_reviews' => $minReviews,
                'sort' => $sort,
                'source' => $source,
                'job_id' => $jobId,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $user = Auth::user();

        $rawIds = $request->input('ids');
        $ids = null;
        if (! empty($rawIds)) {
            $ids = is_array($rawIds) ? array_map('intval', $rawIds) : array_map('intval', explode(',', (string) $rawIds));
            $ids = array_values(array_filter($ids, fn ($id) => $id > 0));
        }

        $query = ExtractedLead::query()
            ->visibleTo($user)
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

