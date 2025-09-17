@php
  // Local/dev-only preview of the notification email
  $subject   = 'Booking Confirmed';
  $title     = 'Your booking is confirmed';
  $greeting  = 'Hi Sam,';
  $lines     = [
    'We\'re happy to let you know your booking for tomorrow has been confirmed.',
    'You can review details and reschedule any time from your dashboard.',
  ];
  $cta_text  = 'Open Dashboard';
  $cta_url   = config('app.url') ? rtrim(config('app.url'), '/') . '/dashboard' : 'https://example.com/dashboard';
  $outro     = 'Thanks for choosing us!';
  $footer_note = 'This is a system notification — do not share this link publicly.';
  $hero_url  = null; // e.g., 'https://picsum.photos/1200/500'
@endphp

@include('emails.notification', [
  'subject'     => $subject,
  'title'       => $title,
  'greeting'    => $greeting,
  'lines'       => $lines,
  'cta_text'    => $cta_text,
  'cta_url'     => $cta_url,
  'outro'       => $outro,
  'footer_note' => $footer_note,
  'hero_url'    => $hero_url,
])
