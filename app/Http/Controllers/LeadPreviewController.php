<?php

namespace App\Http\Controllers;

use App\Models\ExtractedLead;
use App\Services\GeminiWebsiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class LeadPreviewController extends Controller
{
    public function generate(Request $request, int|string $id, GeminiWebsiteService $service): JsonResponse
    {
        $lead = ExtractedLead::findOrFail($id);

        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin() && $user->tenant_id && $lead->tenant_id && $lead->tenant_id !== $user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to generate demo for this lead.',
            ], 403);
        }

        try {
            $content = $service->generateSite($lead);

            return response()->json([
                'success' => true,
                'message' => '✨ AI Demo website generated successfully!',
                'preview_url' => route('leads.preview', $lead->uuid),
                'lead_id' => $lead->id,
                'lead_uuid' => $lead->uuid,
                'data' => $content,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Lead demo website generation failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate demo website: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function preview(Request $request, string $uuid): View
    {
        $lead = ExtractedLead::where('uuid', $uuid)->firstOrFail();

        $content = $lead->generated_website_content;

        if (empty($content) || ! is_array($content)) {
            $bizName = $lead->business_name ?: 'Our Business';
            $category = $lead->category ?: 'Professional Services';
            $city = $lead->city ?: 'Your Area';

            $content = [
                'hero_headline' => "Premium {$category} Solutions by {$bizName}",
                'hero_subheadline' => "Delivering trusted, top-rated {$category} expertise in {$city} and beyond.",
                'about_text' => "At {$bizName}, we are dedicated to providing superior {$category} with unmatched customer dedication, reliable craftsmanship, and a proven track record.",
                'services' => [
                    [
                        'title' => 'Customized Solutions',
                        'description' => "Tailored {$category} solutions designed specifically for your individual needs.",
                    ],
                    [
                        'title' => 'Professional Execution',
                        'description' => 'Experienced specialists providing prompt, detail-oriented service you can trust.',
                    ],
                    [
                        'title' => 'Customer Care & Quality',
                        'description' => 'Dedicated follow-up, transparent pricing, and 100% satisfaction commitment.',
                    ],
                ],
            ];
        }

        return view('leads.preview', compact('lead', 'content'));
    }
}

