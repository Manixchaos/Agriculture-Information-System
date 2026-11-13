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

/** Map a planting status name to its display tone class. */
function status_tone(string $name): string
{
    $n = strtolower($name);
    return match (true) {
        str_contains($n, 'cancel') => 'tone-cancelled',
        str_contains($n, 'harvest') => 'tone-harvested',
        str_contains($n, 'grow') => 'tone-growing',
        str_contains($n, 'plan') => 'tone-planned',
        default => 'tone-neutral',
    };
}

/** Human-readable date for display; returns an em dash for empty values. */
function nice_date(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '—';
    }
    $ts = strtotime($value);
    return $ts !== false ? date('j M Y', $ts) : $value;
}

/** Initials for a person's display name. */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        if (mb_strlen($letters) >= 2) {
            break;
        }
    }
    return $letters !== '' ? $letters : '·';
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

/** Used by includes/header.php: returns one flash message or null. */
function take_flash(): ?array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all ? $all[0] : null;
}

/** Used by includes/header.php: reads an environment value. */
function envv(string $key, ?string $default = null): ?string
{
    return env($key, $default);
}