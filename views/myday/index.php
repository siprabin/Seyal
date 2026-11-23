<?php
$isToday = $date === today();
$status = $day['status'] ?? null;
$src = $edit ?? [];
$v = function ($k, $d = '') use ($src) {
    return old($k, $src[$k] ?? $d);
};
$selTask = (string)$v('task_id', $presetTask ?? '');
$aiOn = (bool)$v('ai_used', 0);
$selTools = array_map('intval', (array)old('tools', array_keys($src['tools'] ?? [])));
$taskOption = function ($t) use ($selTask) {
    return '<option value="' . (int)$t['id'] . '"' . selected($selTask, $t['id']) . '>'
        . e($t['title']) . ' — ' . e($t['project_name']) . ($t['status'] === 'done' ? ' ✓' : '') . '</option>';
};
?>
<div class="page-head">
  <h1><?= e(t('My Day')) ?> <span class="text-muted fs-6 fw-normal"><?= e(fmt_date($date)) ?><?= $isToday ? ' · ' . e(t('Today')) : '' ?></span></h1>
  <div class="actions">
    <a href="<?= e(url('myday', ['a' => 'history'])) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar3"></i> <?= e(t('My past days')) ?></a>
  </div>
</div>

<?php foreach ($otherOpen as $o): ?>
  <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2">
    <i class="bi bi-unlock"></i> <?= e(t('Your day for :date is open again. Add any missing work and close it.', ['date' => fmt_date($o['work_date'])])) ?>
    <a class="btn btn-sm btn-warning ms-auto" href="<?= e(url('myday', ['date' => $o['work_date']])) ?>"><?= e(t('Open that day')) ?></a>
  </div>
<?php endforeach; ?>

<?php if (!$day): ?>
  <!-- Not started yet -->
  <div class="card text-center py-4 mb-3">
    <div class="card-body">
      <div class="display-6 fw-semibold mb-1" id="clock"><?= e(date('h:i A')) ?></div>
      <p class="text-muted mb-4"><?= e(fmt_date(today())) ?>
        <?php if (!$workingDay): ?><br><span class="badge bg-light text-dark border"><?= e($holiday ? t('Holiday: :name', ['name' => $holiday ?: '']) : t('Not a working day')) ?></span><?php endif; ?></p>
      <form method="post" action="<?= e(url('myday', ['a' => 'start'])) ?>"<?= location_form_attr() ?>>
        <?= csrf_field() ?>
        <button class="btn btn-success btn-lg px-5"><i class="bi bi-play-circle"></i> <?= e(t('Start Day')) ?></button>
      </form>
      <p class="small text-muted mt-3 mb-0"><?= e(t('Click when you begin work. Your start time is recorded.')) ?></p>
      <?php if (location_mode() !== 'off'): ?><p class="small text-muted mb-0"><i class="bi bi-geo-alt"></i> <?= e(t('Your location is recorded when you start and close your day.')) ?></p><?php endif; ?>
    </div>
  </div>

<?php elseif (in_array($status, ['missed', 'leave'], true)): ?>
  <div class="card"><div class="card-body">
    <?= badge(work_day_statuses(), $status) ?>
    <p class="mb-0 mt-2 text-muted"><?= e($status === 'leave' ? t('You were marked on leave for this day.') : t('You did not start your day on this date.')) ?></p>
  </div></div>

<?php elseif ($status !== 'open'): ?>
  <!-- Closed -->
  <div class="card mb-3 border-success-subtle">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
      <i class="bi bi-check-circle-fill text-success fs-2"></i>
      <div>
        <div class="fw-semibold"><?= e($status === 'auto_closed' ? t('This day was closed automatically') : t('Day closed')) ?></div>
        <div class="small text-muted"><?= e(t('Started')) ?> <?= e(fmt_time($day['started_at'])) ?> · <?= e(t('Closed')) ?> <?= e(fmt_time($day['closed_at'])) ?></div>
      </div>
      <?= badge(work_day_statuses(), $status) ?>
    </div>
  </div>
  <?php $editable = false; $dayDate = $date; require APP_ROOT . '/views/partials/day_entries.php'; ?>
  <?php if ($day['note']): ?><div class="card"><div class="card-body"><strong><?= e(t('Notes')) ?>:</strong> <span style="white-space:pre-wrap"><?= e($day['note']) ?></span></div></div><?php endif; ?>

