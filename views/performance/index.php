<div class="page-head">
  <h1><?= e(t('Performance')) ?></h1>
  <div class="actions">
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('performance', ['a' => 'mine'])) ?>"><i class="bi bi-person"></i> <?= e(t('My performance')) ?></a>
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('reports', ['tab' => 'performance', 'period' => $period, 'from' => $from, 'to' => $to])) ?>"><i class="bi bi-download"></i> <?= e(t('Export')) ?></a>
  </div>
</div>
<?php
$hidden = ['p' => 'performance', 'sort' => $sort];
$extraFilters = '';
if (count($departments) > 1) {
    $extraFilters = '<div class="col-6 col-md-auto"><label class="form-label small mb-1" for="department">' . e(t('Department')) . '</label><select name="department" id="department" class="form-select form-select-sm"><option value="">' . e(t('All departments')) . '</option>';
    foreach ($departments as $d) {
        $extraFilters .= '<option value="' . (int)$d['id'] . '"' . selected($deptId, $d['id']) . '>' . e($d['name']) . '</option>';
    }
    $extraFilters .= '</select></div>';
}
require APP_ROOT . '/views/partials/period_filter.php';
$sortLink = fn($k, $label) => '<a class="text-decoration-none text-body' . ($sort === $k ? ' fw-bold' : '') . '" href="' . e(current_url_with(['sort' => $k])) . '">' . e($label) . ($sort === $k ? ' ↓' : '') . '</a>';
?>
<div class="alert alert-light border small"><i class="bi bi-calculator"></i>
  <?= e(t('Score = Σ(weightage × quality × on-time) ÷ Σ(weightage × 5) × 100. Late tasks count at :f of their value.', ['f' => rtrim(rtrim(number_format(late_factor(), 2), '0'), '.')])) ?></div>
<div class="card table-card">
  <table class="table table-hover table-sm mb-0">
    <thead class="table-light"><tr>
      <th class="ps-3"><?= $sortLink('name', t('Name')) ?></th>
      <th class="text-center"><?= $sortLink('score', t('Score')) ?></th>
      <th class="text-end"><?= e(t('Rated tasks')) ?></th>
      <th class="text-end d-none d-md-table-cell"><?= e(t('Avg quality')) ?></th>
      <th class="text-end d-none d-md-table-cell"><?= e(t('On time')) ?></th>
      <th class="text-end"><?= $sortLink('hours', t('Hours')) ?></th>
      <th class="text-end d-none d-lg-table-cell"><?= e(t('Time saved with AI')) ?></th>
      <th class="text-end d-none d-lg-table-cell"><?= e(t('Days worked')) ?></th>
      <th class="text-end d-none d-xl-table-cell"><?= e(t('Weekly review')) ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($people as $p): $m = $p['m'];
      $delta = ($m['score'] !== null && $p['prev'] !== null) ? $m['score'] - $p['prev'] : null; ?>
      <tr>
        <td class="ps-3"><a href="<?= e(url('performance', ['a' => 'person', 'id' => $p['id'], 'period' => $period, 'from' => $from, 'to' => $to])) ?>" class="text-decoration-none d-flex align-items-center gap-2">
          <?= avatar($p['name'], 'avatar-sm') ?><span><span class="fw-semibold"><?= e($p['name']) ?></span><span class="d-block small text-muted"><?= e($p['designation'] ?: $p['department_name']) ?></span></span></a></td>
        <td class="text-center text-nowrap"><?= score_badge($m['score']) ?>
          <?php if ($delta !== null && abs($delta) >= 0.5): ?><span class="small <?= $delta > 0 ? 'text-success' : 'text-danger' ?>" title="<?= e(t('Change vs previous period')) ?>"><?= $delta > 0 ? '▲' : '▼' ?><?= e(number_format(abs($delta), 1)) ?></span><?php endif; ?></td>
        <td class="text-end"><?= (int)$m['tasks_rated'] ?><span class="text-muted small"> / <?= (int)$m['tasks_done'] ?></span></td>
        <td class="text-end d-none d-md-table-cell"><?= $m['avg_quality'] !== null ? e(number_format($m['avg_quality'], 1)) : '—' ?></td>
        <td class="text-end d-none d-md-table-cell"><?= $m['on_time_pct'] !== null ? e($m['on_time_pct'] . '%') : '—' ?></td>
        <td class="text-end"><?= e(fmt_hours($m['hours'])) ?></td>
        <td class="text-end d-none d-lg-table-cell"><?= $m['saved_minutes'] ? e(fmt_minutes($m['saved_minutes'])) : '—' ?></td>
        <td class="text-end d-none d-lg-table-cell"><?= (int)$m['days_worked'] ?>/<?= (int)$m['working_days'] ?></td>
        <td class="text-end d-none d-xl-table-cell"><?= $m['review_avg'] !== null ? rating_stars((int)round($m['review_avg'])) : '—' ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$people): ?><tr><td colspan="9" class="text-center text-muted py-4"><?= e(t('Nobody to show.')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<p class="small text-muted mt-2"><?= e(t('Rated tasks shows rated / finished. Unrated tasks do not count towards the score — rate them under Task ratings.')) ?></p>
