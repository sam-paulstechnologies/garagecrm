@php
  $brandName = config('mail.from.name', config('app.name', 'GarageCRM'));
  $brandUrl  = config('app.url');
@endphp

<div class="footer">
  @if(!empty($footer_note))
    <p style="margin:0 0 6px 0;">{{ $footer_note }}</p>
  @endif
  <p style="margin:0;">© {{ now()->year }} {{ $brandName }}. All rights reserved.</p>
  @if($brandUrl)
    <p style="margin:6px 0 0 0;"><a href="{{ $brandUrl }}" target="_blank" style="color:inherit; text-decoration:underline;">{{ parse_url($brandUrl, PHP_URL_HOST) ?? $brandUrl }}</a></p>
  @endif>
</div>
