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
        if ($scriptDirectory !== '/' && $scriptDirectory !== '.') {
            return rtrim($scriptDirectory, '/');
        }

        // Some Apache rewrite/alias configurations report SCRIPT_NAME as just
        // /index.php even though the application is opened through its public/
        // directory URL. Preserve the public/ prefix for routes and redirects.
        $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $normalizedRequestPath = rtrim($requestPath, '/');
        if (str_ends_with($normalizedRequestPath, '/public')) {
            return $normalizedRequestPath;
        }
        $publicPosition = strrpos($normalizedRequestPath, '/public/');
        if ($publicPosition !== false) {
            return substr($normalizedRequestPath, 0, $publicPosition + strlen('/public'));
        }
        return '';
    }

    public static function to(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        $base = self::basePath();
        return $base . ($path === '/' ? '/' : $path);
    }
}
