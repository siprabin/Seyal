<div class="mb-2 small"><a href="<?= e(url('myday')) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('My Day')) ?></a></div>
<div class="page-head"><h1><?= e(t('My past days')) ?></h1></div>
<form class="row g-2 mb-3 align-items-end" method="get">
  <input type="hidden" name="p" value="myday"><input type="hidden" name="a" value="history">
  <div class="col-6 col-md-auto"><label class="form-label small mb-1" for="from"><?= e(t('From')) ?></label><input type="date" class="form-control form-control-sm" id="from" name="from" value="<?= e($from) ?>"></div>
  <div class="col-6 col-md-auto"><label class="form-label small mb-1" for="to"><?= e(t('To')) ?></label><input type="date" class="form-control form-control-sm" id="to" name="to" value="<?= e($to) ?>"></div>
  <div class="col-auto"><button class="btn btn-sm btn-primary"><?= e(t('Show')) ?></button></div>
</form>
<?php
$sum = ['hours' => 0, 'ai' => 0, 'saved' => 0];
foreach ($days as $d) {
    $sum['hours'] += (float)$d['hours'];
    $sum['ai'] += (int)$d['ai_minutes'];
    $sum['saved'] += (int)$d['saved_minutes'];
}
?>
<div class="card table-card">
  <table class="table table-hover table-sm">
    <thead class="table-light"><tr>
      <th class="ps-3"><?= e(t('Date')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Start')) ?></th><th><?= e(t('Close')) ?></th>
      <th class="text-end"><?= e(t('Hours')) ?></th><th class="text-end d-none d-md-table-cell"><?= e(t('AI time')) ?></th><th class="text-end d-none d-md-table-cell"><?= e(t('Time saved with AI')) ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($days as $d): ?>
      <tr>
        <td class="ps-3"><a href="<?= e(url('myday', ['date' => $d['work_date']])) ?>" class="text-decoration-none"><?= e(fmt_date($d['work_date'])) ?></a> <span class="small text-muted"><?= e(t(date('D', strtotime($d['work_date'])))) ?></span></td>
        <td><?= badge(work_day_statuses(), $d['status']) ?><?php if ($d['flagged']): ?> <i class="bi bi-flag-fill text-danger" title="<?= e($d['flag_reason']) ?>"></i><?php endif; ?></td>
        <td><?= e(fmt_time($d['started_at'])) ?></td>
        <td><?= e(fmt_time($d['closed_at'])) ?></td>
        <td class="text-end"><?= e(fmt_hours((float)$d['hours'])) ?></td>
        <td class="text-end d-none d-md-table-cell"><?= $d['ai_minutes'] ? e(fmt_minutes((int)$d['ai_minutes'])) : '—' ?></td>
        <td class="text-end d-none d-md-table-cell"><?= $d['saved_minutes'] ? e(fmt_minutes((int)$d['saved_minutes'])) : '—' ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$days): ?><tr><td colspan="7" class="text-center text-muted py-4"><?= e(t('Nothing here.')) ?></td></tr><?php endif; ?>
    </tbody>
    <?php if ($days): ?>
    <tfoot class="table-light fw-semibold"><tr>
      <td class="ps-3" colspan="4"><?= e(t('Total')) ?></td>
      <td class="text-end"><?= e(fmt_hours($sum['hours'])) ?></td>
      <td class="text-end d-none d-md-table-cell"><?= e(fmt_minutes($sum['ai'])) ?></td>
      <td class="text-end d-none d-md-table-cell"><?= e(fmt_minutes($sum['saved'])) ?></td>
    </tr></tfoot>
    <?php endif; ?>
  </table>
</div>
