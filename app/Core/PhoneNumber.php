<?php
declare(strict_types=1);

namespace App\Core;

/** Shared Bangladesh mobile number format and validation. */
final class PhoneNumber
{
    public const ERROR = 'Enter a valid Bangladeshi mobile number (01XXXXXXXXX or +8801XXXXXXXXX).';

    public static function isBangladeshiMobile(string $value): bool
    {
        return preg_match('/^(?:\\+?88)?01[3-9][0-9]{8}$/D', $value) === 1;
    }
}
