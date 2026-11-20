<?php
$prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
$startLimit = time_setting('remind_start_time', '11:00');
?>
<div class="page-head">
  <h1><?= e(t('Team Day')) ?></h1>
  <div class="actions">
    <form method="get" class="d-flex gap-1 align-items-center">
      <input type="hidden" name="p" value="team">
      <?php if ($deptId): ?><input type="hidden" name="department" value="<?= (int)$deptId ?>"><?php endif; ?>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e(current_url_with(['date' => $prevDate])) ?>" title="<?= e(t('Previous day')) ?>"><i class="bi bi-chevron-left"></i></a>
      <input type="date" class="form-control form-control-sm" name="date" value="<?= e($date) ?>" onchange="this.form.submit()" aria-label="<?= e(t('Date')) ?>">
      <?php if ($date < today()): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(current_url_with(['date' => $nextDate])) ?>" title="<?= e(t('Next day')) ?>"><i class="bi bi-chevron-right"></i></a><?php endif; ?>
      <?php if ($date !== today()): ?><a class="btn btn-sm btn-link" href="<?= e(current_url_with(['date' => today()])) ?>"><?= e(t('Today')) ?></a><?php endif; ?>
    </form>
  </div>
</div>

<?php if (!$workingDay): ?><div class="alert alert-light border small"><i class="bi bi-calendar-x"></i> <?= e(t(':date is not a working day.', ['date' => fmt_date($date)])) ?></div><?php endif; ?>

<!-- Attendance strip -->
<div class="row g-2 mb-3">
  <?php foreach ([
      [t('Working now'), $counts['open'], 'primary', 'bi-play-circle'],
      [t('Closed'), $counts['closed'], 'success', 'bi-check-circle'],
      [t('Not started'), $counts['none'], 'danger', 'bi-x-circle'],
      [t('Auto-closed'), $counts['auto_closed'], 'warning', 'bi-exclamation-circle'],
      [t('On leave'), $counts['leave'], 'secondary', 'bi-airplane'],
      [t('Late start'), $counts['late'], 'warning', 'bi-alarm'],
      [t('Hours logged'), fmt_hours($counts['hours']), 'dark', 'bi-clock'],
      [t('AI time'), $counts['ai'] ? fmt_minutes($counts['ai']) : '0', 'info', 'bi-robot'],
  ] as [$label, $n, $c, $icon]): ?>
    <div class="col-6 col-md-3 col-xl">
      <div class="card h-100"><div class="card-body py-2 px-3">
        <div class="small text-muted text-truncate"><i class="bi <?= $icon ?> text-<?= $c ?>"></i> <?= e($label) ?></div>
        <div dir="auto" class="fs-5 fw-semibold"><?= e((string)$n) ?></div>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>

<form class="row g-2 mb-3 filters js-autosubmit" method="get">
  <input type="hidden" name="p" value="team"><input type="hidden" name="date" value="<?= e($date) ?>">
  <?php if (count($departments) > 1): ?>
  <div class="col-6 col-md-auto">
    <select name="department" class="form-select form-select-sm">
      <option value=""><?= e(t('All departments')) ?></option>
      <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>"<?= selected($deptId, $d['id']) ?>><?= e($d['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
  <div class="col-6 col-md-auto">
    <select name="show" class="form-select form-select-sm">
      <option value="required"<?= selected($show, 'required') ?>><?= e(t('People who log daily')) ?></option>
      <option value="all"<?= selected($show, 'all') ?>><?= e(t('Everyone in my team')) ?></option>
    </select>
  </div>
</form>

<div class="card table-card">
  <table class="table table-hover table-sm">
    <thead class="table-light"><tr>
      <th class="ps-3"><?= e(t('Name')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Start')) ?></th><th><?= e(t('Close')) ?></th>
      <th class="text-end"><?= e(t('Hours')) ?></th><th class="text-end d-none d-md-table-cell"><?= e(t('Entries')) ?></th>
      <th class="text-end d-none d-md-table-cell"><?= e(t('AI time')) ?></th><th class="text-end d-none d-lg-table-cell"><?= e(t('Time saved with AI')) ?></th>
      <th class="text-end"><?= e(t('Blockers')) ?></th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r):
      $late = $r['started_at'] && date('H:i', strtotime($r['started_at'])) > $startLimit; ?>
      <tr>
        <td class="ps-3">
          <div class="d-flex align-items-center gap-2"><?= avatar($r['name'], 'avatar-sm') ?>
            <div class="min-w-0"><?php if ($r['day_id']): ?><a href="<?= e(url('team', ['a' => 'day', 'id' => $r['day_id']])) ?>" class="text-decoration-none fw-semibold"><?= e($r['name']) ?></a><?php else: ?><span class="fw-semibold"><?= e($r['name']) ?></span><?php endif; ?>
              <div class="small text-muted text-truncate"><?= e($r['designation']) ?></div></div></div>
        </td>
        <td class="text-nowrap">
          <?php if ($r['status']): ?><?= badge(work_day_statuses(), $r['status']) ?><?php else: ?><span class="badge bg-light text-danger border"><?= e(t('Not started')) ?></span><?php endif; ?>
          <?php if ($r['flagged']): ?><i class="bi bi-flag-fill text-danger ms-1" title="<?= e($r['flag_reason']) ?>"></i><?php endif; ?>
        </td>
        <td class="text-nowrap<?= $late ? ' text-warning-emphasis fw-semibold' : '' ?>"><?= e(fmt_time($r['started_at'])) ?><?= $late ? ' <i class="bi bi-alarm" title="' . e(t('Late start')) . '"></i>' : '' ?><?= location_pin($r, 'start') ?></td>
        <td class="text-nowrap"><?= e(fmt_time($r['closed_at'])) ?><?= location_pin($r, 'close') ?></td>
        <td class="text-end"><?= e(fmt_hours((float)$r['hours'])) ?></td>
        <td class="text-end d-none d-md-table-cell"><?= (int)$r['entries'] ?></td>
        <td class="text-end d-none d-md-table-cell"><?= $r['ai_minutes'] ? e(fmt_minutes((int)$r['ai_minutes'])) : '—' ?></td>
        <td class="text-end d-none d-lg-table-cell"><?= $r['saved_minutes'] ? e(fmt_minutes((int)$r['saved_minutes'])) : '—' ?></td>
        <td class="text-end<?= $r['blockers'] ? ' text-danger fw-semibold' : '' ?>"><?= (int)$r['blockers'] ?: '—' ?></td>
        <td class="text-end text-nowrap">
          <?php if ((!$r['status'] || in_array($r['status'], ['missed', 'leave'], true)) && can_manage_person_day((int)$r['user_id'])): ?>
            <form method="post" action="<?= e(url('team', ['a' => 'leave'])) ?>" class="d-inline">
              <?= csrf_field() ?><input type="hidden" name="user" value="<?= (int)$r['user_id'] ?>"><input type="hidden" name="date" value="<?= e($date) ?>">
              <button class="btn btn-sm btn-outline-secondary py-0"><?= e($r['status'] === 'leave' ? t('Undo leave') : t('Mark leave')) ?></button>
            </form>
          <?php elseif ($r['day_id']): ?>
            <a class="btn btn-sm btn-outline-primary py-0" href="<?= e(url('team', ['a' => 'day', 'id' => $r['day_id']])) ?>"><?= e(t('View')) ?></a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="10" class="text-center text-muted py-4"><?= e(t('Nobody to show.')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<p class="small text-muted mt-2"><i class="bi bi-alarm"></i> <?= e(t('Late start = started after :time.', ['time' => date('h:i A', strtotime($startLimit))])) ?></p>
