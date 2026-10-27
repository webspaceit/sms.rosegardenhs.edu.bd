<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Keeps a copy of failures on disk.
 *
 * The application renders a friendly error page and leaves the real cause to
 * error_log, which is discarded on plenty of shared hosts. Writing the reason
 * to storage/logs keeps it where it can actually be found.
 */
final class ErrorLog
{
    public static function write(\Throwable $error): void
    {
        $directory = BASE_PATH . '/storage/logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        @file_put_contents(
            $directory . '/app-error.log',
            '[' . date('Y-m-d H:i:s') . '] ' . get_class($error) . ': ' . $error->getMessage()
                . ' in ' . $error->getFile() . ':' . $error->getLine() . PHP_EOL,
            FILE_APPEND
        );
    }
}
