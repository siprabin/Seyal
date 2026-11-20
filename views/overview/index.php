<?php
$attCounts = ['open' => 0, 'closed' => 0, 'none' => 0, 'leave' => 0, 'auto' => 0];
foreach ($attendance as $r) {
    $k = match ($r['status']) { 'open' => 'open', 'closed' => 'closed', 'leave' => 'leave', 'auto_closed' => 'auto', default => 'none' };
    $attCounts[$k]++;
}
$aiChart = [
    'type' => 'bar',
    'data' => [
        'labels' => array_column($weeks, 'label'),
        'datasets' => [
            ['label' => t('AI time (hours)'), 'data' => array_map(fn($w) => round($w['ai_minutes'] / 60, 1), $weeks), 'backgroundColor' => '#6f42c1'],
            ['label' => t('Time saved (hours)'), 'data' => array_map(fn($w) => round($w['saved_minutes'] / 60, 1), $weeks), 'backgroundColor' => '#20c997'],
        ],
    ],
    'options' => ['scales' => ['y' => ['beginAtZero' => true]], 'plugins' => ['legend' => ['position' => 'bottom']]],
];
$toolChart = [
    'type' => 'doughnut',
    'data' => [
        'labels' => array_column($tools, 'name'),
        'datasets' => [['data' => array_map('intval', array_column($tools, 'uses')), 'backgroundColor' => ['#0d6efd', '#6f42c1', '#d63384', '#fd7e14', '#20c997', '#0dcaf0', '#ffc107', '#6c757d']]],
    ],
    'options' => ['plugins' => ['legend' => ['position' => 'bottom']], 'cutout' => '60%'],
];
?>
<div class="page-head">
  <h1><?= e(t('Project Pulse')) ?></h1>
  <div class="actions small text-muted align-self-center"><?= e(fmt_date(today())) ?></div>
</div>

<!-- KPI tiles -->
<div class="row g-2 mb-3">
  <?php foreach ([
      [t('On track'), $ragCounts['green'] ?? 0, 'text-success', 'bi-check-circle'],
      [t('At risk'), $ragCounts['amber'] ?? 0, 'text-warning', 'bi-exclamation-circle'],
      [t('Off track'), $ragCounts['red'] ?? 0, 'text-danger', 'bi-x-circle'],
      [t('Overdue tasks'), $overdueTotal, $overdueTotal ? 'text-danger' : '', 'bi-alarm'],
      [t('Working now'), $attCounts['open'] . ' / ' . count($attendance), 'text-primary', 'bi-people'],
      [t('AI time this week'), fmt_minutes((int)$thisWeek['ai_minutes']), '', 'bi-robot'],
      [t('Time saved this week'), fmt_minutes((int)$thisWeek['saved_minutes']), 'text-success', 'bi-lightning-charge'],
  ] as [$label, $v, $cls, $icon]): ?>
    <div class="col-6 col-md-4 col-xl">
      <div class="metric"><div class="l text-truncate"><i class="bi <?= $icon ?>"></i> <?= e($label) ?></div><div dir="auto" class="v <?= $cls ?>"><?= e((string)$v) ?></div></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Projects -->
<div class="d-flex align-items-center mb-2">
  <h2 class="h6 mb-0"><?= e(t('Projects')) ?> <span class="badge bg-light text-dark"><?= count($projects) ?></span></h2>
  <a class="ms-auto small" href="<?= e(url('reports', ['tab' => 'projects'])) ?>"><?= e(t('Project status report')) ?></a>
