<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $email_title ?? 'Notification' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0; padding:0; background-color:#f4f6f9; font-family: Arial, sans-serif;">

    <!-- Wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding:30px 0;">
        <tr>
            <td align="center">

                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background-color:#ffffff; border-radius:8px; overflow:hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="padding:25px 30px; text-align:center; border-bottom:1px solid #eeeeee;">
                            @if(isset($logo_url))
                                <img src="{{ $logo_url }}" alt="Company Logo" width="140"
                                    style="display:block; margin:0 auto 10px;">
                            @endif
                            <h2 style="margin:0; font-size:18px; color:#1a1a1a;">{{ $company_name ?? 'KoboPoint' }}</h2>
                            <p style="margin:5px 0 0; font-size:12px; color:#777777;">
                                Secure Payment Infrastructure
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:35px 30px;">

                            <!-- Title -->
                            <h1 style="font-size:22px; margin:0 0 20px; color:#111111;">
                                {{ $title ?? 'Notification' }}
                            </h1>

                            <!-- Greeting -->
                            <p style="font-size:14px; color:#333333; margin:0 0 15px;">
                                Hello {{ $user_name ?? 'User' }},
                            </p>

                            <!-- Main Message -->
                            <p style="font-size:14px; line-height:22px; color:#555555; margin:0 0 25px;">
                                {!! $message_body ?? '' !!}
                            </p>

                            <!-- Highlight Box (For OTP / Transactions / Important Info) -->
                            @if(isset($highlight_value))
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:25px;">
                                    <tr>
                                        <td
                                            style="background-color:#f1f5ff; padding:18px; border-radius:6px; text-align:center;">
                                            <p style="margin:0; font-size:14px; color:#555555;">
                                                {{ $highlight_label ?? 'Details' }}
                                            </p>
                                            <p style="margin:8px 0 0; font-size:20px; font-weight:bold; color:#111111;">
                                                {{ $highlight_value }}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <!-- Optional Details Table -->
                            @if(isset($details_rows))
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:25px;">
                                    {!! $details_rows !!}
                                </table>
                            @endif

                            <!-- Button (Optional) -->
                            @if(isset($button_text) && isset($button_link))
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                                    <tr>
                                        <td align="center">
                                            <a href="{{ $button_link }}" style="display:inline-block; padding:12px 24px; 
                                  background-color:#0a58ca; 
                                  color:#ffffff; 
                                  text-decoration:none; 
                                  border-radius:6px; 
                                  font-size:14px;">
                                                {{ $button_text }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <!-- Security Notice -->
                            <p style="font-size:12px; color:#888888; margin-top:30px; line-height:18px;">
                                If you did not authorize this action, please contact our support team immediately.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 30px; background-color:#fafafa; border-top:1px solid #eeeeee;">

                            <p style="margin:0 0 5px; font-size:12px; color:#777777;">
                                {{ $company_legal_name ?? 'KoboPoint Payment Technologies Ltd' }}
                            </p>

                            <p style="margin:0 0 5px; font-size:12px; color:#777777;">
                                {{ $company_address ?? 'Lagos, Nigeria' }}
                            </p>

                            <p style="margin:0 0 5px; font-size:12px; color:#777777;">
                                Support: <a href="mailto:{{ $support_email ?? 'support@kobopoint.com' }}"
                                    style="color:#0a58ca; text-decoration:none;">
                                    {{ $support_email ?? 'support@kobopoint.com' }}
                                </a>
                            </p>

                            <p style="margin:10px 0 0; font-size:11px; color:#aaaaaa;">
                                © {{ date('Y') }} {{ $company_name ?? 'KoboPoint' }}. All rights reserved.
                            </p>

                        </td>
                    </tr>

                </table>
                <!-- End Container -->

            </td>
        </tr>
    </table>
    <!-- End Wrapper -->

</body>

</html>