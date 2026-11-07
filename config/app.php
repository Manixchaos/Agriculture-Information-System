<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Manila'));

if (env('APP_DEBUG', 'false') === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }
}

function can_delete(): bool
{
    return (current_user()['role'] ?? '') === 'Administrator';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back, refresh, and try again.');
    }
}

function validate_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

/** Queue a flash message: flash('success', 'Saved.') */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Read and clear flash messages (call this in includes/header.php). */
function pull_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Compatibility helper used by includes/header.php: returns one message or null. */
function take_flash(): ?array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all ? $all[0] : null;   // ['type' => 'success', 'message' => '...']
}