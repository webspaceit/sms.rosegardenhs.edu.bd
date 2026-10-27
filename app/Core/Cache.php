<?php
declare(strict_types=1);

namespace App\Core;

/**
 * A tiny cache with a file driver that works on shared hosting.
 *
 * Values are serialized to storage/cache with an expiry timestamp. The
 * storage directory must be on a shared volume when the app is load balanced;
 * on a single server it is just a local directory.
 */
final class Cache
{
    public static function get(string $key): mixed
    {
        $path = self::path($key);
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || (int) ($data['expires_at'] ?? 0) < time()) {
            @unlink($path);
            return null;
        }

        return $data['value'] ?? null;
    }

    public static function put(string $key, mixed $value, int $ttl = 60): bool
    {
        $directory = self::directory();
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return (bool) @file_put_contents(
            self::path($key),
            json_encode(['expires_at' => time() + max(1, $ttl), 'value' => $value], JSON_UNESCAPED_UNICODE)
        );
    }

    public static function remember(string $key, int $ttl, callable $resolver): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $resolver();
        self::put($key, $value, $ttl);
        return $value;
    }

    public static function forget(string $key): void
    {
        @unlink(self::path($key));
    }

    private static function directory(): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        return $base . '/storage/cache';
    }

    private static function path(string $key): string
    {
        return self::directory() . '/' . md5($key) . '.json';
    }
}
