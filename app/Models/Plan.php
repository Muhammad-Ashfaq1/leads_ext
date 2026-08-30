<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_interval',
        'lead_quota',
        'max_staff_members',
        'features',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'lead_quota' => 'integer',
            'max_staff_members' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }

    public function getFormattedPriceAttribute(): string
    {
        if ((float) $this->price <= 0) {
            return 'Free';
        }

        return '$'.number_format((float) $this->price, 0).'/'.($this->billing_interval === 'yearly' ? 'yr' : 'mo');
    }

    public function getFeaturesList(): array
    {
        if (is_array($this->features)) {
            return $this->features;
        }

        if (is_string($this->features) && ! empty($this->features)) {
            return array_filter(array_map('trim', explode("\n", $this->features)));
        }

        return [
            number_format($this->lead_quota).' Leads Monthly',
            $this->max_staff_members.' Staff Team Members',
            'Cloud Lead Finder Access',
            'Email & Social Extraction',
            'Export to Excel / CSV',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }
}
