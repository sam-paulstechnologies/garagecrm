<?php

return [
    // Storage
    'public_disk'    => env('FILESYSTEM_DISK', 'public'),

    // Validation
    'allowed_mimes'  => env('DOC_ALLOWED_MIMES', 'pdf,jpg,jpeg,png'),
    'max_size_mb'    => (int) env('DOC_MAX_SIZE_MB', 20),

    // Behavior
    'auto_dedupe'    => true,

    // UI
    'inbox_page_size'=> 20,

    // Webhook verification toggles (implement later if needed)
    'verify_twilio_signature' => false,
    'verify_email_signature'  => false,
];
