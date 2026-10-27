<?php
declare(strict_types=1);

$publicRoot = realpath(__DIR__);
$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestedFile = realpath($publicRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $requestedPath), DIRECTORY_SEPARATOR));

if (
    $requestedFile !== false
    && str_starts_with($requestedFile, $publicRoot . DIRECTORY_SEPARATOR)
    && is_file($requestedFile)
    && !str_ends_with(strtolower($requestedFile), '.php')
) {
    return false;
}

require $publicRoot . '/index.php';
