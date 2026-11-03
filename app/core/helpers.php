<?php
// Small functions the views use.

/** Escape for HTML. Use it on everything you print. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return rtrim($dir, '/');
}

/** Link to a page: url('/passenger/reload'). */
function url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    if (config('app.pretty_urls')) {
        return base_path() . $path;
    }
    [$p, $q] = array_pad(explode('?', $path, 2), 2, '');
    return base_path() . '/index.php?url=' . ltrim($p, '/') . ($q !== '' ? '&' . $q : '');
}

function asset(string $path): string
{
    return base_path() . '/assets/' . ltrim($path, '/');
}

/** The path being requested, without the folder XAMPP serves it from. */
function current_path(): string
{
    if (isset($_GET['url'])) {
        return '/' . trim((string) $_GET['url'], '/');
    }
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    if (str_starts_with($uri, '/index.php')) {
        $uri = substr($uri, 10);
    }
    return '/' . trim($uri, '/');
}

/** True when the current page is $path (or under it, when $prefix is true). */
function is_active(string $path, bool $prefix = false): bool
{
    $cur = current_path();
    return $prefix ? ($cur === $path || str_starts_with($cur, rtrim($path, '/') . '/')) : $cur === $path;
}

function icon(string $name): string
{
    static $icons = null;
    $icons ??= require APP_PATH . '/config/icons.php';
    return $icons[$name] ?? '';
}

function peso($amount, int $decimals = 2): string
{
    return '₱' . number_format((float) $amount, $decimals);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Auth::csrfToken()) . '">';
}

function flash(string $type, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$type] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $msg;
}

/** Keep what the user typed after a failed form. */
function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

function remember_input(array $fields): void
{
    $_SESSION['old'] = array_intersect_key($_POST, array_flip($fields));
}

function forget_input(): void
{
    unset($_SESSION['old']);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '?', 0, 1);
    $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

function first_name(string $name): string
{
    return explode(' ', trim($name))[0] ?: $name;
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return 'never';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return floor($diff / 86400) . ' days ago';
}

function fmt_date(?string $dt, string $format = 'M j, Y'): string
{
    return $dt ? date($format, strtotime($dt)) : '—';
}

function fmt_time(?string $dt): string
{
    return $dt ? date('g:i A', strtotime($dt)) : '—';
}

/** Status pill for fares, units and accounts. */
function pill(string $status): string
{
    $map = [
        'paid'            => ['green', 'Paid'],
        'blocked_full'    => ['amber', 'Blocked · full'],
        'blocked_balance' => ['red', 'Blocked · low balance'],
        'blocked_frozen'  => ['red', 'Blocked · QR frozen'],
        'active'          => ['green', 'Active'],
        'breakdown'       => ['red', 'Breakdown'],
        'offline'         => ['gray', 'Offline'],
        'inactive'        => ['gray', 'Deactivated'],
        'frozen'          => ['red', 'Frozen'],
        'on_trip'         => ['green', 'On a trip'],
        'parked'          => ['brand', 'Parked'],
        'reload'          => ['brand', 'Reload'],
    ];
    [$tone, $label] = $map[$status] ?? ['gray', ucfirst($status)];
    return '<span class="pill pill-' . $tone . '">' . e($label) . '</span>';
}
