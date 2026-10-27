<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewFile = BASE_PATH . '/resources/views/' . $view . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        // The page is rendered in its own scope. The layout and its partials
        // use variable names such as $groups and $item, and without this
        // isolation a page that happens to use the same name would silently
        // receive the layout's value instead of its own.
        ob_start();
        (static function (string $viewFile, array $data): void {
            extract($data, EXTR_SKIP);
            require $viewFile;
        })($viewFile, $data);
        $content = (string) ob_get_clean();

        // The layout needs the page title and the signed in user.
        extract($data, EXTR_SKIP);

        $layout = ($data['layout'] ?? 'admin') === 'portal'
            ? BASE_PATH . '/resources/views/portal-layout.php'
            : BASE_PATH . '/resources/views/layout.php';
        require $layout;
    }
    public static function escape(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
}
