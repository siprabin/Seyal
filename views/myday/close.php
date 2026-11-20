<div class="mb-2 small"><a href="<?= e(url('myday', ['date' => $day['work_date']])) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('My Day')) ?></a></div>
<div class="page-head">
  <h1><?= e(t('Close Day')) ?> <span class="text-muted fs-6 fw-normal"><?= e(fmt_date($day['work_date'])) ?></span></h1>
</div>
<div class="alert alert-info small">
  <i class="bi bi-info-circle"></i>
  <?= e(t('Please check your summary. When you confirm, the day is locked and the summary is sent to :who.', [
      'who' => $recipient ? $recipient['name'] : t('your manager (none set — ask an admin to set "Reports to")'),
  ])) ?>
  <?php if (setting('teams_post_close', '1') === '1' && teams_configured()): ?> <?= e(t('It is also posted to the Teams channel.')) ?><?php endif; ?>
</div>
<div class="card mb-3">
  <div class="card-body">
    <div class="small text-muted mb-3"><?= e(t('Started')) ?> <strong><?= e(fmt_time($day['started_at'])) ?></strong> · <?= e(t('Closing at')) ?> <strong><?= e(date('h:i A')) ?></strong></div>
    <?php $editable = false; $dayDate = $day['work_date']; require APP_ROOT . '/views/partials/day_entries.php'; ?>
    <?php if ($totals['hours'] > 0 && $totals['hours'] < 4): ?>
      <div class="alert alert-warning small py-2"><i class="bi bi-exclamation-triangle"></i> <?= e(t('Only :h hours logged. Did you forget an entry?', ['h' => fmt_hours($totals['hours'])])) ?></div>
    <?php endif; ?>
  </div>
</div>
<form method="post" action="<?= e(url('myday', ['a' => 'close', 'date' => $day['work_date']])) ?>" class="card"<?= location_form_attr() ?>>
  <?= csrf_field() ?>
  <div class="card-body">
    <label class="form-label" for="note"><?= e(t('Note for your manager (optional)')) ?><?= $entries ? '' : ' <span class="text-danger">*</span>' ?></label>
    <textarea class="form-control" id="note" name="note" rows="3" placeholder="<?= e(t('e.g. plan for tomorrow, anything to flag')) ?>"<?= $entries ? '' : ' required' ?>><?= e($day['note']) ?></textarea>
  </div>
  <div class="card-footer bg-white d-flex flex-wrap gap-2">
    <button class="btn btn-danger"><i class="bi bi-lock"></i> <?= e(t('Confirm and close my day')) ?></button>
    <a href="<?= e(url('myday', ['date' => $day['work_date']])) ?>" class="btn btn-outline-secondary"><?= e(t('Back — add more work')) ?></a>
    <?php if (location_mode() !== 'off'): ?><span class="small text-muted align-self-center ms-auto"><i class="bi bi-geo-alt"></i> <?= e(t('Your location is recorded when you start and close your day.')) ?></span><?php endif; ?>
  </div>
</form>
