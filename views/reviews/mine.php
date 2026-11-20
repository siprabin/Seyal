<div class="page-head"><h1><?= e(t('My weekly feedback')) ?></h1>
  <div class="actions"><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('performance')) ?>"><i class="bi bi-graph-up"></i> <?= e(t('My performance')) ?></a></div></div>
<?php if (!$reviews): ?>
  <div class="card"><div class="card-body text-muted"><?= e(t('Your manager has not shared any weekly feedback yet.')) ?></div></div>
<?php endif; ?>
<?php foreach ($reviews as $r): ?>
  <div class="card mb-3">
    <div class="card-header d-flex"><span class="ltr"><?= e(t('Week of :date', ['date' => fmt_date($r['week_start'])])) ?></span><span class="ms-auto small text-muted"><?= e($r['reviewer_name']) ?></span></div>
    <div class="card-body small">
      <?php foreach ([['went_well', t('What went well'), 'text-success'], ['to_improve', t('What to improve'), 'text-warning-emphasis'], ['next_focus', t('Focus for next week'), 'text-primary']] as [$k, $l, $c]): if ($r[$k]): ?>
        <div class="mb-2"><div class="fw-semibold <?= $c ?>"><?= e($l) ?></div><div style="white-space:pre-wrap"><?= e($r[$k]) ?></div></div>
      <?php endif; endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
