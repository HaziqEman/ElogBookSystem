<?php
$file = __DIR__ . '/../app/Http/Controllers/AuthController.php';
if (!function_exists('opcache_invalidate')) {
    echo "opcache not available\n";
    exit(0);
}
var_export(opcache_get_status(false));
if (opcache_invalidate($file, true)) {
    echo "invalidated: $file\n";
} else {
    echo "failed to invalidate: $file\n";
}
