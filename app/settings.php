<?php
/** Key/value application settings stored in the `settings` table. */
declare(strict_types=1);

function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        try {
            foreach (rows('SELECT k, v FROM settings') as $r) {
                $cache[$r['k']] = $r['v'];
            }
        } catch (PDOException $e) {
            // Table may not exist yet during install/upgrade.
        }
    }
    return $cache;
}

function setting(string $key, $default = null)
{
    $all = settings_all();
    return (array_key_exists($key, $all) && $all[$key] !== '') ? $all[$key] : $default;
}

/** Encrypt secrets (e.g. SMTP password) at rest using the app key from config.php. */
function encrypt_secret(string $plain): string
{
    if ($plain === '') {
        return '';
    }
    $key = hash('sha256', (string)config('app_key'), true);
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return 'enc:' . base64_encode($iv . $cipher);
}

function decrypt_secret(?string $stored): string
{
    if (!$stored || !str_starts_with($stored, 'enc:')) {
        return (string)$stored;
    }
    $raw = base64_decode(substr($stored, 4));
    $key = hash('sha256', (string)config('app_key'), true);
    $plain = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, substr($raw, 0, 16));
    return $plain === false ? '' : $plain;
}

function set_setting(string $key, ?string $value): void
{
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
    settings_all(true);
}
