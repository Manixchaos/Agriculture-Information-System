<?php
declare(strict_types=1);

function load_env(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        $_ENV[trim($key)] = $value;
    }
}
load_env(dirname(__DIR__) . '/.env');
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Manila');

function envv(string $key, ?string $default = null): ?string {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = envv('DB_HOST');
    $port = envv('DB_PORT', '5432');
    $name = envv('DB_NAME', 'postgres');
    $user = envv('DB_USER');
    $pass = envv('DB_PASSWORD');
    if (!$host || !$user || !$pass) {
        throw new RuntimeException('Database configuration is incomplete. Check your .env file.');
    }
    $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode=" . envv('DB_SSLMODE', 'require');
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
