<?php
require __DIR__ . '/../vendor/autoload.php';

$class = 'App\\Http\\Controllers\\AuthController';
if (!class_exists($class)) {
    echo "CLASS_NOT_FOUND\n";
    exit(1);
}

$r = new ReflectionClass($class);
echo "FILE: " . $r->getFileName() . "\n";
echo "realpath of reflection file: " . realpath($r->getFileName()) . PHP_EOL;
echo "expected path realpath: " . realpath(__DIR__ . '/../app/Http/Controllers/AuthController.php') . PHP_EOL;
echo "Name: " . $r->getName() . PHP_EOL;
echo "Short: " . $r->getShortName() . PHP_EOL;
echo "Namespace: " . $r->getNamespaceName() . PHP_EOL;
echo "Parent: " . ($r->getParentClass() ? $r->getParentClass()->getName() : 'NONE') . PHP_EOL;
echo "IsUserDefined: " . ($r->isUserDefined() ? 'yes' : 'no') . PHP_EOL;
echo "IsAbstract: " . ($r->isAbstract() ? 'yes' : 'no') . PHP_EOL;
echo "IsFinal: " . ($r->isFinal() ? 'yes' : 'no') . PHP_EOL;
echo "File: " . $r->getFileName() . "\n";
$methods = [];
echo "METHOD COUNT: " . count($r->getMethods()) . "\n";
foreach ($r->getMethods() as $m) {
    echo $m->name . ' -> ' . $m->getDeclaringClass()->getName() . "\n";
    if ($m->getDeclaringClass()->getName() === $class) {
        $methods[] = $m->name;
    }
}
if (empty($methods)) {
    echo "NO_METHODS\n";
} else {
    echo implode("\n", $methods) . "\n";
}

echo "method_exists login: " . (method_exists($class, 'login') ? 'yes' : 'no') . PHP_EOL;
echo "method_exists logout: " . (method_exists($class, 'logout') ? 'yes' : 'no') . PHP_EOL;

$src = file_get_contents(__DIR__ . '/../app/Http/Controllers/AuthController.php');
echo "--- FILE START ---\n";
echo $src . "\n";
echo "--- FILE END ---\n";
