<?php

return [
    // AI drafts each student may request per day. Change it in Render with AI_HELP_DAILY_LIMIT.
    'daily_limit' => (int) env('AI_HELP_DAILY_LIMIT', 10),
];