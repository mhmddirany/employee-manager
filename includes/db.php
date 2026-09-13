<?php
/**
 * Database bootstrap.
 *
 * Uses SQLite (a single file, zero configuration) so the project runs
 * immediately with nothing to install. Everything here is plain PDO —
 * swapping to MySQL later is a one-line DSN change (see README.md).
 */

define('DB_PATH', __DIR__ . '/../data/app.sqlite');

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $isNew = !file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        install_schema($pdo);
        seed_data($pdo);
    }

    return $pdo;
}

function install_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'viewer', -- admin | editor | viewer
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $pdo->exec("
        CREATE TABLE employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            department TEXT NOT NULL,
            position TEXT,
            hire_date TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'active', -- active | inactive
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT
        )
    ");

    $pdo->exec("
        CREATE TABLE activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            user_name TEXT,
            action TEXT NOT NULL,   -- login | create | update | delete
            entity TEXT NOT NULL,   -- employee | user
            entity_id INTEGER,
            details TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");
}

function seed_data(PDO $pdo): void
{
    $users = [
        ['Admin User', 'admin@example.com', 'admin123', 'admin'],
        ['Eve Editor', 'editor@example.com', 'editor123', 'editor'],
        ['Vic Viewer', 'viewer@example.com', 'viewer123', 'viewer'],
    ];
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    foreach ($users as $u) {
        $stmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3]]);
    }

    $departments = ['Engineering', 'Sales', 'Support', 'HR', 'Finance'];
    $positions = ['Specialist', 'Coordinator', 'Manager', 'Analyst', 'Associate'];
    $firstNames = ['Ali', 'Sara', 'Omar', 'Lina', 'Karim', 'Maya', 'Nabil', 'Rana', 'Fadi', 'Dina', 'Hassan', 'Yara'];
    $lastNames = ['Haddad', 'Khalil', 'Mansour', 'Saad', 'Youssef', 'Nasr', 'Fares', 'Habib', 'Aziz', 'Rahal'];

    $stmt = $pdo->prepare(
        'INSERT INTO employees (name, email, department, position, hire_date, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, datetime("now"))'
    );

    $seedCount = 34;
    for ($i = 0; $i < $seedCount; $i++) {
        $first = $firstNames[array_rand($firstNames)];
        $last = $lastNames[array_rand($lastNames)];
        $name = "$first $last";
        $email = strtolower($first . '.' . $last . $i . '@qualizone-demo.test');
        $dept = $departments[array_rand($departments)];
        $pos = $positions[array_rand($positions)];
        $daysAgo = rand(30, 1500);
        $hireDate = date('Y-m-d', strtotime("-$daysAgo days"));
        $status = (rand(1, 10) > 8) ? 'inactive' : 'active';
        $stmt->execute([$name, $email, $dept, $pos, $hireDate, $status]);
    }
}
