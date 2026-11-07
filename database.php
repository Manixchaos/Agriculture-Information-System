<?php
declare(strict_types=1);

/** Minimal .env loader (no Composer needed). */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

function env(string $key, ?string $default = null): ?string
{
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : (string)$v;
}

load_env(dirname(__DIR__) . '/.env');

/** Shared PDO connection to Supabase PostgreSQL. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!extension_loaded('pdo_pgsql')) {
        throw new RuntimeException('The pdo_pgsql PHP extension is not enabled. Enable it in php.ini and restart Apache.');
    }

    $host = env('DB_HOST');
    $port = env('DB_PORT', '5432');
    $name = env('DB_NAME', 'postgres');
    $user = env('DB_USER', 'postgres');
    $pass = env('DB_PASSWORD', '');
    $ssl  = env('DB_SSLMODE', 'require');

    if (!$host || $pass === '') {
        throw new RuntimeException('DB_HOST or DB_PASSWORD is missing in .env');
    }
    if (str_starts_with($pass, 'postgres://') || str_starts_with($pass, 'postgresql://')) {
        throw new RuntimeException('DB_PASSWORD must be only the password, not the full connection URL.');
    }

    $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode={$ssl}";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Supabase transaction pooler (port 6543) does not support server-side prepared statements.
        PDO::ATTR_EMULATE_PREPARES   => ($port === '6543'),
        PDO::ATTR_TIMEOUT            => 10,
    ]);

    return $pdo;
}
