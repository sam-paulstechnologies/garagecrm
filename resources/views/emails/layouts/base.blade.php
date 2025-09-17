@php
  $brandName = config('mail.from.name', config('app.name', 'GarageCRM'));
  $logoUrl   = asset('assets/email/logo.png');
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $subject ?? $brandName }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* Basic, inline-safe styles for most email clients */
    body { margin:0; padding:0; background:#f5f7fb; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Arial, sans-serif; color:#111827; }
    .wrapper { width:100%; background:#f5f7fb; padding:24px 0; }
    .container { width:100%; max-width:640px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(17,24,39,0.06); }
    .inner { padding:24px; }
    .brandbar { background:#111827; color:#ffffff; padding:16px 24px; display:flex; align-items:center; gap:12px; }
    .brandbar img { height:28px; width:auto; display:block; }
    .brandbar .brand { font-size:14px; font-weight:600; letter-spacing:0.3px; }
    .hero img { width:100%; height:auto; display:block; }
    h1 { font-size:22px; line-height:1.3; margin:16px 0 8px; }
    h2 { font-size:18px; line-height:1.4; margin:16px 0 8px; }
    p { font-size:14px; line-height:1.7; margin:12px 0; color:#374151; }
    .cta { display:inline-block; padding:12px 18px; border-radius:10px; background:#2563eb; color:#ffffff !important; text-decoration:none; font-weight:600; font-size:14px; }
    .spacer { height:16px; }
    .footer { text-align:center; color:#6b7280; font-size:12px; padding:18px 24px; }
    .divider { height:1px; background:#e5e7eb; margin:20px 0; }
    @media (prefers-color-scheme: dark) {
      body { background:#0b1220; color:#e5e7eb; }
      .container { background:#101826; }
      .brandbar { background:#0b1220; }
      p { color:#cbd5e1; }
      .divider { background:#1f2937; }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td align="center">
          <table role="presentation" class="container" cellspacing="0" cellpadding="0">
            <tr>
              <td class="brandbar">
                <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo">
                <span class="brand">{{ $brandName }}</span>
              </td>
            </tr>

            @include('emails.partials.header', ['title' => $title ?? null, 'greeting' => $greeting ?? null])

            @if(!empty($hero_url))
              <tr><td class="hero"><img src="{{ $hero_url }}" alt=""></td></tr>
            @endif

            <tr>
              <td class="inner">
                @yield('content')
                @if(!empty($cta_text) && !empty($cta_url))
                  <div class="spacer"></div>
                  <a href="{{ $cta_url }}" class="cta" target="_blank" rel="noopener">{{ $cta_text }}</a>
                @endif

                @if(!empty($outro))
                  <div class="spacer"></div>
                  <p>{{ $outro }}</p>
                @endif

                <div class="spacer"></div>
                <div class="divider"></div>
                @include('emails.partials.footer', ['footer_note' => $footer_note ?? null])
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>
