<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$email = $argv[1] ?? 'student@gmail.com';
$pw = $argv[2] ?? 'password';

$affected = DB::table('students')->where('email',$email)->update(['password'=>Hash::make($pw)]);
if($affected) echo "Updated password for $email\n"; else echo "No student found with email $email\n";
