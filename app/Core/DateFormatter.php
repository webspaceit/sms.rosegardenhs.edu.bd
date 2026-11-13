<?php
declare(strict_types=1);

namespace App\Core;

/** Formats dates using the preference saved for the current school branch. */
final class DateFormatter
{
    private static ?string $cachedFormat = null;

    public static function format(mixed $value, bool $includeTime = false): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        try {
            $date = new \DateTimeImmutable((string) $value);
        } catch (\Throwable) {
            return (string) $value;
        }

        $format = self::formatPreference();
        if ($includeTime) {
            $format .= ' H:i';
        }
        return $date->format($format);
    }

    private static function formatPreference(): string
    {
        if (self::$cachedFormat !== null) {
            return self::$cachedFormat;
        }

        $allowed = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'M j, Y'];
        $statement = Database::connection()->prepare(
            'SELECT value FROM school_settings WHERE `key` = :key AND branch_id = :branch ORDER BY id DESC LIMIT 1'
        );
        $statement->execute(['key' => 'date_format', 'branch' => BranchContext::id()]);
        $value = $statement->fetchColumn();
        $format = is_string($value) && in_array($value, $allowed, true) ? $value : 'd-m-Y';
        self::$cachedFormat = $format;
        return $format;
    }
}
