<?php
$prev = date('Y-m-d', strtotime($week . ' -7 days'));
$next = date('Y-m-d', strtotime($week . ' +7 days'));
?>
<div class="page-head">
  <h1><?= e(t('Weekly reviews')) ?></h1>
  <div class="actions">
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('reviews', ['week' => $prev])) ?>" title="<?= e(t('Previous week')) ?>"><i class="bi bi-chevron-left"></i></a>
    <span class="align-self-center small fw-semibold ltr"><?= e(fmt_date($week)) ?> – <?= e(fmt_date($weekEnd)) ?></span>
    <?php if ($next <= today()): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('reviews', ['week' => $next])) ?>" title="<?= e(t('Next week')) ?>"><i class="bi bi-chevron-right"></i></a><?php endif; ?>
  </div>
</div>
<p class="small text-muted"><?= e(t('Review each person who reports to you once a week. The rating is only visible to managers; you can choose to share your comments with the person.')) ?></p>
<div class="card table-card">
  <table class="table table-hover table-sm mb-0">
    <thead class="table-light"><tr>
      <th class="ps-3"><?= e(t('Name')) ?></th><th class="text-end"><?= e(t('Hours')) ?></th><th class="text-end"><?= e(t('Tasks done')) ?></th>
      <th class="text-end d-none d-md-table-cell"><?= e(t('Avg quality')) ?></th><th class="text-end d-none d-md-table-cell"><?= e(t('On time')) ?></th>
      <th class="text-end d-none d-lg-table-cell"><?= e(t('Days worked')) ?></th><th class="text-end d-none d-lg-table-cell"><?= e(t('Late start')) ?></th>
      <th><?= e(t('Review')) ?></th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($reports as $p): $m = $p['m']; $rv = $existing[(int)$p['id']] ?? null; ?>
      <tr>
        <td class="ps-3"><div class="d-flex align-items-center gap-2"><?= avatar($p['name'], 'avatar-sm') ?><div><div class="fw-semibold"><?= e($p['name']) ?></div><div class="small text-muted"><?= e($p['designation']) ?></div></div></div></td>
        <td class="text-end"><?= e(fmt_hours($m['hours'])) ?></td>
        <td class="text-end"><?= (int)$m['tasks_done'] ?></td>
        <td class="text-end d-none d-md-table-cell"><?= $m['avg_quality'] !== null ? e(number_format($m['avg_quality'], 1)) : '—' ?></td>
        <td class="text-end d-none d-md-table-cell"><?= $m['on_time_pct'] !== null ? e($m['on_time_pct'] . '%') : '—' ?></td>
        <td class="text-end d-none d-lg-table-cell"><?= (int)$m['days_worked'] ?>/<?= (int)$m['working_days'] ?></td>
        <td class="text-end d-none d-lg-table-cell<?= $m['late_starts'] ? ' text-warning-emphasis' : '' ?>"><?= (int)$m['late_starts'] ?></td>
        <td><?= $rv ? rating_stars((int)$rv['rating']) . ($rv['shared'] ? ' <i class="bi bi-share text-muted" title="' . e(t('Shared with the employee')) . '"></i>' : '') : '<span class="badge bg-warning text-dark">' . e(t('Pending')) . '</span>' ?></td>
        <td class="text-end pe-3"><a class="btn btn-sm <?= $rv ? 'btn-outline-secondary' : 'btn-primary' ?> py-0" href="<?= e(url('reviews', ['a' => 'edit', 'user' => $p['id'], 'week' => $week])) ?>"><?= e($rv ? t('Edit') : t('Write review')) ?></a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$reports): ?><tr><td colspan="9" class="text-center text-muted py-4"><?= e(t('Nobody reports to you directly.')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
