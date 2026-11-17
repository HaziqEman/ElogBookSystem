<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$rows = DB::table('lecturers')->limit(10)->get();
foreach ($rows as $r) {
    echo json_encode($r).PHP_EOL;
}
