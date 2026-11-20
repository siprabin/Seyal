<div class="mb-2 small d-flex flex-wrap gap-3">
  <a href="<?= e(url('team', ['date' => $day['work_date']])) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('Team Day')) ?></a>
  <span class="ms-auto">
    <?php if ($prev): ?><a href="<?= e(url('team', ['a' => 'day', 'id' => $prev['id']])) ?>" class="text-decoration-none"><i class="bi bi-chevron-left"></i> <?= e(t('Previous day')) ?></a><?php endif; ?>
    <?php if ($next): ?><a href="<?= e(url('team', ['a' => 'day', 'id' => $next['id']])) ?>" class="text-decoration-none ms-3"><?= e(t('Next day')) ?> <i class="bi bi-chevron-right"></i></a><?php endif; ?>
  </span>
</div>
<div class="page-head">
  <div class="d-flex align-items-center gap-2"><?= avatar($day['user_name']) ?>
    <h1><?= e($day['user_name']) ?> <span class="text-muted fs-6 fw-normal"><?= e(fmt_date($day['work_date'])) ?></span></h1></div>
  <div class="actions"><?= badge(work_day_statuses(), $day['status']) ?></div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body small d-flex flex-wrap gap-4">
      <span><span class="text-muted"><?= e(t('Started')) ?>:</span> <strong><?= e(fmt_time($day['started_at'])) ?></strong>
        <?php if (is_late_start($day)): ?><span class="badge bg-warning text-dark"><?= e(t('Late start')) ?></span><?php endif; ?><?= location_pin($day, 'start', true) ?></span>
      <span><span class="text-muted"><?= e(t('Closed')) ?>:</span> <strong><?= e(fmt_time($day['closed_at'])) ?></strong><?= location_pin($day, 'close', true) ?></span>
      <?php if ($day['closed_by_name'] && $day['closed_by'] != $day['user_id']): ?><span class="text-muted"><?= e(t('Closed by :name', ['name' => $day['closed_by_name']])) ?></span><?php endif; ?>
      <?php if ($day['reopened_count']): ?><span class="text-muted"><?= e(t('Reopened :n time(s)', ['n' => $day['reopened_count']])) ?></span><?php endif; ?>
    </div></div>
    <?php if ($day['flagged']): ?><div class="alert alert-danger small"><i class="bi bi-flag-fill"></i> <?= e(t($day['flag_reason'] ?? 'Flagged')) ?></div><?php endif; ?>
    <?php if (in_array($day['status'], ['missed', 'leave'], true)): ?>
      <p class="text-muted"><?= e($day['status'] === 'leave' ? t('Marked on leave.') : t('The day was not started.')) ?></p>
    <?php else: ?>
      <?php $editable = false; $dayDate = $day['work_date']; require APP_ROOT . '/views/partials/day_entries.php'; ?>
      <?php if ($totals['blockers']): ?>
        <div class="card border-danger-subtle mb-3"><div class="card-header text-danger"><i class="bi bi-sign-stop"></i> <?= e(t('Blockers')) ?></div>
          <ul class="list-group list-group-flush"><?php foreach ($totals['blockers'] as $b): ?><li class="list-group-item small"><strong><?= e($b['task']) ?>:</strong> <?= e($b['text']) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <?php if ($day['note']): ?><div class="card mb-3"><div class="card-body"><strong><?= e(t('Notes')) ?>:</strong> <span style="white-space:pre-wrap"><?= e($day['note']) ?></span></div></div><?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <?php if ($canManage && in_array($day['status'], ['closed', 'auto_closed'], true)): ?>
      <form method="post" action="<?= e(url('team', ['a' => 'reopen', 'id' => $day['id']])) ?>" class="card mb-3">
        <?= csrf_field() ?>
        <div class="card-header"><i class="bi bi-unlock"></i> <?= e(t('Reopen this day')) ?></div>
        <div class="card-body">
          <p class="small text-muted"><?= e(t('Lets the person add or fix entries and close the day again.')) ?></p>
          <input class="form-control form-control-sm mb-2" name="reason" maxlength="300" required placeholder="<?= e(t('Reason')) ?>">
          <button class="btn btn-sm btn-outline-primary"><?= e(t('Reopen')) ?></button>
        </div>
      </form>
    <?php endif; ?>
    <?php if (in_array($day['status'], ['closed', 'auto_closed'], true)): ?>
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-send"></i> <?= e(t('Summary sent')) ?></div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($notes as $n): ?>
          <li class="list-group-item">
            <i class="bi <?= $n['channel'] === 'teams' ? 'bi-microsoft-teams' : 'bi-envelope' ?>"></i>
            <?= e($n['recipient']) ?> ·
            <?= $n['status'] === 'ok' ? '<span class="text-success">' . e(t('Sent')) . '</span>' : '<span class="text-danger">' . e($n['status'] === 'skipped' ? t('Skipped') : t('Failed')) . '</span>' ?>
            <span class="text-muted"><?= e(fmt_datetime($n['created_at'])) ?></span>
            <?php if ($n['error']): ?><div class="text-danger"><?= e($n['error']) ?></div><?php endif; ?>
          </li>
        <?php endforeach; ?>
        <?php if (!$notes): ?><li class="list-group-item text-muted"><?= e(t('Nothing was sent (no manager set, or email/Teams not set up).')) ?></li><?php endif; ?>
      </ul>
      <?php if ($canManage || can('settings.manage')): ?>
        <div class="card-footer bg-white">
          <form method="post" action="<?= e(url('team', ['a' => 'resend', 'id' => $day['id']])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-link p-0"><?= e(t('Send summary again')) ?></button></form>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="card">
      <div class="card-header"><i class="bi bi-clock-history"></i> <?= e(t('History')) ?></div>
      <div class="card-body">
        <?php $showEntity = false; foreach ($history as $h) { require APP_ROOT . '/views/partials/history_item.php'; } ?>
        <?php if (!$history): ?><p class="small text-muted mb-0"><?= e(t('No history yet.')) ?></p><?php endif; ?>
      </div>
    </div>
  </div>
</div>
