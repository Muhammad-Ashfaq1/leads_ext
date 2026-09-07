<?php

namespace App\Mail;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->invitation->tenant?->name ?? config('app.name');

        return new Envelope(
            subject: "You're invited to join {$tenantName} on " . config('app.name', 'VektorLeads'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invitation',
            with: [
                'invitation' => $this->invitation,
                'tenant' => $this->invitation->tenant,
                'invitedBy' => $this->invitation->invitedBy,
                'acceptUrl' => $this->invitation->accept_url,
                'appUrl' => config('app.url', 'https://leads.obtainsolutions.com'),
                'posUrl' => 'https://pos.obtainsolutions.com/',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
