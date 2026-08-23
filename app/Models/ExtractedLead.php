<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtractedLead extends Model
{
    protected $fillable = [
        'extraction_job_id',
        'business_name',
        'address',
        'phone',
        'emails',
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
        'metadata',
        'extracted_at',
    ];

    protected function casts(): array
    {
        return [
            'emails' => 'array',
            'metadata' => 'array',
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

    public static function fromPayload(array $lead): array
    {
        return [
            'business_name' => $lead['business_name'] ?? null,
            'address' => $lead['address'] ?? null,
            'phone' => $lead['phone'] ?? null,
            'emails' => $lead['emails'] ?? [],
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
            'metadata' => $lead['metadata'] ?? [],
            'extracted_at' => $lead['extracted_at'] ?? now(),
        ];
    }
}
