<?php
/**
 * Front controller. Every page is index.php?p=<page>&a=<action>.
 * This works on any host without URL-rewrite rules.
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$routes = [
    // public
    'login'       => 'auth',
    'logout'      => 'auth',
    'forgot'      => 'auth',
    'reset'       => 'auth',
    'lang'        => 'lang',
    // signed-in
    'dashboard'   => 'dashboard',
    'mytasks'     => 'mytasks',
    'projects'    => 'projects',
    'tasks'       => 'tasks',
    'files'       => 'files',
    'clients'     => 'clients',
    'users'       => 'users',
    'orgchart'    => 'orgchart',
    'departments' => 'departments',
    'roles'       => 'roles',
    'audit'       => 'audit',
    'settings'    => 'settings',
    'profile'     => 'profile',
    'about'       => 'about',
    'help'        => 'help',
    'myday'       => 'myday',
    'team'        => 'team',
    'aitools'     => 'aitools',
    'overview'    => 'overview',
    'ratings'     => 'ratings',
    'reviews'     => 'reviews',
    'performance' => 'performance',
    'appraisals'  => 'appraisals',
    'reports'     => 'reports',
    'portal'      => 'portal',
];
$public = ['login', 'forgot', 'reset', 'lang'];

$page   = preg_replace('/[^a-z_]/', '', (string)($_GET['p'] ?? 'dashboard')) ?: 'dashboard';
$action = preg_replace('/[^a-z_]/', '', (string)($_GET['a'] ?? 'index')) ?: 'index';

if (!isset($routes[$page])) {
    abort(404);
}
if (!in_array($page, $public, true)) {
    require_login();
}
if (is_post()) {
    verify_csrf();
}

// Client logins only ever see the read-only portal.
if (user() && user()['role'] === ROLE_CLIENT && !in_array($page, ['portal', 'profile', 'about', 'logout', 'lang', 'login'], true)) {
    redirect(url('portal'));
}

// New files uploaded but the database not updated yet: only the update screen works.
if (user() && !in_array($page, ['logout', 'lang'], true) && pending_migrations(db())) {
    $GLOBALS['APP_UPDATE_PENDING'] = true;
    $isUpdatePage = $page === 'settings' && (($_GET['tab'] ?? '') === 'system' || $action === 'migrate');
    if (!can('settings.manage')) {
        abort(503, t('The system is being updated. Please try again in a few minutes.'));
    }
    if (!$isUpdatePage) {
        flash('warning', t('A database update is needed before the new features can be used. Please run it below.'));
        redirect(url('settings', ['tab' => 'system']));
    }
} elseif (!in_array($page, $public, true)) {
    maybe_run_builtin_scheduler();
}

require APP_ROOT . '/pages/' . $routes[$page] . '.php';
