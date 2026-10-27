<?php
declare(strict_types=1);

use App\Core\Application;
use App\Core\Env;

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

Env::load(BASE_PATH . '/.env');

$config = require BASE_PATH . '/config/app.php';
if (is_file(BASE_PATH . '/config/app.local.php')) {
    $local = require BASE_PATH . '/config/app.local.php';
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}
date_default_timezone_set($config['timezone']);
session_name($config['session_name']);
$secureCookie = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
$sessionPath = BASE_PATH . '/storage/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0775, true);
}
session_save_path($sessionPath);

// Shared sessions make the app horizontally scalable. When the database is
// reachable and has the sessions table, sessions live in MySQL; otherwise the
// file handler is used so the installer and single-server deployments keep
// working.
$started = false;
if (($config['session_driver'] ?? 'db') === 'db') {
    try {
        session_set_save_handler(new \App\Core\DatabaseSessionHandler(), true);
        session_start();
        $started = true;
    } catch (\Throwable $error) {
        error_log('Database sessions unavailable, falling back to file sessions: ' . $error->getMessage());
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}
if (!$started) {
    session_set_save_handler(new SessionHandler(), true);
    session_start();
}

(new Application($config))->run();
