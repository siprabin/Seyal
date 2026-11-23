<?php
$limit = 500;
$exportUrl = fn($fmt) => current_url_with(['export' => $fmt]);
?>
<div class="page-head">
  <h1><?= e(t('Reports')) ?></h1>
  <div class="actions">
    <a class="btn btn-sm btn-success" href="<?= e($exportUrl('xlsx')) ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    <a class="btn btn-sm btn-outline-secondary" href="<?= e($exportUrl('csv')) ?>"><i class="bi bi-filetype-csv"></i> CSV</a>
    <a class="btn btn-sm btn-outline-danger" target="_blank" href="<?= e($exportUrl('print')) ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
  </div>
</div>
<ul class="nav nav-tabs mb-3">
  <?php foreach ($tabs as $k => $label): if ($k === 'performance' && !performance_scope_ids()) continue; ?>
    <li class="nav-item"><a class="nav-link<?= $tab === $k ? ' active' : '' ?>" href="<?= e(url('reports', ['tab' => $k, 'period' => $period, 'from' => $from, 'to' => $to])) ?>"><?= e($label) ?></a></li>
  <?php endforeach; ?>
</ul>
<?php
$hidden = ['p' => 'reports', 'tab' => $tab];
$extraFilters = '';
if ($tab === 'projects') {
    $extraFilters .= '<div class="col-6 col-md-auto"><label class="form-label small mb-1">' . e(t('Projects')) . '</label><select name="status" class="form-select form-select-sm">'
        . '<option value="open"' . selected($extra['status'], 'open') . '>' . e(t('Open projects')) . '</option><option value="all"' . selected($extra['status'], 'all') . '>' . e(t('All')) . '</option></select></div>';
} else {
    $extraFilters .= '<div class="col-6 col-md-auto"><label class="form-label small mb-1">' . e(t('Name')) . '</label><select name="person" class="form-select form-select-sm"><option value="">' . e(t('Everyone')) . '</option>';
    foreach ($peopleRows as $p) {
        $extraFilters .= '<option value="' . (int)$p['id'] . '"' . selected($personId, $p['id']) . '>' . e($p['name']) . '</option>';
    }
    $extraFilters .= '</select></div>';
    if (count($departments) > 1) {
        $extraFilters .= '<div class="col-6 col-md-auto"><label class="form-label small mb-1">' . e(t('Department')) . '</label><select name="department" class="form-select form-select-sm"><option value="">' . e(t('All departments')) . '</option>';
        foreach ($departments as $d) {
            $extraFilters .= '<option value="' . (int)$d['id'] . '"' . selected($deptId, $d['id']) . '>' . e($d['name']) . '</option>';
        }
        $extraFilters .= '</select></div>';
    }
    if ($tab === 'timesheet') {
        $extraFilters .= '<div class="col-6 col-md-auto"><label class="form-label small mb-1">' . e(t('Project')) . '</label><select name="project" class="form-select form-select-sm"><option value="">' . e(t('All')) . '</option>';
        foreach ($extra['projects'] as $p) {
            $extraFilters .= '<option value="' . (int)$p['id'] . '"' . selected($extra['projectId'], $p['id']) . '>' . e($p['name']) . '</option>';
        }
        $extraFilters .= '</select></div>';
    }
}
require APP_ROOT . '/views/partials/period_filter.php';
?>

<?php if ($tab === 'timesheet' && !empty($extra['totals'])): ?>
  <div class="d-flex flex-wrap gap-2 mb-3 small">
    <?php foreach ($extra['totals'] as $name => $h): ?><span class="badge bg-light text-dark border"><?= e($name) ?>: <?= e(fmt_hours($h)) ?></span><?php endforeach; ?>
    <span class="badge bg-primary"><?= e(t('Total')) ?>: <?= e(fmt_hours(array_sum($extra['totals']))) ?></span>
  </div>
<?php endif; ?>

