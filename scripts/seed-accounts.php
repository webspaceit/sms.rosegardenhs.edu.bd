<?php
declare(strict_types=1);

/**
 * Create the accounts that make the portals reachable.
 *
 * A teacher, a student and a parent account are linked to their person
 * record, and a guardian is linked to a student so that the parent portal
 * has somebody to look after.
 *
 * Usage: php scripts/seed-accounts.php
 */

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

$config = require BASE_PATH . '/config/database.php';
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $config['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$password = 'password';

/**
 * Create an account, or report the one that is already there.
 */
function account(PDO $pdo, string $name, string $email, string $role, ?string $linkType, ?int $linkId, string $password): void
{
    $find = $pdo->prepare('SELECT id, link_id FROM users WHERE email = :email LIMIT 1');
    $find->execute(['email' => $email]);
    $existing = $find->fetch();

    if ($existing !== false) {
        // Keep the link up to date even when the account is already there.
        $update = $pdo->prepare(
            'UPDATE users SET name = :name, role = :role, link_type = :link_type, link_id = :link_id, is_active = 1 WHERE id = :id'
        );
        $update->execute([
            'name' => $name,
            'role' => $role,
            'link_type' => $linkType,
            'link_id' => $linkId,
            'id' => (int) $existing['id'],
        ]);
        echo str_pad($email, 34), "already exists, link refreshed\n";
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (name, email, password, role, link_type, link_id, is_active, created_at)'
        . ' VALUES (:name, :email, :password, :role, :link_type, :link_id, 1, NOW())'
    );
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'link_type' => $linkType,
        'link_id' => $linkId,
    ]);
    echo str_pad($email, 34), "created\n";
}

// A guardian linked to the first student, so the parent portal has a child.
$student = $pdo->query('SELECT id, name FROM students ORDER BY id LIMIT 1')->fetch();
$guardian = $pdo->query('SELECT id, name FROM guardians ORDER BY id LIMIT 1')->fetch();

if ($student !== false && $guardian !== false) {
    $find = $pdo->prepare('SELECT id FROM student_guardian WHERE student_id = :student_id AND guardian_id = :guardian_id LIMIT 1');
    $find->execute(['student_id' => (int) $student['id'], 'guardian_id' => (int) $guardian['id']]);

    if ($find->fetchColumn() === false) {
        $insert = $pdo->prepare(
            'INSERT INTO student_guardian (student_id, guardian_id, is_primary, created_at) VALUES (:student_id, :guardian_id, 1, NOW())'
        );
        $insert->execute(['student_id' => (int) $student['id'], 'guardian_id' => (int) $guardian['id']]);
        echo str_pad('guardian link', 34), "created\n";
    } else {
        echo str_pad('guardian link', 34), "already exists\n";
    }
}

$teacher = $pdo->query('SELECT id, name FROM teachers ORDER BY id LIMIT 1')->fetch();

// A second administrator with a known password, for demonstrations.
account($pdo, 'School Administrator', 'admin@school.test', 'superadmin', null, null, $password);

if ($teacher !== false) {
    account($pdo, (string) $teacher['name'], 'teacher@school.test', 'teacher', 'teacher', (int) $teacher['id'], $password);
}

if ($student !== false) {
    account($pdo, (string) $student['name'], 'student@school.test', 'student', 'student', (int) $student['id'], $password);
}

if ($guardian !== false) {
    account($pdo, (string) $guardian['name'], 'parent@school.test', 'parent', 'guardian', (int) $guardian['id'], $password);
}

echo "\nAll three accounts use the password: ", $password, "\n";
