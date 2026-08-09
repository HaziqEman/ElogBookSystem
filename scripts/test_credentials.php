<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

$tests = [
    ['guard'=>'lecturer','email'=>'lecturer@gmail.com','password'=>'secret'],
    ['guard'=>'lecturer','email'=>'aa@gmail.com','password'=>'123'],
    ['guard'=>'student','email'=>'student@example.com','password'=>'password'],
    ['guard'=>'admin','email'=>'admin@example.com','password'=>'adminpass'],
];

foreach($tests as $t){
    $g = Auth::guard($t['guard']);
    $valid = $g->validate(['email'=>$t['email'],'password'=>$t['password']]);
    echo "guard={$t['guard']} email={$t['email']} valid=".($valid? 'yes':'no')."\n";
}
