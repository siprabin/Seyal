<?php
/**
 * Application bootstrap: loads config, helpers, session and the current user.
 * Every public entry point (index.php, cron scripts in later phases) requires this file.
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', 'Seyal');
define('APP_VERSION', '3.0.0 (Phase 3)');
// Product credit, shown on every page, printout, export, email and Teams message (see powered_by()).
define('APP_AUTHOR', 'Fluxosoft');
define('APP_AUTHOR_URL', 'https://fluxosoft.com');
define('APP_SUPPORT_URL', 'https://fluxosoft.com/contact');
define('APP_REPO_URL', 'https://github.com/siprabin/seyal');

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Not installed. Open install/ in a browser first.\n");
        exit(1);
    }
    header('Location: install/');
    exit;
}
$GLOBALS['APP_CONFIG'] = require $configFile;

$debug = !empty($GLOBALS['APP_CONFIG']['debug']);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');

require APP_ROOT . '/app/helpers.php';
require APP_ROOT . '/app/db.php';
require APP_ROOT . '/app/settings.php';
require APP_ROOT . '/app/i18n.php';
require APP_ROOT . '/app/permissions.php';
require APP_ROOT . '/app/auth.php';
require APP_ROOT . '/app/access.php';
require APP_ROOT . '/app/audit.php';
require APP_ROOT . '/app/mailer.php';
require APP_ROOT . '/app/migrate.php';
require APP_ROOT . '/app/daylog.php';
require APP_ROOT . '/app/teams.php';
require APP_ROOT . '/app/notifications.php';
require APP_ROOT . '/app/scheduler.php';
require APP_ROOT . '/app/analytics.php';
require APP_ROOT . '/app/export.php';
require APP_ROOT . '/app/backup.php';

date_default_timezone_set(setting('timezone', 'Asia/Kolkata'));

if (PHP_SAPI !== 'cli') {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('seyal_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() . '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

set_exception_handler(function (Throwable $e) use ($debug) {
    error_log((string)$e);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, (string)$e . "\n");
        exit(1);
    }
    http_response_code(500);
    if ($debug) {
        echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
    } else {
        echo '<h1>Something went wrong</h1><p>The error has been logged. Please contact your administrator.</p>';
    }
});
