<?php $company = setting('company_name', APP_NAME); ?><!doctype html>
<html lang="<?= e(current_lang()) ?>" dir="<?= text_dir() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($pageTitle ? $pageTitle . ' · ' : '') . $company) ?></title>
<link rel="stylesheet" href="<?= asset(is_rtl() ? 'vendor/bootstrap/bootstrap.rtl.min.css' : 'vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-wrap">
  <div class="d-flex justify-content-end mb-3">
    <?= lang_menu('btn-light') ?>
  </div>
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <div class="auth-logo"><i class="bi bi-grid-1x2-fill"></i></div>
        <h1 class="h5 mb-0"><?= e($company) ?></h1>
      </div>
      <?php foreach (flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </div>
</div>
</body>
</html>
