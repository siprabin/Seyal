<div class="page-head">
  <h1><?= e(t('Appraisals')) ?></h1>
  <div class="actions"><?php if ($hasTeam): ?><a href="<?= e(url('appraisals', ['a' => 'create'])) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(t('New appraisal')) ?></a><?php endif; ?></div>
</div>
<div class="d-flex flex-wrap gap-2 align-items-center small text-muted mb-3">
  <?= appraisal_status_badge('draft') ?> <i class="bi bi-arrow-right"></i> <?= appraisal_status_badge('finalised') ?> <i class="bi bi-arrow-right"></i> <?= appraisal_status_badge('published') ?>
  <span class="ms-2"><?= e(t('Figures are frozen when finalised. The employee sees the appraisal only after it is published.')) ?></span>
</div>

<?php if ($mine): ?>
<div class="card mb-3">
  <div class="card-header"><?= e(t('My appraisals')) ?></div>
  <ul class="list-group list-group-flush">
    <?php foreach ($mine as $a): ?>
      <li class="list-group-item d-flex flex-wrap gap-2 align-items-center">
        <a class="fw-semibold text-decoration-none" href="<?= e(url('appraisals', ['a' => 'view', 'id' => $a['id']])) ?>"><?= e($a['title']) ?></a>
        <span class="small text-muted ltr"><?= e(fmt_date($a['period_from'])) ?> – <?= e(fmt_date($a['period_to'])) ?></span>
        <span class="ms-auto"><?= score_badge($a['score'] !== null ? (float)$a['score'] : null) ?></span>
        <?php if (!$a['acknowledged_at']): ?><span class="badge bg-warning text-dark"><?= e(t('Please read and acknowledge')) ?></span><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if ($hasTeam): ?>
<form class="row g-2 mb-3 filters js-autosubmit" method="get">
  <input type="hidden" name="p" value="appraisals">
  <div class="col-auto">
    <select name="status" class="form-select form-select-sm">
      <option value=""><?= e(t('All statuses')) ?></option>
      <?php foreach (appraisal_statuses() as $k => $s): ?><option value="<?= e($k) ?>"<?= selected($status, $k) ?>><?= e($s['label']) ?></option><?php endforeach; ?>
    </select>
  </div>
</form>
<div class="card table-card">
  <table class="table table-hover table-sm mb-0">
    <thead class="table-light"><tr><th class="ps-3"><?= e(t('Name')) ?></th><th><?= e(t('Appraisal')) ?></th><th class="d-none d-md-table-cell"><?= e(t('Period')) ?></th><th><?= e(t('Status')) ?></th><th class="text-center"><?= e(t('Score')) ?></th><th class="d-none d-lg-table-cell"><?= e(t('Reviewer')) ?></th><th class="d-none d-lg-table-cell"><?= e(t('Acknowledged')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($list as $a): ?>
      <tr>
        <td class="ps-3"><?= avatar($a['user_name'], 'avatar-sm') ?> <?= e($a['user_name']) ?></td>
        <td><a class="text-decoration-none" href="<?= e(url('appraisals', ['a' => $a['status'] === 'draft' ? 'edit' : 'view', 'id' => $a['id']])) ?>"><?= e($a['title']) ?></a></td>
        <td class="d-none d-md-table-cell small ltr"><?= e(fmt_date($a['period_from'])) ?> – <?= e(fmt_date($a['period_to'])) ?></td>
        <td><?= appraisal_status_badge($a['status']) ?></td>
        <td class="text-center"><?= score_badge($a['score'] !== null ? (float)$a['score'] : null) ?></td>
        <td class="d-none d-lg-table-cell small"><?= e($a['reviewer_name']) ?></td>
        <td class="d-none d-lg-table-cell small"><?= $a['acknowledged_at'] ? e(fmt_date($a['acknowledged_at'])) : ($a['status'] === 'published' ? '<span class="text-warning-emphasis">' . e(t('Waiting')) . '</span>' : '—') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="7" class="text-center text-muted py-4"><?= e(t('No appraisals yet.')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php if (!$hasTeam && !$mine): ?>
  <div class="card"><div class="card-body text-muted"><?= e(t('Your scores and ratings appear here once your manager publishes an appraisal.')) ?></div></div>
<?php endif; ?>
