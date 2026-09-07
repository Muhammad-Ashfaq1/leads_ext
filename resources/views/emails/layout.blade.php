<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'Message') | {{ config('app.name', 'VektorLeads') }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
{{-- #f8f7fa is the application's Vuexy/POS body background --}}
<body style="margin: 0; padding: 0; background-color: #f8f7fa; font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0; background-color: #f8f7fa;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse; border: 0; border-spacing: 0; background-color: #ffffff; border-radius: 16px; box-shadow: 0 8px 26px -14px rgba(115, 103, 240, 0.34), 0 1px 2px rgba(47, 43, 61, 0.04); overflow: hidden;">
                    {{-- Header with Solid Fallback + Purple Gradient matching POS --}}
                    <tr>
                        <td style="padding: 36px 40px 30px; text-align: center; background-color: #7367f0; background: linear-gradient(135deg, #8b7ff5 0%, #7367f0 45%, #5a4fd4 100%); border-radius: 16px 16px 0 0;">
                            <table role="presentation" style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0;">
                                <tr>
                                    <td style="text-align: center; padding-bottom: 16px;">
                                        <table role="presentation" align="center" style="border-collapse: collapse; border: 0; border-spacing: 0; margin: 0 auto;">
                                            <tr>
                                                <td style="background-color: #ffffff; border-radius: 14px; padding: 10px; line-height: 0; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);">
                                                    <img src="{{ $brandLogoUrl ?? (rtrim(config('app.url', 'https://leads.obtainsolutions.com'), '/') . '/assets/img/favicon/apple-touch-icon.png') }}"
                                                         alt="VektorLeads Logo"
                                                         width="48" height="48"
                                                         style="display: block; width: 48px; height: 48px; border-radius: 10px; border: 0; outline: none; text-decoration: none;">
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: center;">
                                        <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: -0.5px;">
                                            {{ config('app.name', 'VektorLeads') }}
                                        </h1>
                                        <p style="margin: 6px 0 0; color: rgba(255, 255, 255, 0.9); font-size: 14px; font-weight: 400;">
                                            @yield('subtitle', 'Local Leads Discovery & Outreach Engine')
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Email Content -->
                    <tr>
                        <td style="padding: 36px 36px 20px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer (Matching POS & Obtain Solutions) -->
                    <tr>
                        <td style="padding: 28px 36px; background-color: #faf9fc; border-top: 1px solid #e7e5ef; border-radius: 0 0 16px 16px;">
                            <table role="presentation" style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0;">
                                <tr>
                                    <td style="text-align: center; padding-bottom: 16px;">
                                        <p style="margin: 0 0 8px; color: #6b7280; font-size: 13px; line-height: 1.6;">
                                            This is an automated notification from <strong>{{ config('app.name', 'VektorLeads') }}</strong>.
                                        </p>
                                        <p style="margin: 0; color: #8a909d; font-size: 12px; line-height: 1.6;">
                                            Application: 
                                            <a href="{{ $appUrl ?? config('app.url', 'https://leads.obtainsolutions.com') }}"
                                               style="color: #7367f0; text-decoration: none; font-weight: 600;">
                                                {{ rtrim(preg_replace('(^https?://)', '', $appUrl ?? config('app.url', 'leads.obtainsolutions.com')), '/') }}
                                            </a>
                                            &nbsp;|&nbsp;
                                            Powered by: 
                                            <a href="https://pos.obtainsolutions.com/"
                                               style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">
                                                Obtain Solutions POS
                                            </a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: center; padding-top: 16px; border-top: 1px solid #e7e5ef;">
                                        <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                            &copy; {{ date('Y') }} {{ config('app.name', 'VektorLeads') }} &bull; <a href="https://obtainsolutions.com" style="color: #9ca3af; text-decoration: none;" target="_blank">Obtain Solutions</a>. All rights reserved.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
