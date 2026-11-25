<?php
/**
 * Help guide page. The guide itself is views/help/<lang>.php: it sets $sections ([id => title]) for the
 * table of contents and prints the content. To translate the guide, copy en.php to e.g. ta.php.
 */
$sections = [];
ob_start();
require __DIR__ . '/' . $guide . '.php';
$guideHtml = ob_get_clean();
?>
<div class="page-head">
  <h1><i class="bi bi-book text-primary"></i> <?= e(t('Help guide')) ?></h1>
  <div class="actions help-actions d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-primary btn-sm" data-tour-start><i class="bi bi-signpost-split"></i> <?= e(t('Take the tour')) ?></button>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> <?= e(t('Print / Save as PDF')) ?></button>
  </div>
</div>

<?php if (!$translated): ?>
  <div class="alert alert-info small help-actions"><i class="bi bi-translate"></i> <?= e(t('This guide is not yet available in your language, so it is shown in English.')) ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-3">
    <nav class="help-toc card card-body small" aria-label="<?= e(t('Contents')) ?>">
      <div class="fw-semibold mb-1"><?= e(t('Contents')) ?></div>
      <?php foreach ($sections as $id => $title): ?><a href="#<?= e($id) ?>"><?= e($title) ?></a><?php endforeach; ?>
    </nav>
  </div>
  <div class="col-lg-9">
    <article class="help-guide card card-body" lang="<?= e($guide) ?>"<?= $guide === 'ar' ? ' dir="rtl"' : ($translated ? '' : ' dir="ltr"') ?>>
      <?= $guideHtml ?>
    </article>
  </div>
</div>
