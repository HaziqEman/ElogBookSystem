<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::create('/admin/students', 'GET');
$response = $kernel->handle($request);
echo $response->getStatusCode(), PHP_EOL;
echo $response->getContent();
