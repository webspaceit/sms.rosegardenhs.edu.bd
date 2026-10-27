<?php
declare(strict_types=1);

/** Create a streamed SQL backup without mysqldump or Composer dependencies. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
$configPath = BASE_PATH . '/config/database.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Database is not configured.\n");
    exit(1);
}
$config = require $configPath;
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $config['charset'] ?? 'utf8mb4'),
        $config['username'], $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        ]
    );
} catch (Throwable $error) {
    fwrite(STDERR, 'Could not connect to database: ' . $error->getMessage() . "\n");
    exit(1);
}

$directory = BASE_PATH . '/storage/backups';
if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
    fwrite(STDERR, "Could not create private backup directory.\n");
    exit(1);
}
$path = $directory . '/school-' . date('Ymd-His') . '.sql';
$output = fopen($path, 'xb');
if ($output === false) {
    fwrite(STDERR, "Could not create backup file.\n");
    exit(1);
}
@chmod($path, 0600);

fwrite($output, "-- WSIT School Management System database backup\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
$schema = file_get_contents(BASE_PATH . '/database/schema.sql');
if ($schema === false) {
    fclose($output);
    @unlink($path);
    fwrite(STDERR, "Could not read database schema.\n");
    exit(1);
}
fwrite($output, "-- Schema definitions (existing tables are preserved)\n" . $schema . "\n\n");
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $table)) continue;
    $statement = $pdo->query('SELECT * FROM `' . $table . '`');
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $columns = array_map(static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', array_keys($row));
        $values = array_map(static fn (mixed $value): string => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($row));
        fwrite($output, 'INSERT INTO `' . $table . '` (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n");
    }
    $statement->closeCursor();
}
fwrite($output, "\nSET FOREIGN_KEY_CHECKS=1;\n");
fclose($output);
fwrite(STDOUT, 'Backup created: ' . $path . PHP_EOL);
