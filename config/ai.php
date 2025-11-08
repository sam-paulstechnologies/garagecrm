<?php

return [
    // If AI confidence is below this, don’t auto-drive the flow. Handoff to manager.
    'confidence_threshold' => (float) env('AI_CONFIDENCE_THRESHOLD', 0.60),

    // Keep this if you want to log/score propensity (already used in your job)
    'propensity_enabled'   => (bool) env('AI_PROPENSITY_ENABLED', true),

    // Optional default safe text (used by UI/middleware fallbacks if needed)
    'default_reply'        => "I'm not sure I understood that. Our manager will reach out shortly.",
];
