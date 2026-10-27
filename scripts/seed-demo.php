<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$config = require BASE_PATH . '/config/app.php';
date_default_timezone_set($config['timezone']);

try {
    $result = (new App\Database\DemoSeeder())->seed();
    echo "Demo data created.\n";
    echo "Email: {$result['email']}\n";
    echo "Temporary password: {$result['password']}\n";
    echo "Students: {$result['students']} | Classes: {$result['classes']} | Academic year: {$result['year']}\n";
    echo "Save the password now; it is generated once and is not stored in the project files.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Demo seeding failed: {$error->getMessage()}\n");
    exit(1);
}
