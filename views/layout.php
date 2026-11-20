<?php
/** Main signed-in layout: sidebar + top bar + content. */
$u = user();
$company = setting('company_name', APP_NAME);
$current = $_GET['p'] ?? 'dashboard';
$updatePending = !empty($GLOBALS['APP_UPDATE_PENDING']);
$myDay = (!$updatePending && daylog_enabled_for($u)) ? get_work_day((int)$u['id'], today()) : null;
$hasTeam = !$updatePending && team_member_ids();
$nav = [
    ['dashboard', 'bi-speedometer2', t('Dashboard'), true],
    ['overview', 'bi-activity', t('Project Pulse'), !$updatePending && ($hasTeam || can('projects.view_all') || can('reports.view'))],
    ['myday', 'bi-sunrise', t('My Day'), !$updatePending],
    ['team', 'bi-people-fill', t('Team Day'), $hasTeam],
    ['mytasks', 'bi-check2-square', t('My Tasks'), true],
    ['projects', 'bi-kanban', t('Projects'), true],
    ['clients', 'bi-building', t('Clients'), can('clients.view')],
];
$hasReports = !$updatePending && (bool)val('SELECT COUNT(*) FROM users WHERE reports_to = ? AND is_active = 1', [$u['id']]);
$toRate = ($hasReports || is_super_admin()) && !$updatePending ? count(tasks_to_rate((int)$u['id'])) : 0;
$perfNav = [
    ['ratings', 'bi-star', t('Task ratings'), $hasReports || is_super_admin(), $toRate],
    ['reviews', 'bi-chat-square-text', t('Weekly reviews'), $hasReports || is_super_admin()],
    ['performance', 'bi-graph-up', t('Performance'), !$updatePending],
    ['appraisals', 'bi-award', t('Appraisals'), !$updatePending],
    ['reports', 'bi-file-earmark-bar-graph', t('Reports'), can('reports.view') && !$updatePending],
];
$peopleNav = [
    ['users', 'bi-people', t('Users'), can('users.view')],
    ['orgchart', 'bi-diagram-3', t('Org Chart'), can('users.view')],
    ['departments', 'bi-collection', t('Departments'), can('departments.manage')],
];
$adminNav = [
    ['aitools', 'bi-robot', t('AI Tools'), can('settings.manage') && !$updatePending],
    ['roles', 'bi-shield-lock', t('Roles & Permissions'), is_super_admin()],
    ['audit', 'bi-clock-history', t('Audit Log'), can('audit.view')],
    ['settings', 'bi-gear', t('Settings'), can('settings.manage')],
];
$navBlock = function (array $items, ?string $heading = null) use ($current) {
    $visible = array_filter($items, fn($i) => $i[3]);
    if (!$visible) {
        return;
    }
    if ($heading) {
        echo '<div class="nav-heading">' . e($heading) . '</div>';
    }
    foreach ($visible as $item) {
        [$p, $icon, $label] = $item;
        $active = $current === $p ? ' active' : '';
        $badgeHtml = !empty($item[4]) ? '<span class="badge rounded-pill bg-danger ms-auto">' . (int)$item[4] . '</span>' : '';
        echo '<a class="nav-link' . $active . '" href="' . e(url($p)) . '"><i class="bi ' . $icon . '"></i><span>' . e($label) . '</span>' . $badgeHtml . '</a>';
    }
};
?><!doctype html>
<html lang="<?= e(current_lang()) ?>" dir="<?= text_dir() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(($pageTitle ? $pageTitle . ' · ' : '') . $company) ?></title>
<link rel="stylesheet" href="<?= asset(is_rtl() ? 'vendor/bootstrap/bootstrap.rtl.min.css' : 'vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?= head_icons() ?>
<script>try { if (localStorage.getItem('seyal.sidebar') === 'collapsed') document.documentElement.classList.add('sidebar-collapsed'); } catch (e) {}</script>
</head>
<body>
<div class="app">
  <aside class="sidebar offcanvas-lg offcanvas-start" id="sidebar" tabindex="-1">
    <div class="sidebar-brand">
      <img src="<?= asset('img/logo-mark.svg') ?>" alt="" class="brand-mark" width="30" height="30">
      <span class="brand-name text-truncate"><?= e($company) ?></span>
      <button type="button" class="btn-close btn-close-white d-lg-none ms-auto" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="<?= e(t('Close')) ?>"></button>
    </div>
    <nav class="sidebar-nav">
      <?php $navBlock($nav); ?>
      <?php $navBlock($perfNav, t('Performance')); ?>
      <?php $navBlock($peopleNav, t('People')); ?>
      <?php $navBlock($adminNav, t('Administration')); ?>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="btn btn-link text-body d-lg-none px-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="<?= e(t('Menu')) ?>"><i class="bi bi-list fs-4"></i></button>
      <button class="btn btn-link text-body d-none d-lg-inline-flex px-1 sidebar-toggle" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="true" aria-label="<?= e(t('Menu')) ?>" title="<?= e(t('Menu')) ?>"><i class="bi bi-list fs-4"></i></button>
      <div class="topbar-title text-truncate"><?= e($pageTitle) ?></div>
      <div class="ms-auto d-flex align-items-center gap-2">
        <?php if (!$updatePending && daylog_enabled_for($u)): ?>
          <?php if (!$myDay || in_array($myDay['status'], ['missed'], true)): ?>
            <form method="post" action="<?= e(url('myday', ['a' => 'start'])) ?>" class="m-0"<?= location_form_attr() ?>>
              <?= csrf_field() ?><button class="btn btn-success btn-sm text-nowrap"><i class="bi bi-play-circle"></i><span class="d-none d-sm-inline"> <?= e(t('Start Day')) ?></span></button>
            </form>
          <?php elseif ($myDay['status'] === 'open'): ?>
            <a href="<?= e(url('myday')) ?>" class="btn btn-outline-primary btn-sm text-nowrap day-pill"><span class="pulse-dot"></span><span class="d-none d-sm-inline"> <?= e(t('Working since :time', ['time' => fmt_time($myDay['started_at'])])) ?></span></a>
          <?php elseif ($myDay['status'] !== 'leave'): ?>
            <a href="<?= e(url('myday')) ?>" class="btn btn-outline-success btn-sm text-nowrap"><i class="bi bi-check-circle"></i><span class="d-none d-sm-inline"> <?= e(t('Day closed')) ?></span></a>
          <?php endif; ?>
        <?php endif; ?>
        <?= lang_menu() ?>
        <div class="dropdown">
          <button class="btn btn-link text-body text-decoration-none dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
            <?= avatar($u['name']) ?><span class="d-none d-md-inline"><?= e($u['name']) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header"><?= e(role_label($u['role'])) ?></h6></li>
            <li><a class="dropdown-item" href="<?= e(url('profile')) ?>"><i class="bi bi-person me-2"></i><?= e(t('My Profile')) ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?>
                <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i><?= e(t('Log out')) ?></button>
              </form>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small text-muted" href="<?= e(url('about')) ?>"><i class="bi bi-info-circle me-2"></i><?= e(t('About :app', ['app' => APP_NAME])) ?> <span class="ltr">v<?= e(APP_VERSION) ?></span></a></li>
          </ul>
        </div>
      </div>
    </header>

    <main class="content">
      <?php foreach (flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
          <?= e($f['msg']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= e(t('Close')) ?>"></button>
        </div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
    <footer class="app-footer">
      <span>&copy; <?= date('Y') ?> <?= e($company) ?></span>
      <?= powered_by_html() ?>
    </footer>
  </div>
</div>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script>window.APP = {csrf: <?= json_encode(csrf_token()) ?>, t: <?= json_encode([
    'confirm' => t('Are you sure?'),
    'saveFailed' => t('Could not save. The page will reload.'),
    'locating' => t('Getting your location…'),
    'locationRequired' => t('Your location is needed to start or close your day. Allow location access in your browser and try again.'),
], JSON_UNESCAPED_UNICODE) ?>};</script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (!empty($extraScripts)) foreach ($extraScripts as $s): ?>
<script src="<?= asset($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
