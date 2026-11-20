<?php $v = fn($k, $d = '') => old($k, $review[$k] ?? $d); ?>
<div class="mb-2 small"><a href="<?= e(url('reviews', ['week' => $week])) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('Weekly reviews')) ?></a></div>
<div class="page-head">
  <div class="d-flex align-items-center gap-2"><?= avatar($person['name']) ?>
    <h1><?= e($person['name']) ?> <span class="text-muted fs-6 fw-normal ltr"><?= e(fmt_date($week)) ?> – <?= e(fmt_date($weekEnd)) ?></span></h1></div>
</div>
<div class="row g-3">
  <div class="col-lg-5 order-lg-2">
    <div class="row g-2 mb-3">
      <?php foreach ([
          [t('Hours logged'), fmt_hours($m['hours'])],
          [t('Tasks done'), $m['tasks_done']],
          [t('Avg quality'), $m['avg_quality'] !== null ? number_format($m['avg_quality'], 1) . ' / 5' : '—'],
          [t('On time'), $m['on_time_pct'] !== null ? $m['on_time_pct'] . '%' : '—'],
          [t('Days worked'), $m['days_worked'] . ' / ' . $m['working_days']],
          [t('Late start'), $m['late_starts']],
          [t('AI time'), $m['ai_minutes'] ? fmt_minutes($m['ai_minutes']) : '—'],
          [t('Time saved with AI'), $m['saved_minutes'] ? fmt_minutes($m['saved_minutes']) : '—'],
      ] as [$l, $val]): ?>
        <div class="col-6"><div class="metric"><div class="l"><?= e($l) ?></div><div dir="auto" class="v"><?= e((string)$val) ?></div></div></div>
      <?php endforeach; ?>
    </div>
    <div class="card mb-3">
      <div class="card-header"><?= e(t('Tasks finished this week')) ?></div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($tasksDone as $tk): ?>
          <li class="list-group-item d-flex gap-2"><a href="<?= e(url('tasks', ['a' => 'view', 'id' => $tk['id']])) ?>" class="text-decoration-none flex-grow-1 min-w-0 text-truncate"><?= e($tk['title']) ?></a>
            <?= $tk['quality'] ? rating_stars((int)$tk['quality']) : '<a class="small" href="' . e(url('tasks', ['a' => 'view', 'id' => $tk['id']])) . '">' . e(t('Rate')) . '</a>' ?></li>
        <?php endforeach; ?>
        <?php if (!$tasksDone): ?><li class="list-group-item text-muted"><?= e(t('Nothing here.')) ?></li><?php endif; ?>
      </ul>
    </div>
    <?php if ($blockers): ?>
    <div class="card border-danger-subtle">
      <div class="card-header text-danger"><?= e(t('Blockers')) ?></div>
      <ul class="list-group list-group-flush small"><?php foreach ($blockers as $b): ?><li class="list-group-item"><span class="text-muted"><?= e(fmt_date($b['work_date'])) ?>:</span> <?= e($b['blockers']) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-7 order-lg-1">
    <form method="post" class="card">
      <?= csrf_field() ?>
      <div class="card-body">
        <div class="mb-3">
          <div class="form-label"><?= e(t('Overall rating for the week')) ?> <span class="text-danger">*</span></div>
          <div class="btn-group rate-btns flex-wrap" role="radiogroup">
            <?php foreach (quality_labels() as $n => $label): ?>
              <input type="radio" class="btn-check" name="rating" id="wr<?= $n ?>" value="<?= $n ?>"<?= checked((int)$v('rating') === $n) ?> required>
              <label class="btn btn-outline-warning text-dark" for="wr<?= $n ?>"><?= $n ?>★ <span class="small d-none d-sm-inline"><?= e($label) ?></span></label>
            <?php endforeach; ?>
          </div>
          <div class="form-text"><?= e(t('Only managers see this rating. It feeds into the appraisal.')) ?></div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="went_well"><?= e(t('What went well')) ?></label>
          <textarea class="form-control" id="went_well" name="went_well" rows="3"><?= e($v('went_well')) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="to_improve"><?= e(t('What to improve')) ?></label>
          <textarea class="form-control" id="to_improve" name="to_improve" rows="3"><?= e($v('to_improve')) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="next_focus"><?= e(t('Focus for next week')) ?></label>
          <textarea class="form-control" id="next_focus" name="next_focus" rows="2"><?= e($v('next_focus')) ?></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="shared" name="shared" value="1"<?= checked($v('shared', 1)) ?>>
          <label class="form-check-label" for="shared"><?= e(t('Share these comments with :name (the rating stays hidden)', ['name' => $person['name']])) ?></label>
        </div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= e(t('Save review')) ?></button></div>
    </form>
  </div>
</div>
