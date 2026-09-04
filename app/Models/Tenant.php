<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'plan',
        'plan_id',
        'lead_quota',
        'leads_extracted_count',
        'google_maps_api_key',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'plan_id' => 'integer',
            'lead_quota' => 'integer',
            'leads_extracted_count' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(ExtractionJob::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(ExtractedLead::class);
    }

    public function gmailAccounts(): HasMany
    {
        return $this->hasMany(GmailAccount::class);
    }

    public function gmailMessages(): HasMany
    {
        return $this->hasMany(GmailMessage::class);
    }

    public const MAX_STAFF_MEMBERS = 5;

    public function staffMembers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'user');
    }

    public function staffMembersCount(): int
    {
        return $this->staffMembers()->count();
    }

    public function canAddStaffMember(): bool
    {
        return $this->staffMembersCount() < self::MAX_STAFF_MEMBERS;
    }

    public function adminUser()
    {
        return $this->hasOne(User::class)->where('role', 'admin');
    }

    public function hasQuotaAvailable(int $amount = 1): bool
    {
        if ($this->lead_quota <= 0) {
            return true; // unlimited
        }

        return ($this->leads_extracted_count + $amount) <= $this->lead_quota;
    }

    public function incrementLeadsCount(int $amount = 1): void
    {
        $this->increment('leads_extracted_count', $amount);
    }
}

