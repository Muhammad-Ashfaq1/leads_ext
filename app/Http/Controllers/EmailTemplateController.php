<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\LeadEmailLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $tenantId = $user?->tenant_id;

        $templates = EmailTemplate::query()
            ->forTenant($tenantId, $isSuperAdmin)
            ->latest('id')
            ->get();

        $logs = LeadEmailLog::query()
            ->with(['lead', 'template', 'user'])
            ->when(! $isSuperAdmin && $tenantId, function ($q) use ($tenantId): void {
                $q->where(function ($sub) use ($tenantId): void {
                    $sub->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                });
            })
            ->latest('id')
            ->paginate(15);

        return view('email-templates.index', [
            'templates' => $templates,
            'logs' => $logs,
            'user' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user?->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            EmailTemplate::where('tenant_id', $tenantId)->update(['is_default' => false]);
        }

        $template = EmailTemplate::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'name' => $validated['name'],
            'category' => $validated['category'] ?: 'Outreach',
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'description' => $validated['description'] ?? null,
            'is_default' => $isDefault,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Email template created successfully.',
                'template' => $template,
            ]);
        }

        return redirect()->route('email-templates.index')->with('success', 'Email template created successfully.');
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $tenantId = $user?->tenant_id;

        if ($emailTemplate->tenant_id && $tenantId && ! $isSuperAdmin && $emailTemplate->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            EmailTemplate::where('tenant_id', $tenantId)->where('id', '!=', $emailTemplate->id)->update(['is_default' => false]);
        }

        $emailTemplate->update([
            'name' => $validated['name'],
            'category' => $validated['category'] ?: 'Outreach',
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'description' => $validated['description'] ?? null,
            'is_default' => $isDefault,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Email template updated successfully.',
                'template' => $emailTemplate,
            ]);
        }

        return redirect()->route('email-templates.index')->with('success', 'Email template updated successfully.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $tenantId = $user?->tenant_id;

        if ($emailTemplate->tenant_id && $tenantId && ! $isSuperAdmin && $emailTemplate->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        $emailTemplate->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Email template deleted successfully.',
            ]);
        }

        return redirect()->route('email-templates.index')->with('success', 'Email template deleted successfully.');
    }

    public function setDefault(EmailTemplate $emailTemplate): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $tenantId = $user?->tenant_id;

        if ($emailTemplate->tenant_id && $tenantId && ! $isSuperAdmin && $emailTemplate->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        EmailTemplate::where('tenant_id', $tenantId)->update(['is_default' => false]);
        $emailTemplate->update(['is_default' => true]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Default template updated.']);
        }

        return redirect()->route('email-templates.index')->with('success', 'Default template updated.');
    }

    public function listJson(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $tenantId = $user?->tenant_id;

        $templates = EmailTemplate::query()
            ->forTenant($tenantId, $isSuperAdmin)
            ->latest('is_default')
            ->latest('id')
            ->get(['id', 'name', 'category', 'subject', 'body', 'is_default']);

        return response()->json($templates);
    }
}

