<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

$creds = ['email'=>'aa@gmail.com','password'=>'123'];
$guard = Auth::guard('lecturer');
$attempt = $guard->attempt($creds);
var_export(['attempt'=>$attempt, 'user'=> $guard->user() ? $guard->user()->lecturer_id : null]);
