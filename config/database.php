<?php
declare(strict_types=1);

/**
 * Database configuration (PDO + MySQL).
 *
 * EASY WAY (XAMPP): the defaults below already work with a fresh XAMPP install.
 * If you changed your MySQL password, either edit the defaults below
 * OR copy ".env.example" to ".env" and change the values there.
 */

function load_env_file(string $path): array
{
    $values = [];
    if (!is_readable($path)) {
        return $values;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $val] = explode('=', $line, 2);
        $values[trim($key)] = trim(trim($val), "\"'");
    }
    return $values;
}

$env = load_env_file(dirname(__DIR__) . '/.env');

define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? 'visitor_photo_slideshow');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('APP_DEBUG', ($env['APP_DEBUG'] ?? 'false') === 'true');

/** Returns one shared PDO connection (created on first use). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