<?php else: ?>
  <!-- Open day -->
  <div class="card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
      <span class="pulse-dot"></span>
      <div>
        <div class="fw-semibold"><?= e(t('Working since :time', ['time' => fmt_time($day['started_at'])])) ?>
          <?php if (is_late_start($day)): ?><span class="badge bg-warning text-dark ms-1"><?= e(t('Late start')) ?></span><?php endif; ?></div>
        <div class="small text-muted"><?= e(t('Log each piece of work below, then close your day.')) ?></div>
      </div>
      <a href="<?= e(url('myday', ['a' => 'close', 'date' => $date])) ?>" class="btn btn-danger ms-auto"><i class="bi bi-stop-circle"></i> <?= e(t('Close Day')) ?></a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-5 order-lg-2">
      <form method="post" action="<?= e(url('myday', ['a' => 'save_entry'])) ?>" class="card entry-form" id="entry-form">
        <?= csrf_field() ?>
        <input type="hidden" name="date" value="<?= e($date) ?>">
        <?php if ($edit): ?><input type="hidden" name="log_id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="card-header d-flex align-items-center">
          <i class="bi bi-plus-circle me-2"></i><?= e($edit ? t('Edit entry') : t('Log work')) ?>
          <?php if ($edit): ?><a href="<?= e(url('myday', array_filter(['date' => $isToday ? null : $date]))) ?>" class="ms-auto small"><?= e(t('Cancel')) ?></a><?php endif; ?>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="task_id"><?= e(t('Task')) ?></label>
            <select class="form-select" id="task_id" name="task_id">
              <option value=""><?= e(t('Other work (not a task)')) ?></option>
              <?php if ($myTasks): ?><optgroup label="<?= e(t('My tasks')) ?>"><?php foreach ($myTasks as $tk) echo $taskOption($tk); ?></optgroup><?php endif; ?>
              <?php if ($otherTasks): ?><optgroup label="<?= e(t('Other tasks in my projects')) ?>"><?php foreach ($otherTasks as $tk) echo $taskOption($tk); ?></optgroup><?php endif; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="description"><?= e(t('What did you do?')) ?></label>
            <textarea class="form-control" id="description" name="description" rows="2" placeholder="<?= e(t('Short description (required for other work)')) ?>"><?= e($v('description')) ?></textarea>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-12">
              <label class="form-label" for="hours_h"><?= e(t('Time spent')) ?> <span class="text-danger">*</span></label>
              <?php $durName = 'hours'; $durMinutes = $src ? (int)round((float)$src['hours'] * 60) : null; $durMaxHours = 24;
              $durQuick = [15, 30, 45, 60, 90, 120, 240]; $durSmall = false; $durLabelId = 'hours_h';
              require APP_ROOT . '/views/partials/duration_input.php'; ?>
            </div>
            <div class="col-12">
              <label class="form-label" for="task_status"><?= e(t('Task status now')) ?></label>
              <select class="form-select" id="task_status" name="task_status">
                <option value=""><?= e(t('Keep as is')) ?></option>
                <?php foreach (task_statuses() as $k => $s): ?><option value="<?= e($k) ?>"<?= selected(old('task_status'), $k) ?>><?= e($s['label']) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="ai-box rounded border p-2 mb-3">
            <div class="form-check form-switch m-1">
              <input class="form-check-input" type="checkbox" role="switch" id="ai_used" name="ai_used" value="1"<?= checked($aiOn) ?> data-toggle-target="#ai-fields">
              <label class="form-check-label fw-semibold" for="ai_used"><i class="bi bi-robot"></i> <?= e(t('I used AI for this work')) ?></label>
            </div>
            <div id="ai-fields" class="px-1 pt-2">
              <div class="mb-2">
                <div class="form-label small mb-1"><?= e(t('Which tools?')) ?></div>
                <div class="d-flex flex-wrap gap-1">
                  <?php foreach ($tools as $tool): ?>
                    <input type="checkbox" class="btn-check" name="tools[]" id="tool<?= (int)$tool['id'] ?>" value="<?= (int)$tool['id'] ?>"<?= checked(in_array((int)$tool['id'], $selTools, true)) ?>>
                    <label class="btn btn-sm btn-outline-primary" for="tool<?= (int)$tool['id'] ?>"><?= e($tool['name']) ?></label>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="row g-2 mb-2">
                <div class="col-sm-6">
                  <label class="form-label small mb-1" for="ai_minutes_h"><?= e(t('Time using AI')) ?></label>
                  <?php $durName = 'ai_minutes'; $durMinutes = $src && $src['ai_minutes'] ? (int)$src['ai_minutes'] : null; $durMaxHours = 24;
                  $durQuick = []; $durSmall = true; $durLabelId = 'ai_minutes_h';
                  require APP_ROOT . '/views/partials/duration_input.php'; ?>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small mb-1" for="time_saved_minutes_h"><?= e(t('Time saved')) ?></label>
                  <?php $durName = 'time_saved_minutes'; $durMinutes = $src && $src['time_saved_minutes'] ? (int)$src['time_saved_minutes'] : null; $durMaxHours = 24;
                  $durQuick = []; $durSmall = true; $durLabelId = 'time_saved_minutes_h';
                  require APP_ROOT . '/views/partials/duration_input.php'; ?>
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label small mb-1" for="ai_purpose"><?= e(t('Used AI for')) ?></label>
                <select class="form-select form-select-sm" id="ai_purpose" name="ai_purpose">
                  <option value="">—</option>
                  <?php foreach (ai_purposes() as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($v('ai_purpose'), $k) ?>><?= e($label) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div>
                <div class="form-label small mb-1"><?= e(t('Quality of the AI output')) ?></div>
                <div class="btn-group btn-group-sm flex-wrap" role="group">
                  <?php foreach (quality_labels() as $n => $label): ?>
                    <input type="radio" class="btn-check" name="output_quality" id="q<?= $n ?>" value="<?= $n ?>"<?= checked((int)$v('output_quality') === $n) ?>>
                    <label class="btn btn-outline-secondary" for="q<?= $n ?>" title="<?= e($label) ?>"><?= $n ?> <span class="d-none d-xl-inline small"><?= e($label) ?></span></label>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label" for="blockers"><?= e(t('Blockers (if any)')) ?></label>
            <textarea class="form-control" id="blockers" name="blockers" rows="2" placeholder="<?= e(t('Anything stopping you? Your manager will see this.')) ?>"><?= e($v('blockers')) ?></textarea>
          </div>
        </div>
        <div class="card-footer bg-white">
          <button class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> <?= e($edit ? t('Save changes') : t('Add entry')) ?></button>
        </div>
      </form>
    </div>
    <div class="col-lg-7 order-lg-1">
      <?php $editable = true; $dayDate = $date; require APP_ROOT . '/views/partials/day_entries.php'; ?>
    </div>
  </div>
<?php endif; ?>
