<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login credentials</title>
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; line-height: 1.5; color: #1f2937; max-width: 560px; margin: 0 auto; padding: 24px;">
    <p>Hello {{ $student->name }},</p>

    <p>Your CapTrack account is ready. Use the credentials below to sign in, then you will be asked to choose your own password.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 16px 0; width: 100%; background: #f3f4f6; border-radius: 8px;">
        <tr>
            <td style="padding: 16px;">
                <p style="margin: 0 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Student ID (login)</p>
                <p style="margin: 0; font-size: 18px; font-weight: 600;">{{ $student->student_id }}</p>
                <p style="margin: 16px 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Temporary password</p>
                <p style="margin: 0; font-size: 16px; font-family: ui-monospace, monospace; word-break: break-all;">{{ $temporaryPlainPassword }}</p>
            </td>
        </tr>
    </table>

    <p>
        <a href="{{ url('/login') }}" style="display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600;">Open login page</a>
    </p>

    <p style="font-size: 13px; color: #6b7280;">
        For security, do not share this email. After your first login you must set a new password; the temporary password will no longer be valid for reuse once changed.
    </p>

    <p style="font-size: 13px; color: #9ca3af;">— {{ config('app.name') }}</p>
</body>
</html>
