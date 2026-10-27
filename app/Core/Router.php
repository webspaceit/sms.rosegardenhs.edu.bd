<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];
    public function get(string $path, callable $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void { $this->add('POST', $path, $handler); }
    private function add(string $method, string $path, callable $handler): void { $this->routes[$method][$path] = $handler; }
    public function dispatch(Request $request): void
    {
        $handler = $this->routes[$request->method()][$request->path()] ?? null;
        if ($handler === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Page not found']);
            return;
        }
        $handler($request);
    }
}
