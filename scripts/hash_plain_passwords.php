<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$rows = DB::table('lecturers')->get();
foreach($rows as $r){
    $pw = $r->password;
    if (!preg_match('/^\$2[ayb]\$|^\$argon/', $pw)) {
        echo "Hashing lecturer id={$r->lecturer_id} email={$r->email} (was: {$pw})\n";
        DB::table('lecturers')->where('lecturer_id',$r->lecturer_id)->update(['password'=>Hash::make($pw)]);
    } else {
        echo "Already hashed lecturer id={$r->lecturer_id}\n";
    }
}
