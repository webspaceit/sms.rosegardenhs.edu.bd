<?php
declare(strict_types=1);

/**
 * Seed the permission catalogue and the default role grants.
 *
 * Existing permissions are reused, so running this script again only fills
 * in what is missing.
 *
 * Usage: php scripts/seed-permissions.php
 */

use App\Models\Permission;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$count = Permission::seed();

echo 'Permissions seeded: ', $count, " catalogue entries.\n";
foreach (Permission::catalogue() as $module => $permissions) {
    echo '  ', str_pad($module, 14), count($permissions), " permission(s)\n";
}

echo "\nRole grants:\n";
$map = Permission::roleMap();
foreach (Permission::ROLES as $role => $label) {
    echo '  ', str_pad($label, 14), count($map[$role] ?? []), " permission(s)\n";
}
