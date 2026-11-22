<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:32px 16px;background:#FAF6F0;font-family:Arial,Helvetica,sans-serif;color:#321923;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #eadfe2;border-radius:14px;overflow:hidden;">
        <tr>
            <td style="padding:28px 36px;background:#3A091A;text-align:left;">
                <div style="font-family:Georgia,serif;font-size:25px;letter-spacing:.2px;color:#FAF6F0;font-weight:bold;">Apoint <span style="color:#E8B4B8;">Translations</span></div>
                <div style="margin-top:7px;color:#E8B4B8;font-size:11px;letter-spacing:2px;text-transform:uppercase;">Clarity across every language</div>
            </td>
        </tr>
        <tr>
            <td style="padding:36px;">
                <p style="margin:0 0 18px;color:#6c5961;font-size:14px;">Hello {{ $recipientName }},</p>
                <h1 style="margin:0 0 16px;color:#3A091A;font-family:Georgia,serif;font-size:25px;line-height:1.3;font-weight:normal;">{{ $title }}</h1>
                <p style="margin:0 0 28px;color:#55454b;font-size:15px;line-height:1.7;">{{ $bodyText }}</p>
                <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 23px;background:#3A091A;color:#ffffff;text-decoration:none;border-radius:7px;font-size:14px;font-weight:bold;">View details</a>
                <p style="margin:30px 0 0;color:#897b80;font-size:12px;line-height:1.6;">If the button does not work, copy and paste this link into your browser:<br><a href="{{ $actionUrl }}" style="color:#7a344c;word-break:break-all;">{{ $actionUrl }}</a></p>
            </td>
        </tr>
        <tr>
            <td style="padding:20px 36px;background:#FAF6F0;border-top:3px solid #D4AF37;color:#806f75;font-size:12px;line-height:1.6;">
                <strong style="color:#3A091A;">Apoint Translations</strong><br>
                This is an automated service email about your Apoint account. Please do not reply directly to this message.
            </td>
        </tr>
    </table>
</body>
</html>
