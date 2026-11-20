<?php
/**
 * Installation wizard.
 * 1. Checks the server  2. Connects to MySQL and creates the tables
 * 3. Creates the company settings and the first Super Admin  4. Writes config/config.php
 * Once config/config.php exists the wizard refuses to run again.
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
$configFile = APP_ROOT . '/config/config.php';

session_name('pminstall');
session_start();

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Minimal stand-ins for app helpers used by the shared files below.
if (!function_exists('t')) {
    function t(string $s, array $r = []): string
    {
        foreach ($r as $k => $v) {
            $s = str_replace(':' . $k, (string)$v, $s);
        }
        return $s;
    }
}
require APP_ROOT . '/app/permissions.php';
require APP_ROOT . '/app/migrate.php';

// Same order as SUPPORTED_LANGS in app/i18n.php.
$installLangs = ['en' => 'English', 'hi' => 'हिन्दी (Hindi)', 'ta' => 'தமிழ் (Tamil)', 'kn' => 'ಕನ್ನಡ (Kannada)', 'te' => 'తెలుగు (Telugu)', 'ml' => 'മലയാളം (Malayalam)', 'ar' => 'العربية (Arabic)', 'de' => 'Deutsch (German)', 'es' => 'Español (Spanish)'];

$installed = is_file($configFile);
$step = $installed ? 'done' : ($_SESSION['install_step'] ?? 'check');
$errors = [];

if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['install_csrf'];

/* ---------- Requirement checks ---------- */
$checks = [
    ['PHP 8.1 or newer', version_compare(PHP_VERSION, '8.1.0', '>='), 'You have PHP ' . PHP_VERSION, true],
    ['PDO MySQL extension', extension_loaded('pdo_mysql'), 'Enable "pdo_mysql" in php.ini / hPanel → PHP Configuration', true],
    ['mbstring extension', extension_loaded('mbstring'), 'Needed for Indian-language text', true],
    ['OpenSSL extension', extension_loaded('openssl'), 'Needed for secure email and encryption', true],
    ['fileinfo extension', extension_loaded('fileinfo'), 'Recommended for detecting uploaded file types', false],
    ['config/ folder writable', is_writable(APP_ROOT . '/config'), 'Give write permission to the config folder (755 or 775)', true],
    ['uploads/ folder writable', is_writable(APP_ROOT . '/uploads'), 'Give write permission to the uploads folder', true],
    ['storage/logs/ folder writable', is_writable(APP_ROOT . '/storage/logs'), 'Give write permission to storage/logs', true],
];
$checksOk = !in_array(false, array_map(fn($c) => $c[1] || !$c[3], $checks), true);

function install_pdo(array $db, bool $withDb = true): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], (int)$db['port']) . ($withDb ? ';dbname=' . $db['name'] : '');
    return new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
}