<div class="card table-card">
  <table class="table table-sm table-hover mb-0 small">
    <thead class="table-light"><tr><?php foreach ($headers as $i => $h): ?><th class="<?= in_array($i, $numeric, true) ? 'text-end' : '' ?><?= $i === 0 ? ' ps-3' : '' ?> text-nowrap"><?= e($h) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach (array_slice($data, 0, $limit) as $ri => $r): ?>
      <tr>
        <?php foreach ($r as $i => $cell): ?>
          <td class="<?= in_array($i, $numeric, true) ? 'text-end' : '' ?><?= $i === 0 ? ' ps-3' : '' ?>">
            <?php if ($tab === 'projects' && $i === 6): ?><?= rag_badge($extra['rag'][$ri]) ?>
            <?php elseif ($tab === 'performance' && $i === 2): ?><?= score_badge($cell === '' ? null : (float)$cell) ?>
            <?php else: ?><?= e(is_float($cell) ? rtrim(rtrim(number_format($cell, 2, '.', ''), '0'), '.') : (string)$cell) ?><?php endif; ?>
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    <?php if (!$data): ?><tr><td colspan="<?= count($headers) ?>" class="text-center text-muted py-4"><?= e(t('No data for this selection.')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php if (count($data) > $limit): ?><p class="small text-muted mt-2"><?= e(t('Showing the first :n rows. Export to get all :total rows.', ['n' => $limit, 'total' => count($data)])) ?></p><?php endif; ?>

<?php if ($tab === 'ai' && (!empty($extra['byTool']) || !empty($extra['byPurpose']))): ?>
<div class="row g-3 mt-1">
  <div class="col-md-6"><div class="card table-card"><div class="card-header"><?= e(t('By tool')) ?></div>
    <table class="table table-sm mb-0 small"><thead class="table-light"><tr><th class="ps-3"><?= e(t('Tool')) ?></th><th class="text-end"><?= e(t('Times used')) ?></th><th class="text-end"><?= e(t('AI time')) ?></th><th class="text-end"><?= e(t('Time saved with AI')) ?></th><th class="text-end"><?= e(t('Avg AI quality')) ?></th></tr></thead>
    <tbody><?php foreach ($extra['byTool'] as $r): ?><tr><td class="ps-3"><?= e($r['name']) ?></td><td class="text-end"><?= (int)$r['uses'] ?></td><td class="text-end"><?= e(fmt_minutes((int)$r['minutes'])) ?></td><td class="text-end"><?= e(fmt_minutes((int)$r['saved'])) ?></td><td class="text-end"><?= $r['q'] !== null ? e(number_format((float)$r['q'], 1)) : '—' ?></td></tr><?php endforeach; ?></tbody></table></div></div>
  <div class="col-md-6"><div class="card table-card"><div class="card-header"><?= e(t('By purpose')) ?></div>
    <table class="table table-sm mb-0 small"><thead class="table-light"><tr><th class="ps-3"><?= e(t('Used AI for')) ?></th><th class="text-end"><?= e(t('Times used')) ?></th><th class="text-end"><?= e(t('AI time')) ?></th><th class="text-end"><?= e(t('Time saved with AI')) ?></th></tr></thead>
    <tbody><?php foreach ($extra['byPurpose'] as $r): ?><tr><td class="ps-3"><?= e(ai_purposes()[$r['ai_purpose']] ?? $r['ai_purpose'] ?? '—') ?></td><td class="text-end"><?= (int)$r['uses'] ?></td><td class="text-end"><?= e(fmt_minutes((int)$r['minutes'])) ?></td><td class="text-end"><?= e(fmt_minutes((int)$r['saved'])) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
</div>
<?php endif; ?>
<p class="small text-muted mt-3"><i class="bi bi-info-circle"></i> <?= e(t('PDF opens a print-ready page: choose "Save as PDF" in the print dialog. This keeps every language and right-to-left text exactly as on screen.')) ?></p>
