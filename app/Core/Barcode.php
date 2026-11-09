<?php
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/** Dependency-free Code 39 barcode renderer for printable school identifiers. */
final class Barcode
{
    private const PATTERNS = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
        '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
    ];

    public function toDataUri(string $value): string
    {
        $value = strtoupper(trim($value));
        $supported = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%';
        if ($value === '' || strlen($value) > 64 || strspn($value, $supported) !== strlen($value)) {
            throw new InvalidArgumentException('Barcode value contains unsupported characters or is too long.');
        }

        $encoded = '*' . $value . '*';
        $unit = 2;
        $quiet = 10 * $unit;
        $height = 60;
        $width = $quiet * 2;
        foreach (str_split($encoded) as $character) {
            foreach (str_split(self::PATTERNS[$character]) as $module) {
                $width += ($module === 'w' ? 3 : 1) * $unit;
            }
            $width += $unit;
        }

        $x = $quiet;
        $bars = '';
        foreach (str_split($encoded) as $character) {
            foreach (str_split(self::PATTERNS[$character]) as $index => $module) {
                $moduleWidth = ($module === 'w' ? 3 : 1) * $unit;
                if ($index % 2 === 0) {
                    $bars .= '<rect x="' . $x . '" y="0" width="' . $moduleWidth . '" height="' . $height . '"/>';
                }
                $x += $moduleWidth;
            }
            $x += $unit;
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $height . '"><rect width="100%" height="100%" fill="#fff"/><g fill="#111827">' . $bars . '</g></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
