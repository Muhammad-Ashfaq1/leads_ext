<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ExtractionJob extends Model
{
    public const STATUS_IDLE = 'idle';

    public const STATUS_STARTING = 'starting';

    public const STATUS_SEARCHING = 'searching';

    public const STATUS_EXTRACTING = 'extracting';

    public const STATUS_ENRICHING = 'enriching';

    public const STATUS_WAITING_FOR_HUMAN_VERIFICATION = 'waiting_for_human_verification';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ERROR = 'error';

    public const STATUS_VERIFICATION_TIMEOUT = 'verification_timeout';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'uuid',
        'prompt',
        'query',
        'status',
        'limit',
        'mode',
        'businesses_seen',
        'leads_extracted',
        'emails_found',
        'websites_found',
        'current_activity',
        'started_at',
        'completed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'limit' => 'integer',
            'businesses_seen' => 'integer',
            'leads_extracted' => 'integer',
            'emails_found' => 'integer',
            'websites_found' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $job): void {
            $job->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(ExtractedLead::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_ERROR,
            self::STATUS_VERIFICATION_TIMEOUT,
            self::STATUS_BLOCKED,
        ], true);
    }

    public function toStatusArray(): array
    {
        return [
            'job_id' => $this->uuid,
            'status' => $this->status,
            'prompt' => $this->prompt,
            'query' => $this->query,
            'limit' => $this->limit,
            'businesses_seen' => $this->businesses_seen,
            'leads_extracted' => $this->leads_extracted,
            'emails_found' => $this->emails_found,
            'websites_found' => $this->websites_found,
            'current_activity' => $this->current_activity,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'error' => $this->error,
            'mode' => $this->mode,
        ];
    }
}
