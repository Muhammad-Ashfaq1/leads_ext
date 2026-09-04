<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmailMessage extends Model
{
    protected $fillable = [
        'tenant_id',
        'gmail_account_id',
        'extracted_lead_id',
        'gmail_message_id',
        'gmail_thread_id',
        'sender_name',
        'sender_email',
        'recipient_email',
        'subject',
        'snippet',
        'body_text',
        'body_html',
        'received_at',
        'is_read',
        'is_starred',
        'labels',
        'has_attachments',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'has_attachments' => 'boolean',
        'labels' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function gmailAccount(): BelongsTo
    {
        return $this->belongsTo(GmailAccount::class);
    }

    public function extractedLead(): BelongsTo
    {
        return $this->belongsTo(ExtractedLead::class);
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
