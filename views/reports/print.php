<!doctype html>
<html lang="<?= e(current_lang()) ?>" dir="<?= text_dir() ?>">
<head>
<meta charset="utf-8">
<title><?= e($title) ?></title>
<link rel="stylesheet" href="<?= asset(is_rtl() ? 'vendor/bootstrap/bootstrap.rtl.min.css' : 'vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>
  body { background: #fff; font-size: 11px; }
  .page { padding: 16px; }
  table { width: 100%; }
  th { background: #e9eef5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  td, th { padding: 3px 6px !important; vertical-align: top; }
  @page { size: <?= count($headers) > 7 ? 'A4 landscape' : 'A4' ?>; margin: 10mm; }
</style>
</head>
<body>
<div class="page">
  <div class="no-print mb-2"><button class="btn btn-primary btn-sm" onclick="window.print()"><?= e(t('Print / Save as PDF')) ?></button></div>
  <div class="d-flex border-bottom pb-2 mb-2">
    <div><div class="fw-bold fs-6"><?= e(setting('company_name', APP_NAME)) ?></div><div class="fs-5"><?= e($title) ?></div><div class="text-muted ltr"><?= e($subtitle) ?></div></div>
    <div class="ms-auto text-muted small"><?= e(t('Generated')) ?>: <?= e(fmt_datetime(now())) ?><br><?= e(user()['name']) ?></div>
  </div>
  <table class="table table-bordered table-sm">
    <thead><tr><?php foreach ($headers as $i => $h): ?><th class="<?= in_array($i, $numericCols, true) ? 'text-end' : '' ?>"><?= e($h) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?><tr><?php foreach ($r as $i => $c): ?><td class="<?= in_array($i, $numericCols, true) ? 'text-end' : '' ?>"><?= e(is_float($c) ? rtrim(rtrim(number_format($c, 2, '.', ''), '0'), '.') : (string)$c) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="<?= count($headers) ?>" class="text-center text-muted"><?= e(t('No data for this selection.')) ?></td></tr><?php endif; ?>
    </tbody>
    <tfoot class="print-spacer"><tr><td colspan="<?= count($headers) ?>"></td></tr></tfoot>
  </table>
  <div class="text-muted small"><?= e(t(':n rows', ['n' => count($rows)])) ?></div>
  <?php require APP_ROOT . '/views/partials/print_credit.php'; ?>
</div>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
