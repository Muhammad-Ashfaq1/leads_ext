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
        $isVisible = $user && ExtractedLead::query()->visibleTo($user)->where('id', $lead->id)->exists();
        if (! $isVisible) {
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

    public function preview(Request $request, string $uuid, GeminiWebsiteService $service): View
    {
        $lead = ExtractedLead::where('uuid', $uuid)->firstOrFail();

        $content = $lead->generated_website_content;

        if (empty($content) || ! is_array($content)) {
            $content = $service->normalizeContent([], $lead);
        }

        $viewName = view()->exists('preview.sample-site') ? 'preview.sample-site' : 'leads.preview';

        return view($viewName, compact('lead', 'content'));
    }
}

