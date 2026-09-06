<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Invitation - {{ config('app.name', 'VektorLeads') }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f5fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #4b465c;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f5fa;
            padding: 40px 0;
        }
        .main-container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #ebeef4;
        }
        .header {
            padding: 32px 32px 24px;
            text-align: center;
            background: linear-gradient(135deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.02) 100%);
            border-bottom: 1px solid #f0f2f8;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 700;
            color: #4b465c;
            margin: 0;
        }
        .brand-title span {
            color: #7367f0;
        }
        .content {
            padding: 32px;
        }
        .org-badge {
            display: inline-block;
            padding: 6px 14px;
            background-color: #f2f1ff;
            color: #7367f0;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 20px;
            font-weight: 600;
            color: #2f2b3d;
            margin: 0 0 14px;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #6f6b7d;
            margin: 0 0 18px;
        }
        .cta-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            background-color: #7367f0;
            color: #ffffff !important;
            text-decoration: none;
            padding: 13px 32px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 3px 12px rgba(115, 103, 240, 0.35);
        }
        .direct-link-box {
            background-color: #f8f9fc;
            border: 1px dashed #dcdfe8;
            border-radius: 8px;
            padding: 14px;
            font-size: 12px;
            color: #8a8d93;
            word-break: break-all;
            margin-top: 24px;
        }
        .footer {
            padding: 24px 32px;
            background-color: #fafafc;
            border-top: 1px solid #f0f2f8;
            text-align: center;
            font-size: 12px;
            color: #a5a3ae;
        }
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="main-container" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <h2 class="brand-title">Vektor<span>Leads</span></h2>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="content">
                            <div class="org-badge">
                                🏢 {{ $tenant->name }}
                            </div>

                            <h1>You've been invited to join {{ $tenant->name }}!</h1>

                            <p>
                                @if ($invitedBy)
                                    <strong>{{ $invitedBy->name }}</strong> has invited you to join the team on <strong>{{ config('app.name', 'VektorLeads') }}</strong>.
                                @else
                                    You have been invited to join the <strong>{{ $tenant->name }}</strong> organization workspace on <strong>{{ config('app.name', 'VektorLeads') }}</strong>.
                                @endif
                            </p>

                            <p>
                                With your staff account, you will be able to search and discover local business leads, run automated enrichment, and manage outreach workflows.
                            </p>

                            <div class="cta-container">
                                <a href="{{ $acceptUrl }}" class="btn" target="_blank">Accept Invitation &amp; Set Up Account</a>
                            </div>

                            <p style="font-size: 13px; color: #8a8d93; margin-bottom: 0;">
                                ⏱ This invitation link is valid for 24 hours (1 day). If you didn't expect this invitation, you can safely ignore this email.
                            </p>

                            <div class="direct-link-box">
                                If the button above doesn't work, copy and paste this URL into your browser:<br>
                                <a href="{{ $acceptUrl }}" style="color: #7367f0; text-decoration: underline;">{{ $acceptUrl }}</a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            &copy; {{ date('Y') }} {{ config('app.name', 'VektorLeads') }}. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
