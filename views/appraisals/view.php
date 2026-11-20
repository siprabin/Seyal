<?php
$isEmployee = (int)$a['user_id'] === uid();
$steps = ['draft' => 1, 'finalised' => 2, 'published' => 3];
$cur = $steps[$a['status']];
?>
<div class="mb-2 small"><a href="<?= e(url($isEmployee ? 'performance' : 'appraisals', $isEmployee ? ['a' => 'mine'] : [])) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e($isEmployee ? t('My performance') : t('Appraisals')) ?></a></div>
<div class="page-head">
  <div class="d-flex align-items-center gap-2"><?= avatar($a['user_name'], 'avatar-lg') ?>
    <div><h1 class="mb-0"><?= e($a['title']) ?></h1>
      <div class="small text-muted"><?= e($a['user_name']) ?><?= $a['designation'] ? ' · ' . e($a['designation']) : '' ?> · <span class="ltr"><?= e(fmt_date($a['period_from'])) ?> – <?= e(fmt_date($a['period_to'])) ?></span></div></div></div>
  <div class="actions">
    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(url('appraisals', ['a' => 'print', 'id' => $a['id']])) ?>"><i class="bi bi-file-earmark-pdf"></i> <?= e(t('PDF / Print')) ?></a>
    <?php if (can_edit_appraisal($a)): ?><a class="btn btn-sm btn-primary" href="<?= e(url('appraisals', ['a' => 'edit', 'id' => $a['id']])) ?>"><i class="bi bi-pencil"></i> <?= e(t('Edit')) ?></a><?php endif; ?>
  </div>
</div>

<?php if (!$isEmployee): ?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3 small">
  <?php foreach (appraisal_statuses() as $k => $s): ?>
    <span class="badge rounded-pill <?= $steps[$k] <= $cur ? 'bg-' . $s['color'] : 'bg-light text-muted border' ?>"><?= e($s['label']) ?></span>
    <?php if ($k !== 'published'): ?><i class="bi bi-arrow-right text-muted"></i><?php endif; ?>
  <?php endforeach; ?>
  <?php if ($a['finalised_at']): ?><span class="text-muted ms-2"><?= e(t('Finalised by :name on :date', ['name' => $a['finalised_by_name'] ?? '—', 'date' => fmt_date($a['finalised_at'])])) ?></span><?php endif; ?>
  <?php if ($a['published_at']): ?><span class="text-muted">· <?= e(t('Published by :name on :date', ['name' => $a['published_by_name'] ?? '—', 'date' => fmt_date($a['published_at'])])) ?></span><?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-body d-flex flex-wrap align-items-center gap-4">
        <div><div class="small text-muted"><?= e(t('Performance score')) ?></div><div class="fs-2 fw-bold"><?= $a['score'] !== null || $a['status'] === 'draft' ? score_badge(isset($m['score']) && $m['score'] !== null ? (float)$m['score'] : null) : '—' ?></div></div>
        <div><div class="small text-muted"><?= e(t('Overall rating')) ?></div><div class="fs-4"><?= $a['overall_rating'] ? rating_stars((int)$a['overall_rating']) . ' <span class="small">' . e(quality_labels()[(int)$a['overall_rating']]) . '</span>' : '—' ?></div></div>
        <div class="small text-muted ms-auto"><?= e(t('Reviewer')) ?>: <?= e($a['reviewer_name'] ?? '—') ?></div>
      </div>
    </div>
    <div class="mb-3"><?php require __DIR__ . '/_metrics.php'; ?></div>
    <?php foreach ([['strengths', t('Strengths'), 'text-success'], ['improvements', t('Areas to improve'), 'text-warning-emphasis'], ['goals', t('Goals for the next period'), 'text-primary'], ['manager_comments', t('Manager\'s comments'), '']] as [$k, $l, $c]): if ($a[$k]): ?>
      <div class="card mb-3"><div class="card-header <?= $c ?>"><?= e($l) ?></div><div class="card-body" style="white-space:pre-wrap"><?= e($a[$k]) ?></div></div>
    <?php endif; endforeach; ?>
  </div>

  <div class="col-lg-4">
    <?php if (!$isEmployee && $a['status'] === 'finalised' && can('appraisals.publish')): ?>
      <form method="post" action="<?= e(url('appraisals', ['a' => 'publish', 'id' => $a['id']])) ?>" class="card mb-3 border-success-subtle" data-confirm="<?= e(t('Publish this appraisal? :name will be able to see it and will get an email.', ['name' => $a['user_name']])) ?>">
        <?= csrf_field() ?>
        <div class="card-body"><p class="small mb-2"><?= e(t('Ready to share with :name.', ['name' => $a['user_name']])) ?></p>
          <button class="btn btn-success w-100"><i class="bi bi-send-check"></i> <?= e(t('Publish to employee')) ?></button></div>
      </form>
    <?php elseif (!$isEmployee && $a['status'] === 'finalised'): ?>
      <div class="alert alert-info small"><?= e(t('Waiting for an administrator to publish this appraisal.')) ?></div>
    <?php endif; ?>

    <?php if ($a['status'] === 'published'): ?>
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person-check"></i> <?= e(t('Employee acknowledgement')) ?></div>
        <div class="card-body small">
          <?php if ($a['acknowledged_at']): ?>
            <div class="text-success mb-2"><i class="bi bi-check-circle"></i> <?= e(t('Acknowledged on :date', ['date' => fmt_datetime($a['acknowledged_at'])])) ?></div>
            <?php if ($a['employee_comment']): ?><div style="white-space:pre-wrap"><?= e($a['employee_comment']) ?></div><?php endif; ?>
          <?php elseif ($isEmployee): ?>
            <form method="post" action="<?= e(url('appraisals', ['a' => 'acknowledge', 'id' => $a['id']])) ?>">
              <?= csrf_field() ?>
              <label class="form-label" for="employee_comment"><?= e(t('Your comments (optional)')) ?></label>
              <textarea class="form-control mb-2" id="employee_comment" name="employee_comment" rows="4"></textarea>
              <button class="btn btn-primary w-100"><?= e(t('I have read this appraisal')) ?></button>
            </form>
          <?php else: ?>
            <span class="text-warning-emphasis"><?= e(t('Waiting for :name to acknowledge.', ['name' => $a['user_name']])) ?></span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!$isEmployee && $reviews): ?>
      <div class="card mb-3">
        <div class="card-header"><?= e(t('Weekly reviews in this period')) ?></div>
        <ul class="list-group list-group-flush small">
          <?php foreach ($reviews as $w): ?><li class="list-group-item d-flex"><span class="ltr"><?= e(fmt_date($w['week_start'])) ?></span><span class="ms-auto"><?= rating_stars((int)$w['rating']) ?></span></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!$isEmployee && in_array($a['status'], ['finalised', 'published'], true) && can('appraisals.publish')): ?>
      <form method="post" action="<?= e(url('appraisals', ['a' => 'reopen', 'id' => $a['id']])) ?>" class="card">
        <?= csrf_field() ?>
        <div class="card-header small"><i class="bi bi-unlock"></i> <?= e(t('Reopen as draft')) ?></div>
        <div class="card-body">
          <input class="form-control form-control-sm mb-2" name="reason" maxlength="300" required placeholder="<?= e(t('Reason')) ?>">
          <button class="btn btn-sm btn-outline-danger"><?= e(t('Reopen')) ?></button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
