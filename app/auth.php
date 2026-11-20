<?php
/** Authentication, current user and permission checks. */
declare(strict_types=1);

const MAX_FAILED_LOGINS = 5;
const LOCKOUT_MINUTES   = 15;

function user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = row(
                'SELECT u.*, r.slug AS role, r.level AS role_level, d.name AS department_name
                 FROM users u JOIN roles r ON r.id = u.role_id
                 LEFT JOIN departments d ON d.id = u.department_id
                 WHERE u.id = ? AND u.is_active = 1',
                [$_SESSION['uid']]
            );
            if (!$user) {
                unset($_SESSION['uid']);
            }
        }
    }
    return $user;
}

function uid(): int
{
    return (int)(user()['id'] ?? 0);
}

function is_role(string ...$roles): bool
{
    $u = user();
    return $u && in_array($u['role'], $roles, true);
}

function is_super_admin(): bool
{
    return is_role(ROLE_SUPER_ADMIN);
}

function user_permissions(): array
{
    static $perms = null;
    if ($perms === null) {
        $u = user();
        if (!$u) {
            return [];
        }
        if ($u['role'] === ROLE_SUPER_ADMIN) {
            $perms = array_keys(permission_list());
        } else {
            $perms = q('SELECT perm FROM role_permissions WHERE role_id = ?', [$u['role_id']])->fetchAll(PDO::FETCH_COLUMN);
        }
    }
    return $perms;
}

function can(string $perm): bool
{
    return in_array($perm, user_permissions(), true);
}

function require_login(): void
{
    if (!user()) {
        if (is_ajax()) {
            json_out(['ok' => false, 'error' => t('Please log in again.')], 401);
        }
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect(url('login'));
    }
}

function require_perm(string $perm): void
{
    if (!can($perm)) {
        abort(403);
    }
}

/**
 * Attempt a login. Returns null on success, or an error message.
 * Accounts lock for a short time after repeated failures.
 */
function attempt_login(string $email, string $password): ?string
{
    $u = row('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);
    $generic = t('Incorrect email or password.');
    if (!$u) {
        password_verify($password, '$2y$10$wzGNb/WqexnsHAuZdjktuuhkGPetdFeTdSE6b5yMRW1mOkbF4TlNK'); // even out timing
        return $generic;
    }
    if (!$u['is_active']) {
        return t('This account is deactivated. Please contact your administrator.');
    }
    if ($u['locked_until'] && strtotime($u['locked_until']) > time()) {
        return t('Too many failed attempts. Try again after :time.', ['time' => date('h:i A', strtotime($u['locked_until']))]);
    }
    if (!password_verify($password, $u['password_hash'])) {
        $fails = (int)$u['failed_logins'] + 1;
        $lock = $fails >= MAX_FAILED_LOGINS ? date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60) : null;
        update('users', ['failed_logins' => $lock ? 0 : $fails, 'locked_until' => $lock], ['id' => $u['id']]);
        if ($lock) {
            audit_raw((int)$u['id'], $u['name'], 'user', (int)$u['id'], $u['name'], 'locked', null, null, null, 'Locked after repeated failed logins');
        }
        return $generic;
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], ['id' => $u['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['lang'] = $u['lang'] ?: setting('default_lang', 'en');
    update('users', ['failed_logins' => 0, 'locked_until' => null, 'last_login_at' => now()], ['id' => $u['id']]);
    audit_raw((int)$u['id'], $u['name'], 'user', (int)$u['id'], $u['name'], 'login');
    return null;
}

function logout(): void
{
    $lang = $_SESSION['lang'] ?? null;
    $_SESSION = [];
    session_regenerate_id(true);
    if ($lang) {
        $_SESSION['lang'] = $lang;
    }
}

function validate_password(string $pw, string $confirm): ?string
{
    if (mb_strlen($pw) < 8) {
        return t('Password must be at least 8 characters.');
    }
    if ($pw !== $confirm) {
        return t('The two passwords do not match.');
    }
    return null;
}
