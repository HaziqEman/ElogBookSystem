<?php
header('X-Probe: 1');
setcookie('probe', '1', [
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
echo 'ok';
