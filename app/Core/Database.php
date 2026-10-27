<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * The single PDO gateway.
 *
 * connection() is the write/primary connection and remains the default for
 * every query that mutates state. read() returns a read replica when one is
 * configured (via DB_READ_HOST or the "read" key in config/database.php) and
 * otherwise falls back to the primary, so a single-server install behaves
 * exactly as before.
 */
final class Database
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    private static ?PDO $connection = null;

    private static ?PDO $readConnection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        self::$connection = self::connect(self::config());
        return self::$connection;
    }

    /**
     * A read-only connection, routed to a replica when one is available.
     */
    public static function read(): PDO
    {
        if (self::$readConnection instanceof PDO) {
            return self::$readConnection;
        }

        $replicas = self::readConfigs();
        if ($replicas === []) {
            self::$readConnection = self::connection();
            return self::$readConnection;
        }

        self::$readConnection = self::connect($replicas[array_rand($replicas)]);
        return self::$readConnection;
    }

    /**
     * @return array<string, mixed>
     */
    private static function config(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $envPath = defined('BASE_PATH') ? BASE_PATH . '/.env' : (__DIR__ . '/../../.env');
        if (is_file($envPath)) {
            Env::load($envPath);
        }

        $host = Env::get('DB_HOST') ?? Env::get('DATABASE_HOST');
        $database = Env::get('DB_DATABASE') ?? Env::get('DB_NAME') ?? Env::get('DATABASE_NAME');
        $username = Env::get('DB_USERNAME') ?? Env::get('DB_USER') ?? Env::get('DATABASE_USERNAME') ?? Env::get('DATABASE_USER');
        $password = Env::get('DB_PASSWORD') ?? Env::get('DATABASE_PASSWORD');
        $charset = Env::get('DB_CHARSET') ?? Env::get('DATABASE_CHARSET') ?? 'utf8mb4';

        $config = null;
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

        self::$config = $config;
        return self::$config;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function connect(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'] ?? 'localhost',
            $config['database'] ?? '',
            $config['charset'] ?? 'utf8mb4'
        );

        return new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function readConfigs(): array
    {
        $base = self::config();

        $readHosts = Env::get('DB_READ_HOST');
        if (is_string($readHosts) && trim($readHosts) !== '') {
            $hosts = array_filter(array_map('trim', explode(',', $readHosts)));
            return array_map(static fn (string $host): array => $base + ['host' => $host], $hosts);
        }

        $read = $base['read'] ?? null;
        if (!is_array($read) || $read === []) {
            return [];
        }

        // A single replica object, e.g. ['host' => 'db-replica'].
        if (isset($read['host']) || isset($read['database'])) {
            return [array_replace($base, $read)];
        }

        return array_map(static fn (array $replica): array => array_replace($base, $replica), $read);
    }
}
