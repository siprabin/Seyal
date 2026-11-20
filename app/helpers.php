<?php
/** General helpers: escaping, URLs, requests, flash messages, CSRF, dates, rendering. */
declare(strict_types=1);

function config(string $key, $default = null)
{
    return $GLOBALS['APP_CONFIG'][$key] ?? $default;
}

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL path of the app folder, e.g. "" or "/pm". */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim($dir, '/');
    }
    return $base;
}

/** Absolute base URL (used in emails). Falls back to the URL saved at install time. */
function base_url(): string
{
    if (!empty($_SERVER['HTTP_HOST'])) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        return ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . base_path();
    }
    return rtrim((string)config('base_url', ''), '/');
}

function url(string $page = 'dashboard', array $params = []): string
{
    $params = array_merge(['p' => $page], $params);
    return base_path() . '/index.php?' . http_build_query($params);
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 0;
    return base_path() . '/assets/' . $path . '?v=' . $v;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function back(string $fallback = ''): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && $host && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)) {
        redirect($ref);
    }
    redirect($fallback ?: url());
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = null)
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key): ?int
{
    $v = input($key);
    return ($v === null || $v === '') ? null : (int)$v;
}

function input_date(string $key): ?string
{
    $v = (string)input($key, '');
    if ($v === '') {
        return null;
    }
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : null;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $message];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Keep submitted form values for re-display after a validation redirect. */
function remember_old(): void
{
    $data = $_POST;
    unset($data['_csrf'], $data['password'], $data['password_confirm'], $data['current_password']);
    $_SESSION['_old'] = $data;
}

function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        if (is_ajax()) {
            json_out(['ok' => false, 'error' => t('Your session expired. Please reload the page.')], 419);
        }
        flash('danger', t('Your session expired. Please try again.'));
        back();
    }
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- Errors ---------- */

function abort(int $code = 403, ?string $message = null): never
{
    http_response_code($code);
    if (is_ajax()) {
        json_out(['ok' => false, 'error' => $message ?? t('Not allowed')], $code);
    }
    $title = match ($code) {
        404 => t('Not found'),
        503 => t('Update in progress'),
        default => t('Access denied'),
    };
    $message = $message ?? ($code === 404
        ? t('The page or record you asked for does not exist.')
        : t('You do not have permission to do this.'));
    render('errors/error', compact('title', 'message', 'code'), user() ? 'layout' : 'auth_layout');
    exit;
}

/* ---------- Dates ---------- */

function fmt_date(?string $date): string
{
    if (!$date || str_starts_with($date, '0000')) {
        return '';
    }
    return date(setting('date_format', 'd-m-Y'), strtotime($date));
}

function fmt_datetime(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    return date(setting('date_format', 'd-m-Y') . ' h:i A', strtotime($dt));
}

function time_ago(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $diff = time() - strtotime($dt);
    if ($diff < 60) {
        return t('just now');
    }
    if ($diff < 3600) {
        return t(':n min ago', ['n' => (int)floor($diff / 60)]);
    }
    if ($diff < 86400) {
        return t(':n hr ago', ['n' => (int)floor($diff / 3600)]);
    }
    if ($diff < 86400 * 2) {
        return t('yesterday');
    }
    if ($diff < 86400 * 7) {
        return t(':n days ago', ['n' => (int)floor($diff / 86400)]);
    }
    return fmt_date($dt);
}

function human_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $n = (float)$bytes;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return round($n, $i ? 1 : 0) . ' ' . $units[$i];
}

/* ---------- Rendering ---------- */

/**
 * Render a view inside the main layout. $vars become local variables in the view.
 * Pass layout => 'auth' for the login-style layout or false for no layout.
 */
function render(string $view, array $vars = [], $layout = 'layout'): void
{
    $viewFile = APP_ROOT . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException("View not found: $view");
    }
    extract($vars, EXTR_SKIP);
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    clear_old();
    if ($layout === false) {
        echo $content;
        return;
    }
    $pageTitle = $pageTitle ?? '';
    if ($layout === 'layout' && (user()['role'] ?? '') === ROLE_CLIENT) {
        $layout = 'portal_layout';
    }
    require APP_ROOT . '/views/' . $layout . '.php';
}

/** Build simple pagination info. */
function paginate(int $total, int $perPage = 50): array
{
    $page = max(1, (int)input('page', 1));
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    return ['page' => $page, 'pages' => $pages, 'per' => $perPage, 'offset' => ($page - 1) * $perPage, 'total' => $total];
}

/** Current query string with some params replaced (for pagination/filter links). */
function current_url_with(array $replace): string
{
    $params = array_merge($_GET, $replace);
    $p = $params['p'] ?? 'dashboard';
    unset($params['p']);
    return url($p, array_filter($params, fn($v) => $v !== null && $v !== ''));
}

function selected($a, $b): string
{
    return ((string)$a === (string)$b) ? ' selected' : '';
}

function checked($cond): string
{
    return $cond ? ' checked' : '';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out ?: '?';
}

function avatar(?string $name, string $size = ''): string
{
    if (!$name) {
        return '<span class="avatar avatar-empty ' . $size . '" title="' . e(t('Unassigned')) . '"><i class="bi bi-person"></i></span>';
    }
    $hue = crc32($name) % 360;
    return '<span class="avatar ' . $size . '" style="--h:' . $hue . '" title="' . e($name) . '">' . e(initials($name)) . '</span>';
}
