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
        // directory URL. Infer that prefix from the request so routes and
        // redirects keep working from /project/public/ as well.
        $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $publicPosition = strrpos(rtrim($requestPath, '/'), '/public');
        if ($publicPosition !== false) {
            $publicEnd = $publicPosition + strlen('/public');
            $suffix = substr($requestPath, $publicEnd, 1);
            if ($suffix === '' || $suffix === '/') {
                return substr($requestPath, 0, $publicEnd);
            }
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
