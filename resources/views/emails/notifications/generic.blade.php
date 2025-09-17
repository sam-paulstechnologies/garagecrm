<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $subject ?? 'Notification' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { margin:0; padding:0; background:#f5f7fb; font-family:-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:#111827; }
    .container { max-width:620px; margin:40px auto; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.05); }
    .header { padding:20px 24px; background:#111827; color:#fff; font-weight:600; font-size:18px; }
    .content { padding:24px; font-size:16px; line-height:1.6; }
    .btn { display:inline-block; padding:12px 18px; border-radius:8px; text-decoration:none; background:#2563eb; color:#fff; font-weight:600; }
    .footer { padding:16px 24px; font-size:12px; color:#6b7280; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">{{ config('app.name') }}</div>
    <div class="content">
      <p>{{ $bodyText }}</p>

      @if(!empty($cta) && !empty($cta['url']) && !empty($cta['label']))
        <p style="margin-top:24px;">
          <a class="btn" href="{{ $cta['url'] }}" target="_blank" rel="noopener">{{ $cta['label'] }}</a>
        </p>
      @endif

      <p style="margin-top:24px; color:#6b7280; font-size:14px;">
        If you didn’t request this update, you can ignore this email.
      </p>
    </div>
    <div class="footer">
      &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>
  </div>
</body>
</html>
