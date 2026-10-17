<?php

return [
    // Secret that lets an outside scheduler trigger the reminders (see "How the reminder gets triggered").
    'cron_token' => env('CRON_TOKEN'),
];