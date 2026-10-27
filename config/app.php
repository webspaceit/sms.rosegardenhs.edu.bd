<?php
declare(strict_types=1);

use App\Core\Env;

if (defined('BASE_PATH') && is_file(BASE_PATH . '/.env')) {
    Env::load(BASE_PATH . '/.env');
}

$name = Env::get('APP_NAME') ?? 'WSIT School Management System';
$baseUrl = Env::get('APP_BASE_URL') ?? Env::get('BASE_URL') ?? '';
$timezone = Env::get('APP_TIMEZONE') ?? Env::get('TIMEZONE') ?? 'Asia/Dhaka';
$sessionName = Env::get('SESSION_NAME') ?? 'wsit_school_session';
$sessionDriver = Env::get('SESSION_DRIVER') ?? 'db';
$cacheDriver = Env::get('CACHE_DRIVER') ?? 'file';

return [
    'name' => $name,
    'base_url' => $baseUrl, // Set this when hosted in a subdirectory, e.g. /school
    'timezone' => $timezone,
    'session_name' => $sessionName,
    'session_driver' => $sessionDriver, // "db" for shared sessions, "file" for local
    'cache_driver' => $cacheDriver, // "file" for the built-in file cache
];
