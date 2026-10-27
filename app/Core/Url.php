<?php
declare(strict_types=1);

namespace App\Core;

final class Url
{
    public static function basePath(): string
    {
        $config = require BASE_PATH . '/config/app.php';
        $configuredBase = trim((string) ($config['base_url'] ?? ''), '/');
        if ($configuredBase !== '') {
            return '/' . $configuredBase;
        }

        // Infer the install path when Apache serves this app from a subdirectory.
        $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
        return $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
    }

    public static function to(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        $base = self::basePath();
        return $base . ($path === '/' ? '/' : $path);
    }
}
