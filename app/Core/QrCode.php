<?php
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/** Small dependency-free QR encoder for student identifiers (QR versions 1–4, error level L). */
final class QrCode
{
    private const DATA_CODEWORDS = [1 => 19, 2 => 34, 3 => 55, 4 => 80];
    private const ECC_CODEWORDS = [1 => 7, 2 => 10, 3 => 15, 4 => 20];
    private array $matrix = [];
    private array $reserved = [];
    private int $size = 0;

    public function toDataUri(string $value): string
    {
        if ($value === '' || strlen($value) > 78) {
            throw new InvalidArgumentException('QR value must contain between 1 and 78 bytes.');
        }

        $version = 1;
        foreach (self::DATA_CODEWORDS as $candidate => $capacity) {
            if (12 + (strlen($value) * 8) <= $capacity * 8) {
                $version = $candidate;
                break;
            }
        }
        $this->size = 17 + 4 * $version;
        $this->matrix = array_fill(0, $this->size, array_fill(0, $this->size, false));
        $this->reserved = array_fill(0, $this->size, array_fill(0, $this->size, false));

        $this->drawFinder(0, 0);
        $this->drawFinder($this->size - 7, 0);
        $this->drawFinder(0, $this->size - 7);
        for ($i = 8; $i < $this->size - 8; $i++) {
            $this->setFunction(6, $i, $i % 2 === 0);
            $this->setFunction($i, 6, $i % 2 === 0);
        }
        if ($version >= 2) {
            $center = 4 * $version + 10;
            $this->drawAlignment($center, $center);
        }
        $this->reserveFormatAreas();
        $this->setFunction(8, $this->size - 8, true);

        $data = $this->makeCodewords($value, $version);
        $this->placeData($data);
        $this->writeFormatBits();

        $canvasSize = $this->size + 8; // QR scanners require a four-module quiet zone.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $canvasSize . ' ' . $canvasSize . '" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><path fill="#052e32" d="';
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                if ($this->matrix[$y][$x]) {
                    $svg .= 'M' . ($x + 4) . ' ' . ($y + 4) . 'h1v1h-1z';
                }
            }
        }
        $svg .= '"/></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function makeCodewords(string $value, int $version): array
    {
        $bits = [0, 1, 0, 0]; // byte mode
        $length = strlen($value);
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = ($length >> $i) & 1;
        }
        for ($i = 0; $i < $length; $i++) {
            $byte = ord($value[$i]);
            for ($bit = 7; $bit >= 0; $bit--) {
                $bits[] = ($byte >> $bit) & 1;
            }
        }

        $capacity = self::DATA_CODEWORDS[$version] * 8;
        for ($i = 0, $count = min(4, $capacity - count($bits)); $i < $count; $i++) {
            $bits[] = 0;
        }
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }
        $data = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | $bits[$i + $j];
            }
            $data[] = $byte;
        }
        for ($pad = 0xEC; count($data) < self::DATA_CODEWORDS[$version]; $pad ^= 0xEC ^ 0x11) {
            $data[] = $pad;
        }

        return array_merge($data, $this->reedSolomonRemainder($data, self::ECC_CODEWORDS[$version]));
    }

    private function reedSolomonRemainder(array $data, int $degree): array
    {
        $generator = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($generator) + 1, 0);
            $alpha = $this->alphaPower($i);
            foreach ($generator as $j => $coefficient) {
                $next[$j] ^= $coefficient;
                $next[$j + 1] ^= $this->gfMultiply($coefficient, $alpha);
            }
            $generator = $next;
        }

        $remainder = array_fill(0, $degree, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($remainder);
            $remainder[] = 0;
            for ($i = 0; $i < $degree; $i++) {
                $remainder[$i] ^= $this->gfMultiply($generator[$i + 1], $factor);
            }
        }
        return $remainder;
    }

    private function alphaPower(int $power): int
    {
        $value = 1;
        for ($i = 0; $i < $power; $i++) {
            $value <<= 1;
            if (($value & 0x100) !== 0) {
                $value ^= 0x11D;
            }
        }
        return $value;
    }

    private function gfMultiply(int $x, int $y): int
    {
        $result = 0;
        while ($y !== 0) {
            if (($y & 1) !== 0) {
                $result ^= $x;
            }
            $x <<= 1;
            if (($x & 0x100) !== 0) {
                $x ^= 0x11D;
            }
            $y >>= 1;
        }
        return $result;
    }

    private function drawFinder(int $left, int $top): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $x = $left + $dx;
                $y = $top + $dy;
                if ($x < 0 || $y < 0 || $x >= $this->size || $y >= $this->size) {
                    continue;
                }
                $distance = max(abs($dx - 3), abs($dy - 3));
                $this->setFunction($x, $y, $distance !== 2 && $distance <= 3);
            }
        }
    }

    private function drawAlignment(int $centerX, int $centerY): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $distance = max(abs($dx), abs($dy));
                $this->setFunction($centerX + $dx, $centerY + $dy, $distance !== 1);
            }
        }
    }

    private function reserveFormatAreas(): void
    {
        for ($i = 0; $i <= 5; $i++) $this->setFunction(8, $i, false);
        $this->setFunction(8, 7, false);
        $this->setFunction(8, 8, false);
        $this->setFunction(7, 8, false);
        for ($i = 9; $i < 15; $i++) $this->setFunction(14 - $i, 8, false);
        for ($i = 0; $i < 8; $i++) $this->setFunction($this->size - 1 - $i, 8, false);
        for ($i = 8; $i < 15; $i++) $this->setFunction(8, $this->size - 15 + $i, false);
    }

    private function writeFormatBits(): void
    {
        $data = 0b01000; // error correction level L, mask pattern 0
        $remainder = $data;
        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) * 0x537);
        }
        $bits = (($data << 10) | $remainder) ^ 0x5412;
        for ($i = 0; $i <= 5; $i++) $this->matrix[$i][8] = (($bits >> $i) & 1) !== 0;
        $this->matrix[7][8] = (($bits >> 6) & 1) !== 0;
        $this->matrix[8][8] = (($bits >> 7) & 1) !== 0;
        $this->matrix[8][7] = (($bits >> 8) & 1) !== 0;
        for ($i = 9; $i < 15; $i++) $this->matrix[8][14 - $i] = (($bits >> $i) & 1) !== 0;
        for ($i = 0; $i < 8; $i++) $this->matrix[8][$this->size - 1 - $i] = (($bits >> $i) & 1) !== 0;
        for ($i = 8; $i < 15; $i++) $this->matrix[$this->size - 15 + $i][8] = (($bits >> $i) & 1) !== 0;
        $this->matrix[$this->size - 8][8] = true;
    }

    private function placeData(array $codewords): void
    {
        $bits = [];
        foreach ($codewords as $byte) {
            for ($i = 7; $i >= 0; $i--) $bits[] = ($byte >> $i) & 1;
        }
        $bitIndex = 0;
        for ($right = $this->size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) $right = 5;
            for ($vertical = 0; $vertical < $this->size; $vertical++) {
                $upward = (($right + 1) & 2) === 0;
                $y = $upward ? $this->size - 1 - $vertical : $vertical;
                for ($offset = 0; $offset < 2; $offset++) {
                    $x = $right - $offset;
                    if ($this->reserved[$y][$x]) continue;
                    $value = $bitIndex < count($bits) ? $bits[$bitIndex++] === 1 : false;
                    if (($x + $y) % 2 === 0) $value = !$value;
                    $this->matrix[$y][$x] = $value;
                }
            }
        }
    }

    private function setFunction(int $x, int $y, bool $dark): void
    {
        if ($x < 0 || $y < 0 || $x >= $this->size || $y >= $this->size) return;
        $this->matrix[$y][$x] = $dark;
        $this->reserved[$y][$x] = true;
    }
}
