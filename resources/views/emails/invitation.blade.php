<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Invited</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #111; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .header { background: #111; padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; font-size: 20px; font-weight: 600; letter-spacing: -0.3px; }
        .body { padding: 40px; }
        .greeting { font-size: 24px; font-weight: 700; color: #111; margin-bottom: 16px; line-height: 1.3; }
        .text { font-size: 15px; color: #444; line-height: 1.6; margin-bottom: 16px; }
        .role-badge { display: inline-block; background: #f0f0f0; color: #333; font-size: 13px; font-weight: 600; padding: 4px 12px; border-radius: 100px; margin-bottom: 24px; }
        .cta { text-align: center; margin: 32px 0; }
        .cta a { display: inline-block; background: #111; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 15px; font-weight: 600; letter-spacing: -0.2px; }
        .cta a:hover { background: #333; }
        .divider { border: none; border-top: 1px solid #eee; margin: 28px 0; }
        .small { font-size: 13px; color: #888; line-height: 1.6; }
        .small a { color: #555; word-break: break-all; }
        .footer { background: #fafafa; border-top: 1px solid #eee; padding: 24px 40px; text-align: center; }
        .footer p { font-size: 12px; color: #aaa; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ tenancy()->tenant?->company_name ?? config('app.name') }}</h1>
        </div>

        <div class="body">
            <p class="greeting">You've been invited!</p>

            <p class="text">
                <strong>{{ $invitation->inviter?->name ?? 'Someone' }}</strong> has invited you to join
                <strong>{{ tenancy()->tenant?->company_name ?? config('app.name') }}</strong> on
                {{ config('app.name') }}.
            </p>

            <p class="text">You'll be joining as:</p>
            <span class="role-badge">{{ $invitation->role->display_name }}</span>

            <div class="cta">
                @php
                    $domain = tenancy()->tenant?->domains()->first()?->domain ?? request()->getHost();
                    $url = 'https://' . $domain . '/accept-invitation/' . $invitation->token;
                @endphp
                <a href="{{ $url }}">Accept Invitation</a>
            </div>

            <hr class="divider">

            <p class="small">
                This invitation expires on
                <strong>{{ $invitation->expires_at->format('F j, Y') }}</strong>.
                If you weren't expecting this invitation, you can safely ignore this email.
            </p>

            <p class="small" style="margin-top: 12px;">
                Or copy this link into your browser:<br>
                <a href="{{ $url }}">{{ $url }}</a>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ tenancy()->tenant?->company_name ?? config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
