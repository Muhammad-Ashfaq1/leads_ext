<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExtractedLead extends Model
{
    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'extraction_job_id',
        'business_name',
        'address',
        'phone',
        'emails',
        'social_links',
        'email_verification_status',
        'avatar_url',
        'website',
        'google_maps_url',
        'place_id',
        'category',
        'rating',
        'review_count',
        'business_hours',
        'latitude',
        'longitude',
        'city',
        'country',
        'source',
        'status',
        'is_saved',
        'metadata',
        'generated_website_content',
        'extracted_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (ExtractedLead $lead): void {
            if (empty($lead->uuid)) {
                $lead->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'emails' => 'array',
            'social_links' => 'array',
            'email_verification_status' => 'array',
            'metadata' => 'array',
            'generated_website_content' => 'array',
            'is_saved' => 'boolean',
            'rating' => 'float',
            'review_count' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'extracted_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(ExtractionJob::class, 'extraction_job_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gmailMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GmailMessage::class);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        if ($user->tenant_id) {
            $query->where(function (Builder $sub) use ($user): void {
                $sub->where('tenant_id', $user->tenant_id)
                    ->orWhereNull('tenant_id');
            });
        }

        if ($user->canViewOrganizationLeads()) {
            $query->where(function (Builder $sub) use ($user): void {
                $sub->where('is_saved', true)
                    ->orWhere('status', 'saved')
                    ->orWhere('user_id', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function fromPayload(array $lead, ?int $tenantId = null, ?int $userId = null): array
    {
        return [
            'uuid' => $lead['uuid'] ?? (string) Str::uuid(),
            'tenant_id' => $tenantId ?? ($lead['tenant_id'] ?? null),
            'user_id' => $userId ?? ($lead['user_id'] ?? null),
            'business_name' => $lead['business_name'] ?? null,
            'address' => $lead['address'] ?? null,
            'phone' => $lead['phone'] ?? null,
            'emails' => $lead['emails'] ?? [],
            'social_links' => $lead['social_links'] ?? [],
            'email_verification_status' => $lead['email_verification_status'] ?? [],
            'avatar_url' => $lead['avatar_url'] ?? null,
            'website' => $lead['website'] ?? null,
            'google_maps_url' => $lead['google_maps_url'] ?? null,
            'place_id' => $lead['place_id'] ?? null,
            'category' => $lead['category'] ?? null,
            'rating' => $lead['rating'] ?? null,
            'review_count' => $lead['review_count'] ?? null,
            'business_hours' => $lead['business_hours'] ?? null,
            'latitude' => $lead['latitude'] ?? null,
            'longitude' => $lead['longitude'] ?? null,
            'city' => $lead['city'] ?? null,
            'country' => $lead['country'] ?? null,
            'source' => $lead['source'] ?? 'Google Maps',
            'status' => $lead['status'] ?? 'saved',
            'is_saved' => $lead['is_saved'] ?? true,
            'metadata' => $lead['metadata'] ?? [],
            'generated_website_content' => $lead['generated_website_content'] ?? null,
            'extracted_at' => $lead['extracted_at'] ?? now(),
        ];
    }
}
