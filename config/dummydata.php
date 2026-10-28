<?php

return [
    // Set DUMMY_DATA_SEEDING_ENABLED=true only in a development/test environment.
    'enabled' => (bool) env('DUMMY_DATA_SEEDING_ENABLED', false),
    'password' => env('DUMMY_DATA_PASSWORD', 'DummyPass2026!'),
    'counts' => [
        'admins' => 1,
        'lecturers' => 2,
        'supervisors' => 2,
        'students' => 8,
        'weeks' => 12,
    ],
];