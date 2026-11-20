<!doctype html>
<html lang="<?= e(current_lang()) ?>" dir="<?= text_dir() ?>">
<head>
<meta charset="utf-8">
<title><?= e($a['user_name'] . ' — ' . $a['title']) ?></title>
<link rel="stylesheet" href="<?= asset(is_rtl() ? 'vendor/bootstrap/bootstrap.rtl.min.css' : 'vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>body{background:#fff;font-size:12px}.page{max-width:900px;margin:0 auto;padding:24px}.metric{padding:.4rem .6rem}@page{margin:14mm}</style>
</head>
<body>
<div class="page">
  <div class="no-print mb-3 d-flex gap-2"><button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> <?= e(t('Print / Save as PDF')) ?></button></div>
  <div class="d-flex border-bottom pb-2 mb-3">
    <div><div class="fw-bold fs-5"><?= e(setting('company_name', 'Project Management')) ?></div><div class="text-muted"><?= e(t('Appraisal')) ?></div></div>
    <div class="ms-auto text-end small text-muted"><?= e(fmt_datetime(now())) ?><br><?= appraisal_status_badge($a['status']) ?></div>
  </div>
  <h1 class="h4 mb-1"><?= e($a['title']) ?></h1>
  <p class="mb-3"><strong><?= e($a['user_name']) ?></strong><?= $a['designation'] ? ' · ' . e($a['designation']) : '' ?> · <span class="ltr"><?= e(fmt_date($a['period_from'])) ?> – <?= e(fmt_date($a['period_to'])) ?></span><br>
    <?= e(t('Reviewer')) ?>: <?= e($a['reviewer_name'] ?? '—') ?></p>
  <div class="d-flex gap-4 mb-3">
    <div><div class="text-muted"><?= e(t('Performance score')) ?></div><div class="fs-3 fw-bold"><?= isset($m['score']) && $m['score'] !== null ? number_format((float)$m['score'], 1) : '—' ?></div></div>
    <div><div class="text-muted"><?= e(t('Overall rating')) ?></div><div class="fs-4"><?= $a['overall_rating'] ? rating_stars((int)$a['overall_rating']) : '—' ?></div></div>
  </div>
  <div class="mb-3"><?php require __DIR__ . '/_metrics.php'; ?></div>
  <?php foreach ([['strengths', t('Strengths')], ['improvements', t('Areas to improve')], ['goals', t('Goals for the next period')], ['manager_comments', t('Manager\'s comments')]] as [$k, $l]): if ($a[$k]): ?>
    <h2 class="h6 mt-3"><?= e($l) ?></h2><p style="white-space:pre-wrap"><?= e($a[$k]) ?></p>
  <?php endif; endforeach; ?>
  <?php if ($a['acknowledged_at']): ?>
    <h2 class="h6 mt-3"><?= e(t('Employee acknowledgement')) ?></h2>
    <p><?= e(t('Acknowledged on :date', ['date' => fmt_datetime($a['acknowledged_at'])])) ?></p>
    <?php if ($a['employee_comment']): ?><p style="white-space:pre-wrap"><?= e($a['employee_comment']) ?></p><?php endif; ?>
  <?php endif; ?>
</div>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
