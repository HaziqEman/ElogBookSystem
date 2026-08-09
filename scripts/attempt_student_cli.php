<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

$creds = ['email'=>'student@gmail.com','password'=>'password'];
$guard = Auth::guard('student');
$attempt = $guard->attempt($creds);
var_export(['attempt'=>$attempt, 'user'=> $guard->user() ? $guard->user()->student_id : null]);