if (!$installed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['_csrf'] ?? ''))) {
        $errors[] = 'Session expired. Please try again.';
    } elseif (($_POST['go'] ?? '') === 'check') {
        if ($checksOk) {
            $step = $_SESSION['install_step'] = 'database';
        }
    } elseif (($_POST['go'] ?? '') === 'back') {
        $step = $_SESSION['install_step'] = 'database';
    } elseif (($_POST['go'] ?? '') === 'test_db') {
        // Test only: report what we find, never create or change anything.
        $db = [
            'host' => trim($_POST['host'] ?? 'localhost') ?: 'localhost',
            'port' => (int)($_POST['port'] ?? 3306) ?: 3306,
            'name' => trim($_POST['name'] ?? ''),
            'user' => trim($_POST['user'] ?? ''),
            'pass' => (string)($_POST['pass'] ?? ''),
        ];
        $_SESSION['install_db_form'] = array_diff_key($db, ['pass' => 1]);
        $testPass = $db['pass'];
        try {
            $server = install_pdo($db, false);
            $version = (string)$server->query('SELECT VERSION()')->fetchColumn();
            $testResult = ['ok', "Connected to the database server (version $version)."];
            if ($db['name'] === '') {
                $testResult[1] .= ' Now enter a database name.';
            } else {
                $exists = $server->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
                $exists->execute([$db['name']]);
                if (!$exists->fetchColumn()) {
                    $testResult[1] .= " Database \"{$db['name']}\" does not exist yet; the installer will try to create it when you click Connect & continue.";
                    $testResult[0] = 'warn';
                } else {
                    $pdo = install_pdo($db);
                    $tables = (int)$pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchColumn();
                    $hasApp = (bool)$pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
                    if ($hasApp) {
                        $testResult = ['error', "Connected, but database \"{$db['name']}\" already contains this app's tables. Use an empty database."];
                    } else {
                        $testResult[1] .= " Database \"{$db['name']}\" is reachable" . ($tables ? " ($tables other tables found; that is OK)." : ' and empty. Ready to install.');
                    }
                }
            }
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            $hint = match (true) {
                str_contains($msg, '1045') => 'Wrong username or password.',
                str_contains($msg, '2002') || str_contains($msg, 'refused') => 'Cannot reach the server. Check the host and port, and that MySQL is running.',
                str_contains($msg, '1044') => 'This user has no access to that database. Grant the user access in your hosting panel.',
                default => '',
            };
            $testResult = ['error', trim("Connection failed. $hint Details: $msg")];
        }
    } elseif (($_POST['go'] ?? '') === 'database') {
        $db = [
            'host' => trim($_POST['host'] ?? 'localhost') ?: 'localhost',
            'port' => (int)($_POST['port'] ?? 3306) ?: 3306,
            'name' => trim($_POST['name'] ?? ''),
            'user' => trim($_POST['user'] ?? ''),
            'pass' => (string)($_POST['pass'] ?? ''),
        ];
        $_SESSION['install_db_form'] = array_diff_key($db, ['pass' => 1]);
        if ($db['name'] === '' || !preg_match('/^[A-Za-z0-9_\-$]+$/', $db['name'])) {
            $errors[] = 'Enter a valid database name (letters, numbers, underscore).';
        } elseif ($db['user'] === '') {
            $errors[] = 'Enter the database username.';
        } else {
            try {
                try {
                    $pdo = install_pdo($db);
                } catch (PDOException $e) {
                    // Unknown database: try to create it (works on XAMPP; on shared hosting create it in the control panel).
                    if (str_contains($e->getMessage(), '1049') || str_contains($e->getMessage(), 'Unknown database')) {
                        $root = install_pdo($db, false);
                        $root->exec('CREATE DATABASE `' . str_replace('`', '', $db['name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                        $pdo = install_pdo($db);
                    } else {
                        throw $e;
                    }
                }
                $existing = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
                if ($existing) {
                    $errors[] = 'This database already contains the app tables. Use an empty database (or drop the old tables first).';
                } else {
                    $_SESSION['install_db'] = $db;
                    $step = $_SESSION['install_step'] = 'admin';
                }
            } catch (PDOException $e) {
                $errors[] = 'Could not connect: ' . $e->getMessage();
            }
        }
    } elseif (($_POST['go'] ?? '') === 'admin' && !empty($_SESSION['install_db'])) {
        $a = [
            'company'  => trim($_POST['company'] ?? ''),
            'name'     => trim($_POST['admin_name'] ?? ''),
            'email'    => strtolower(trim($_POST['admin_email'] ?? '')),
            'pass'     => (string)($_POST['admin_pass'] ?? ''),
            'pass2'    => (string)($_POST['admin_pass2'] ?? ''),
            'lang'     => isset($installLangs[$_POST['lang'] ?? '']) ? $_POST['lang'] : 'en',
            'timezone' => in_array($_POST['timezone'] ?? '', timezone_identifiers_list(), true) ? $_POST['timezone'] : 'Asia/Kolkata',
        ];
        $_SESSION['install_admin_form'] = array_diff_key($a, ['pass' => 1, 'pass2' => 1]);
        if ($a['company'] === '') {
            $errors[] = 'Enter your company name.';
        }
        if ($a['name'] === '') {
            $errors[] = 'Enter the administrator\'s name.';
        }
        if (!filter_var($a['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid administrator email.';
        }
        if (strlen($a['pass']) < 8) {
            $errors[] = 'The password must be at least 8 characters.';
        } elseif ($a['pass'] !== $a['pass2']) {
            $errors[] = 'The two passwords do not match.';
        }
        if (!$errors) {
            $db = $_SESSION['install_db'];
            try {
                date_default_timezone_set($a['timezone']);
                $pdo = install_pdo($db);
                run_migrations($pdo);
                seed_role_permissions($pdo);
                $now = date('Y-m-d H:i:s');
                $set = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
                foreach ([
                    'company_name' => $a['company'], 'timezone' => $a['timezone'], 'default_lang' => $a['lang'],
                    'date_format' => 'd-m-Y', 'upload_max_mb' => '10', 'installed_at' => $now,
                ] as $k => $v) {
                    $set->execute([$k, $v]);
                }
                $roleId = $pdo->query("SELECT id FROM roles WHERE slug = 'super_admin'")->fetchColumn();
                $pdo->prepare('INSERT INTO users (name, email, password_hash, role_id, designation, lang, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$a['name'], $a['email'], password_hash($a['pass'], PASSWORD_DEFAULT), $roleId, 'Administrator', $a['lang'], $now]);
                $adminId = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO audit_log (user_id, user_name, entity_type, entity_id, entity_label, action, note, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$adminId, $a['name'], 'system', null, 'Installation', 'create', 'Application installed', $_SERVER['REMOTE_ADDR'] ?? '', $now]);

                $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                $base = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
                $config = [
                    'db'       => $db,
                    'app_key'  => bin2hex(random_bytes(32)),
                    'base_url' => $base,
                    'debug'    => false,
                ];
                $php = "<?php\n// Generated by the installer on $now. Keep this file private.\nreturn " . var_export($config, true) . ";\n";
                if (file_put_contents($configFile, $php, LOCK_EX) === false) {
                    throw new RuntimeException('Could not write config/config.php. Check folder permissions.');
                }
                @chmod($configFile, 0640);
                $_SESSION = [];
                $installed = true;
                $step = 'done';
            } catch (Throwable $e) {
                $errors[] = 'Installation failed: ' . $e->getMessage();
            }
        }
    }
}

$dbForm = $_SESSION['install_db_form'] ?? ['host' => 'localhost', 'port' => 3306, 'name' => 'project_mgmt', 'user' => 'root'];
$adminForm = $_SESSION['install_admin_form'] ?? ['company' => '', 'name' => '', 'email' => '', 'lang' => 'en', 'timezone' => 'Asia/Kolkata'];
$steps = ['check' => '1. Server check', 'database' => '2. Database', 'admin' => '3. Company & admin', 'done' => '4. Finished'];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install · Project Management</title>
<link rel="stylesheet" href="../assets/vendor/bootstrap/bootstrap.min.css">
<link rel="stylesheet" href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
<style>body{background:#f4f6f9}.wrap{max-width:720px;margin:2rem auto;padding:0 1rem}.steps .badge{font-weight:500}</style>
</head>
<body>
<div class="wrap">
  <h1 class="h4 mb-3"><i class="bi bi-grid-1x2-fill text-primary"></i> Project Management — Installation</h1>
  <div class="steps d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($steps as $k => $label): ?>
      <span class="badge rounded-pill <?= $k === $step ? 'bg-primary' : 'bg-light text-dark border' ?>"><?= h($label) ?></span>
    <?php endforeach; ?>
  </div>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endforeach; ?>

  <div class="card shadow-sm"><div class="card-body p-4">
  <?php if ($step === 'done'): ?>
    <?php if ($installed && ($_SERVER['REQUEST_METHOD'] === 'POST')): ?>
      <h2 class="h5 text-success"><i class="bi bi-check-circle"></i> Installation complete</h2>
      <p>The app is ready. Log in with the administrator email and password you just chose.</p>
    <?php else: ?>
      <h2 class="h5">Already installed</h2>
      <p>This app is already installed, so the installer is locked. To reinstall, delete <code>config/config.php</code> and use an empty database.</p>
    <?php endif; ?>
    <div class="alert alert-info small mb-3">
      <strong>Next steps</strong>
      <ol class="mb-0 ps-3">
        <li>Open <em>Settings → Email</em> and enter your SMTP details (needed for “Forgot password” and, in Phase 2, daily reports).</li>
        <li>Add departments, then users (set each person's <em>Reports to</em>).</li>
        <li>Optional: <em>Settings → Demo data</em> loads a sample company to try things out.</li>
        <li>For extra safety, delete <code>install/index.php</code> (keep the <code>install/migrations</code> folder).</li>
      </ol>
    </div>
    <a href="../index.php?p=login" class="btn btn-primary">Go to login</a>

  <?php elseif ($step === 'check'): ?>
    <h2 class="h5">Server check</h2>
    <table class="table table-sm align-middle">
      <?php foreach ($checks as [$label, $ok, $hint, $required]): ?>
        <tr>
          <td><?= h($label) ?><?= $required ? '' : ' <span class="text-muted small">(recommended)</span>' ?></td>
          <td class="text-end"><?= $ok ? '<span class="text-success"><i class="bi bi-check-circle-fill"></i> OK</span>' : '<span class="' . ($required ? 'text-danger' : 'text-warning') . '"><i class="bi bi-x-circle-fill"></i> ' . h($hint) . '</span>' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= h($csrf) ?>"><input type="hidden" name="go" value="check">
      <?php if ($checksOk): ?>
        <button class="btn btn-primary">Continue <i class="bi bi-arrow-right"></i></button>
      <?php else: ?>
        <button class="btn btn-outline-secondary">Check again</button>
      <?php endif; ?>
    </form>

  <?php elseif ($step === 'database'): ?>
    <h2 class="h5">Database</h2>
    <p class="small text-muted">On <strong>XAMPP</strong> the user is usually <code>root</code> with an empty password; the database is created for you if it doesn't exist.
      On <strong>Hostinger</strong>, first create a MySQL database and user in hPanel → Databases, then enter those details here (host is usually <code>localhost</code>).</p>
    <?php if (!empty($testResult)): [$kind, $msg] = $testResult; ?>
      <div class="alert alert-<?= $kind === 'ok' ? 'success' : ($kind === 'warn' ? 'warning' : 'danger') ?> small">
        <i class="bi bi-<?= $kind === 'ok' ? 'check-circle' : ($kind === 'warn' ? 'exclamation-triangle' : 'x-circle') ?>"></i> <?= h($msg) ?>
      </div>
    <?php endif; ?>
    <form method="post" class="row g-3">
      <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">
      <div class="col-md-8"><label class="form-label">Host</label><input class="form-control" name="host" value="<?= h($dbForm['host']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Port</label><input type="number" class="form-control" name="port" value="<?= h($dbForm['port']) ?>" required></div>
      <div class="col-md-12"><label class="form-label">Database name</label><input class="form-control" name="name" value="<?= h($dbForm['name']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="user" value="<?= h($dbForm['user']) ?>" required autocomplete="off"></div>
      <div class="col-md-6"><label class="form-label">Password</label><input type="password" class="form-control" name="pass" value="<?= h($testPass ?? '') ?>" autocomplete="new-password"></div>
      <div class="col-12 d-flex flex-wrap gap-2">
        <button class="btn btn-outline-primary" name="go" value="test_db" formnovalidate><i class="bi bi-plug"></i> Test connection</button>
        <button class="btn btn-primary" name="go" value="database">Connect &amp; continue <i class="bi bi-arrow-right"></i></button>
      </div>
    </form>

  <?php elseif ($step === 'admin'): ?>
    <h2 class="h5">Company &amp; Super Admin</h2>
    <p class="small text-success"><i class="bi bi-check-circle"></i> Connected to database <code><?= h($_SESSION['install_db']['name'] ?? '') ?></code>.</p>
    <form method="post" class="row g-3">
      <input type="hidden" name="_csrf" value="<?= h($csrf) ?>"><input type="hidden" name="go" value="admin">
      <div class="col-12"><label class="form-label">Company name</label><input class="form-control" name="company" value="<?= h($adminForm['company']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Your name (Super Admin)</label><input class="form-control" name="admin_name" value="<?= h($adminForm['name']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Your email (used to log in)</label><input type="email" class="form-control" name="admin_email" value="<?= h($adminForm['email']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Password</label><input type="password" class="form-control" name="admin_pass" minlength="8" required autocomplete="new-password"></div>
      <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" class="form-control" name="admin_pass2" minlength="8" required autocomplete="new-password"></div>
      <div class="col-md-6"><label class="form-label">Default language</label>
        <select class="form-select" name="lang"><?php foreach ($installLangs as $code => $label): ?><option value="<?= h($code) ?>"<?= $adminForm['lang'] === $code ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Time zone</label>
        <select class="form-select" name="timezone"><?php foreach (timezone_identifiers_list() as $tz): ?><option<?= $tz === $adminForm['timezone'] ? ' selected' : '' ?>><?= h($tz) ?></option><?php endforeach; ?></select></div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Install</button>
      </div>
    </form>
    <form method="post" class="mt-2"><input type="hidden" name="_csrf" value="<?= h($csrf) ?>"><input type="hidden" name="go" value="back"><button class="btn btn-link btn-sm px-0">&larr; Change database details</button></form>
  <?php endif; ?>
  </div></div>
</div>
</body>
</html>
