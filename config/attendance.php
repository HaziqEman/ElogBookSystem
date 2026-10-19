<?php

return [
    // Largest MC certificate accepted, in kilobytes. Your server's PHP upload limit must be at least this.
    'mc_max_kb' => (int) env('MC_MAX_KB', 2048),
];