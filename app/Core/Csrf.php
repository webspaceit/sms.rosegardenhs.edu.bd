<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
    public static function field(): string { return '<input type="hidden" name="_token" value="' . View::escape(self::token()) . '">'; }
    public static function verify(Request $request): bool
    {
        return is_string($request->input('_token')) && hash_equals(self::token(), $request->input('_token'));
    }
}
