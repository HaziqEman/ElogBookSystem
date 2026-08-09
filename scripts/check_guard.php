<?php
require __DIR__ . '/../vendor/autoload.php';
 $app = require __DIR__ . '/../bootstrap/app.php';
 $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
 $kernel->bootstrap();
 $auth = $app->make('auth');
try {
    $g = $auth->guard('student');
    echo "guard resolved: ";
    var_export(get_class($g));
    echo "\n";
} catch (Exception $e) {
    echo 'EX: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}
echo "CONFIG GUARDS:\n";
var_export($app->make('config')->get('auth.guards'));
echo "\n";

echo "DIRECT REQUIRE OF CONFIG FILE:\n";
$cfg = require __DIR__ . '/../config/auth.php';
var_export($cfg['guards']);
echo "\n";