</div>
<?php if (!$projects): ?><div class="card mb-3"><div class="card-body text-muted"><?= e(t('No active projects.')) ?></div></div><?php endif; ?>
<div class="row g-3 mb-3">
  <?php foreach ($projects as $p): $h = $health[(int)$p['id']];
    $late = $h['forecast'] && $p['target_date'] && $h['forecast'] > $p['target_date']; ?>
    <div class="col-md-6 col-xl-4">
      <a href="<?= e(url('projects', ['a' => 'view', 'id' => $p['id']])) ?>" class="card project-card rag-border-<?= e($h['rag']) ?> h-100 text-decoration-none text-body">
        <div class="card-body">
          <div class="d-flex align-items-start gap-2 mb-1">
            <div class="fw-semibold flex-grow-1 min-w-0 text-truncate"><?= e($p['name']) ?></div>
            <?= rag_badge($h['rag'], $h['reason']) ?>
          </div>
          <div class="small text-muted text-truncate mb-2"><?= e($p['client_name'] ?: t('Internal')) ?><?= $p['manager_name'] ? ' · ' . e($p['manager_name']) : '' ?></div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="flex-grow-1"><?= progress_bar($h['pct']) ?></div><strong class="small"><?= $h['pct'] ?>%</strong>
          </div>
          <div class="row small g-0">
            <div class="col-6"><div class="text-muted"><?= e(t('Target')) ?></div><div><?= e(fmt_date($p['target_date']) ?: '—') ?></div></div>
            <div class="col-6"><div class="text-muted"><?= e(t('Forecast')) ?></div><div class="<?= $late ? 'text-overdue' : '' ?>"><?= e($h['forecast'] ? fmt_date($h['forecast']) : t('Unknown')) ?></div></div>
          </div>
          <div class="small text-muted mt-2"><i class="bi bi-info-circle"></i> <?= e($h['reason']) ?>
            <?php if ($h['overdue']): ?> · <span class="text-overdue"><?= e(t(':n overdue', ['n' => $h['overdue']])) ?></span><?php endif; ?></div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <!-- Attendance strip -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex"><?= e(t('Today\'s attendance')) ?><a class="ms-auto small" href="<?= e(url('team')) ?>"><?= e(t('Team Day')) ?></a></div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-3 small mb-3">
          <span><span class="fw-semibold text-primary"><?= $attCounts['open'] ?></span> <?= e(t('working')) ?></span>
          <span><span class="fw-semibold text-success"><?= $attCounts['closed'] ?></span> <?= e(t('closed')) ?></span>
          <span><span class="fw-semibold text-warning"><?= $attCounts['auto'] ?></span> <?= e(t('Auto-closed')) ?></span>
          <span><span class="fw-semibold text-danger"><?= $attCounts['none'] ?></span> <?= e(t('not started')) ?></span>
          <span><span class="fw-semibold text-secondary"><?= $attCounts['leave'] ?></span> <?= e(t('on leave')) ?></span>
        </div>
        <div class="att-strip">
          <?php foreach ($attendance as $r):
            $k = match ($r['status']) { 'open' => 'open', 'closed' => 'closed', 'leave' => 'leave', 'auto_closed' => 'auto', default => 'none' };
            $tip = $r['name'] . ' — ' . digest_status_label($r) . ($r['started_at'] ? ' · ' . fmt_time($r['started_at']) : ''); ?>
            <a class="att-<?= $k ?>" href="<?= e($r['day_id'] ? url('team', ['a' => 'day', 'id' => $r['day_id']]) : url('team')) ?>" title="<?= e($tip) ?>"><?= avatar($r['name']) ?></a>
          <?php endforeach; ?>
          <?php if (!$attendance): ?><span class="text-muted small"><?= e(t('Nobody to show.')) ?></span><?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Overdue tasks -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><?= e(t('Overdue tasks')) ?> <span class="badge bg-danger"><?= $overdueTotal ?></span></div>
      <?php if (!$overdue): ?>
        <div class="card-body text-muted small"><?= e(t('Nothing overdue. 👍')) ?></div>
      <?php else: ?>
      <div class="table-card" style="max-height:320px;overflow-y:auto">
        <table class="table table-sm mb-0 small">
          <tbody>
          <?php foreach ($overdue as $tk): $days = (int)round((strtotime(today()) - strtotime($tk['due_date'])) / 86400); ?>
            <tr>
              <td class="ps-3"><a href="<?= e(url('tasks', ['a' => 'view', 'id' => $tk['id']])) ?>" class="text-decoration-none"><?= e($tk['title']) ?></a>
                <div class="text-muted"><?= e($tk['project_name']) ?></div></td>
              <td class="text-nowrap"><?= avatar($tk['assignee_name'], 'avatar-sm') ?> <span class="d-none d-md-inline"><?= e($tk['assignee_name'] ?? t('Unassigned')) ?></span></td>
              <td class="text-nowrap text-overdue text-end pe-3"><?= e(days_late_label((int)$days)) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- AI usage -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex"><?= e(t('Weekly AI usage')) ?><a class="ms-auto small" href="<?= e(url('reports', ['tab' => 'ai'])) ?>"><?= e(t('AI usage report')) ?></a></div>
      <div class="card-body"><div class="chart-box"><canvas data-chart="<?= e(json_encode($aiChart, JSON_UNESCAPED_UNICODE)) ?>" aria-label="<?= e(t('Weekly AI usage')) ?>"></canvas></div></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><?= e(t('AI tools this week')) ?></div>
      <div class="card-body">
        <?php if ($tools): ?>
          <div class="chart-box" style="height:200px"><canvas data-chart="<?= e(json_encode($toolChart, JSON_UNESCAPED_UNICODE)) ?>" aria-label="<?= e(t('AI tools this week')) ?>"></canvas></div>
        <?php else: ?><p class="text-muted small"><?= e(t('No AI usage logged this week yet.')) ?></p><?php endif; ?>
        <?php if ($topAi): ?>
          <div class="small fw-semibold mt-3 mb-1"><?= e(t('Most time saved this week')) ?></div>
          <ul class="list-unstyled small mb-0">
            <?php foreach ($topAi as $a): ?><li class="d-flex gap-2 py-1"><?= avatar($a['name'], 'avatar-sm') ?><span class="flex-grow-1 text-truncate"><?= e($a['name']) ?></span><span class="text-success"><?= e(fmt_minutes((int)$a['saved'])) ?></span></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
