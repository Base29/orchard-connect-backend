<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Ticket Received</title>
</head>
<body style="margin: 0; padding: 0; background-color: #fafafa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fafafa; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px 40px; text-align: left;">
                            <h2 style="margin: 0; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #10b981;">Orchard Connect</h2>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 20px 40px 40px 40px; text-align: left;">
                            <h1 style="margin: 0 0 20px 0; font-size: 24px; font-weight: 800; color: #111111; line-height: 1.3;">Support Ticket Received</h1>
                            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #555555; font-weight: 400;">
                                Hello {{ $ticket->guest_name }},
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #555555; font-weight: 400;">
                                We have received your support ticket regarding <strong>"{{ $ticket->subject }}"</strong>. A member of our community administration team will review your request shortly.
                            </p>
                            <p style="margin: 0 0 30px 0; font-size: 15px; line-height: 1.6; color: #555555; font-weight: 400;">
                                Your unique ticket reference is: <strong style="font-size: 18px; color: #111111; background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; font-family: monospace; letter-spacing: 1px;">{{ $ticket->tracking_id }}</strong>
                            </p>
                            <!-- Button -->
                            <table border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center" style="border-radius: 4px; background-color: #10b981;">
                                        <a href="{{ $trackUrl }}" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 4px; border: 1px solid #10b981; background-color: #10b981;">Track Ticket Progress</a>
                                    </td>
                                </tr>
                            </table>
                            <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 30px 0;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #888888;">
                                If you did not submit this support ticket, please contact us at support@orchardconnect.pk.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
