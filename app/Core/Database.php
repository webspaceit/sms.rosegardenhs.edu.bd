<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = null;

        $envPath = defined('BASE_PATH') ? BASE_PATH . '/.env' : (__DIR__ . '/../../.env');
        if (is_file($envPath)) {
            Env::load($envPath);
        }

        $host = Env::get('DB_HOST') ?? Env::get('DATABASE_HOST');
        $database = Env::get('DB_DATABASE') ?? Env::get('DB_NAME') ?? Env::get('DATABASE_NAME');
        $username = Env::get('DB_USERNAME') ?? Env::get('DB_USER') ?? Env::get('DATABASE_USERNAME') ?? Env::get('DATABASE_USER');
        $password = Env::get('DB_PASSWORD') ?? Env::get('DATABASE_PASSWORD');
        $charset = Env::get('DB_CHARSET') ?? Env::get('DATABASE_CHARSET') ?? 'utf8mb4';

        if ($host && $database && $username !== null) {
            $config = [
                'host' => $host,
                'database' => $database,
                'username' => $username,
                'password' => $password ?? '',
                'charset' => $charset,
            ];
        } else {
            $path = defined('BASE_PATH') ? BASE_PATH . '/config/database.php' : (__DIR__ . '/../../config/database.php');
            if (is_file($path)) {
                $loaded = require $path;
                if (is_array($loaded)) {
                    $config = $loaded;
                }
            }
        }

        if (!is_array($config)) {
            throw new RuntimeException('Database is not configured. Create a .env file or copy config/database.example.php to config/database.php.');
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'] ?? 'localhost', $config['database'] ?? '', $config['charset'] ?? 'utf8mb4');
        self::$connection = new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return self::$connection;
    }
}
