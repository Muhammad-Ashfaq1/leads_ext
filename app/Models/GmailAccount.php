<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmailAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'provider', // hostinger, gmail, custom_imap
        'google_id',
        'email',
        'name',
        'avatar_url',
        'password',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'sync_status',
        'error_message',
        'last_synced_at',
        'history_id',
        'is_active',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'imap_port' => 'integer',
        'smtp_port' => 'integer',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GmailMessage::class);
    }

    public function isHostinger(): bool
    {
        return $this->provider === 'hostinger';
    }

    public function isGmail(): bool
    {
        return $this->provider === 'gmail';
    }

    public function isImap(): bool
    {
        return in_array($this->provider, ['hostinger', 'custom_imap'], true) || ! empty($this->imap_host);
    }

    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        // Buffer of 2 minutes before actual expiration
        return Carbon::now()->addMinutes(2)->greaterThanOrEqualTo($this->token_expires_at);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('tenant_id', $user->tenant_id);
    }
}
