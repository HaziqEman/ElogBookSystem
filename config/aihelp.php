<?php

return [
    // AI drafts each student may request per day. Change it in Render with AI_HELP_DAILY_LIMIT.
    'daily_limit' => (int) env('AI_HELP_DAILY_LIMIT', 10),

    // Tried when the main Gemini model is out of quota or down. Model names change over time:
    // check the current names in Google AI Studio.
    'fallback_model' => env('GEMINI_FALLBACK_MODEL', 'gemini-2.5-flash-lite'),
];