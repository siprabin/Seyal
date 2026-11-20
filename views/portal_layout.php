<?php
/** Layout for client portal users: simple top bar, no internal navigation. */
$u = user();
$company = setting('company_name', APP_NAME);
?><!doctype html>
<html lang="<?= e(current_lang()) ?>" dir="<?= text_dir() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($pageTitle ? $pageTitle . ' · ' : '') . $company) ?></title>
<link rel="stylesheet" href="<?= asset(is_rtl() ? 'vendor/bootstrap/bootstrap.rtl.min.css' : 'vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?= head_icons() ?>
</head>
<body class="portal-body">
<header class="portal-top">
  <div class="container d-flex align-items-center gap-3 py-2">
    <a href="<?= e(url('portal')) ?>" class="text-decoration-none fw-semibold d-flex align-items-center gap-2"><img src="<?= asset('img/logo-mark.svg') ?>" alt="" width="28" height="28"><span class="text-truncate"><?= e($company) ?></span></a>
    <span class="small opacity-75 d-none d-md-inline"><?= e(t('Client portal')) ?></span>
    <div class="ms-auto d-flex align-items-center gap-2">
      <?= lang_menu('btn-light') ?>
      <div class="dropdown">
        <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> <span class="d-none d-sm-inline"><?= e($u['name']) ?></span></button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= e(url('portal')) ?>"><i class="bi bi-kanban me-2"></i><?= e(t('My projects')) ?></a></li>
          <li><a class="dropdown-item" href="<?= e(url('profile')) ?>"><i class="bi bi-key me-2"></i><?= e(t('Change password')) ?></a></li>
          <li><hr class="dropdown-divider"></li>
          <li><form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i><?= e(t('Log out')) ?></button></form></li>
        </ul>
      </div>
    </div>
  </div>
</header>
<main class="container py-4">
  <?php foreach (flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
