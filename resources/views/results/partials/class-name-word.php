<?php
$formatClassName = static function (mixed $className): string {
    $className = trim((string) $className);
    if (preg_match('/^Class\s+(\d+)$/i', $className, $matches) !== 1) {
        return $className;
    }

    $numberWords = [
        1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
    ];

    return $numberWords[(int) $matches[1]] ?? $className;
};
