<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'category',
        'subject',
        'body',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LeadEmailLog::class);
    }

    public function scopeForTenant(Builder $query, ?int $tenantId, bool $isSuperAdmin = false): Builder
    {
        if ($isSuperAdmin) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($tenantId): void {
            if ($tenantId) {
                $sub->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            } else {
                $sub->whereNull('tenant_id');
            }
        });
    }

    public function renderForLead(ExtractedLead $lead, ?User $sender = null): array
    {
        $vars = [
            '{{business_name}}' => $lead->business_name ?? 'Business Owner',
            '{{email}}' => is_array($lead->emails) ? ($lead->emails[0] ?? '') : (string) ($lead->emails ?? ''),
            '{{phone}}' => $lead->phone ?? '',
            '{{website}}' => $lead->website ?? '',
            '{{category}}' => $lead->category ?? 'your business',
            '{{address}}' => $lead->address ?? '',
            '{{city}}' => $lead->city ?: (explode(',', $lead->address ?? '')[0] ?? ''),
            '{{rating}}' => $lead->rating ? (string) $lead->rating : '',
            '{{reviews}}' => $lead->review_count ? (string) $lead->review_count : '',
            '{{sender_name}}' => $sender?->name ?? 'Our Team',
            '{{sender_company}}' => $sender?->tenant?->name ?? config('app.name', 'VektorLeads'),
        ];

        $renderedSubject = str_replace(array_keys($vars), array_values($vars), $this->subject);
        $renderedBody = str_replace(array_keys($vars), array_values($vars), $this->body);

        return [
            'subject' => $renderedSubject,
            'body' => $renderedBody,
        ];
    }
}

