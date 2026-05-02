<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Activate your account at {{ $tenant->school_name }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background:#f3f4f6; padding:24px;">
    <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;box-shadow:0 4px 12px rgba(0,0,0,0.06);color:#111;">
        <h1 style="margin:0 0 8px;font-size:22px;color:#1e3a8a;">Welcome to {{ $tenant->school_name }}</h1>
        <p style="margin:0 0 16px;color:#555;font-size:14px;">
            @if($kind === 'parent')
                A parent account has been created for you on {{ $tenant->school_name }}'s portal.
            @else
                A student account has been created for you on {{ $tenant->school_name }}'s portal.
            @endif
        </p>

        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Hi {{ $recipient->full_name ?? $recipient->name ?? 'there' }},<br><br>
            To activate your account and set your password, click the button below.
            This link will expire on <strong>{{ $expiresAt }}</strong>.
        </p>

        <p style="text-align:center;margin:28px 0;">
            <a href="{{ $setPasswordUrl }}" style="display:inline-block;background:#1e3a8a;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:15px;">
                Activate &amp; Set Password
            </a>
        </p>

        <p style="font-size:12px;color:#666;line-height:1.5;">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{{ $setPasswordUrl }}" style="color:#1e3a8a;word-break:break-all;">{{ $setPasswordUrl }}</a>
        </p>

        <hr style="border:0;border-top:1px solid #e5e7eb;margin:24px 0;">

        <p style="font-size:11px;color:#9ca3af;margin:0;">
            Didn't expect this email? You can safely ignore it — the account will remain inactive
            unless the link above is used. For support, contact your school administrator at
            {{ $tenant->admin_email }}.
        </p>
    </div>
</body>
</html>
