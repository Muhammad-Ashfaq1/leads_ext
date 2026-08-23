<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'plan',
        'lead_quota',
        'leads_extracted_count',
        'google_maps_api_key',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'lead_quota' => 'integer',
            'leads_extracted_count' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
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
