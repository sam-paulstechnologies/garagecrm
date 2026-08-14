<?php

return [
    // If AI confidence is below this, don’t auto-drive the flow. Handoff to manager.
    'confidence_threshold' => (float) env('AI_CONFIDENCE_THRESHOLD', 0.60),

    // Keep this if you want to log/score propensity (already used in your job)
    'propensity_enabled' => (bool) env('AI_PROPENSITY_ENABLED', true),

    // Outbound AI replies are disabled unless an explicitly reviewed runtime
    // policy enables them. This is a safety control, not a tenant default.
    'first_reply' => (bool) env('AI_FIRST_REPLY', false),

    // Optional default safe text (used by UI/middleware fallbacks if needed)
    'default_reply' => "I'm not sure I understood that. Our manager will reach out shortly.",
];
