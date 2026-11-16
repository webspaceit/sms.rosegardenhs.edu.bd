<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function method(): string { return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); }
    public function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = Url::basePath();
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        // In some WAMP/Apache rewrite configurations SCRIPT_NAME resolves to
        // the project directory instead of its public/ directory. In that
        // case Url::basePath() strips the project prefix but leaves /public
        // on the route, so the router never matches its application paths.
        // The public folder is a front-controller directory, not a route
        // prefix; strip it when it remains at the start of the path.
        if ($path === '/public' || str_starts_with($path, '/public/')) {
            $path = substr($path, strlen('/public')) ?: '/';
        }

        return '/' . trim($path, '/');
    }
    public function input(string $key, mixed $default = null): mixed { return $_POST[$key] ?? $_GET[$key] ?? $default; }
}
