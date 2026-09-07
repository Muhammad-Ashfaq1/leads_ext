@extends('emails.layout')

@section('title', "Invitation to join {$tenant->name}")
@section('subtitle', "Join {$tenant->name} on " . config('app.name', 'VektorLeads'))

@section('content')
<table role="presentation" style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0;">
    <tr>
        <td>
            <!-- Organization Chip Badge -->
            <table role="presentation" style="border-collapse: collapse; border: 0; border-spacing: 0; margin-bottom: 20px;">
                <tr>
                    <td style="background-color: #f2f1ff; border: 1px solid rgba(115, 103, 240, 0.2); border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 600; color: #7367f0;">
                        🏢 {{ $tenant->name }} Workspace
                    </td>
                </tr>
            </table>

            <h2 style="margin: 0 0 16px; color: #2f2b3d; font-size: 20px; font-weight: 700; line-height: 1.35;">
                You've been invited to join the team!
            </h2>

            <p style="margin: 0 0 16px; color: #5d596c; font-size: 15px; line-height: 1.6;">
                Hello,
            </p>

            <p style="margin: 0 0 20px; color: #5d596c; font-size: 15px; line-height: 1.6;">
                @if ($invitedBy)
                    <strong>{{ $invitedBy->name }}</strong> has invited you to join <strong>{{ $tenant->name }}</strong> on <strong>{{ config('app.name', 'VektorLeads') }}</strong> as a team member.
                @else
                    You have been invited to collaborate with <strong>{{ $tenant->name }}</strong> on <strong>{{ config('app.name', 'VektorLeads') }}</strong>.
                @endif
            </p>

            <p style="margin: 0 0 28px; color: #5d596c; font-size: 15px; line-height: 1.6;">
                With your staff account, you can discover high-density local business leads, run automated Google Places extractions, verify business emails, and manage outreach campaigns.
            </p>

            <!-- CTA Button -->
            <table role="presentation" style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0; margin-bottom: 28px;">
                <tr>
                    <td align="center">
                        <a href="{{ $acceptUrl }}"
                           style="display: inline-block; background-color: #7367f0; background: linear-gradient(135deg, #7367f0 0%, #5a4fd4 100%); color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 34px; border-radius: 8px; box-shadow: 0 4px 14px rgba(115, 103, 240, 0.4); text-align: center;">
                            Accept Invitation &amp; Set Up Account
                        </a>
                    </td>
                </tr>
            </table>

            <!-- Expiration Notice Callout -->
            <table role="presentation" style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0; background-color: #fffbeb; border-radius: 8px; border-left: 4px solid #f59e0b; margin-bottom: 24px;">
                <tr>
                    <td style="padding: 14px 18px;">
                        <p style="margin: 0; color: #92400e; font-size: 13px; line-height: 1.5;">
                            ⏱ <strong>Security Notice:</strong> This single-use invitation link expires in <strong>24 hours</strong>. If you were not expecting this invitation, you can safely disregard this email.
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Fallback URL -->
            <p style="margin: 0 0 8px; color: #8a8d93; font-size: 12px; line-height: 1.5;">
                If the button above does not work, copy and paste this link into your browser:
            </p>
            <p style="margin: 0 0 20px; color: #7367f0; font-size: 12px; line-height: 1.5; word-break: break-all;">
                <a href="{{ $acceptUrl }}" style="color: #7367f0; text-decoration: underline;">{{ $acceptUrl }}</a>
            </p>

            <p style="margin: 0; color: #8a8d93; font-size: 12px; line-height: 1.5;">
                Application Portal: <a href="{{ $appUrl ?? config('app.url', 'https://leads.obtainsolutions.com') }}" style="color: #7367f0; text-decoration: none; font-weight: 500;">{{ $appUrl ?? config('app.url', 'https://leads.obtainsolutions.com') }}</a>
            </p>
        </td>
    </tr>
</table>
@endsection
