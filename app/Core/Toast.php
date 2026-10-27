<?php
declare(strict_types=1);

namespace App\Core;

final class Toast
{
    public static function flash(string $message, string $type = 'success'): void
    {
        $_SESSION['_toast'] = ['message' => $message, 'type' => $type];
    }
    public static function pull(): ?array
    {
        $toast = $_SESSION['_toast'] ?? null;
        unset($_SESSION['_toast']);
        return $toast;
    }
}
