<?php
$trendChart = [
    'type' => 'line',
    'data' => [
        'labels' => array_column($trend, 'label'),
        'datasets' => [
            ['label' => t('Score'), 'data' => array_column($trend, 'score'), 'borderColor' => '#0d6efd', 'backgroundColor' => '#0d6efd', 'tension' => .3, 'spanGaps' => true],
            ['label' => t('On time') . ' %', 'data' => array_column($trend, 'on_time'), 'borderColor' => '#20c997', 'backgroundColor' => '#20c997', 'borderDash' => [5, 4], 'tension' => .3, 'spanGaps' => true],
        ],
    ],
    'options' => ['scales' => ['y' => ['min' => 0, 'max' => 100]], 'plugins' => ['legend' => ['position' => 'bottom']]],
];
$hoursChart = [
    'type' => 'bar',
    'data' => [
        'labels' => array_column($weeks, 'label'),
        'datasets' => [
            ['label' => t('Hours'), 'data' => array_map(fn($w) => round($w['hours'], 1), $weeks), 'backgroundColor' => '#0d6efd'],
            ['label' => t('AI time (hours)'), 'data' => array_map(fn($w) => round($w['ai_minutes'] / 60, 1), $weeks), 'backgroundColor' => '#6f42c1'],
        ],
    ],
    'options' => ['scales' => ['y' => ['beginAtZero' => true]], 'plugins' => ['legend' => ['position' => 'bottom']]],
];
?>
<div class="mb-2 small"><a href="<?= e(url('performance', ['period' => $period, 'from' => $from, 'to' => $to])) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('Performance')) ?></a></div>
<div class="page-head">
  <div class="d-flex align-items-center gap-2"><?= avatar($person['name'], 'avatar-lg') ?>
    <div><h1 class="mb-0"><?= e($person['name']) ?></h1><div class="small text-muted"><?= e(trim(($person['designation'] ?? '') . ' · ' . ($person['department_name'] ?? ''), ' ·')) ?><?= $person['manager_name'] ? ' · ' . e(t('Reports to')) . ' ' . e($person['manager_name']) : '' ?></div></div></div>
  <div class="actions">
    <a class="btn btn-sm btn-primary" href="<?= e(url('appraisals', ['a' => 'create', 'user' => $person['id']])) ?>"><i class="bi bi-file-earmark-plus"></i> <?= e(t('Start appraisal')) ?></a>
  </div>
</div>
<?php $hidden = ['p' => 'performance', 'a' => 'person', 'id' => $person['id']]; require APP_ROOT . '/views/partials/period_filter.php'; ?>

<div class="row g-2 mb-3">
  <?php foreach ([
      [t('Score'), $m['score'] !== null ? number_format($m['score'], 1) : '—'],
      [t('Rated tasks'), $m['tasks_rated'] . ' / ' . $m['tasks_done']],
      [t('Avg quality'), $m['avg_quality'] !== null ? number_format($m['avg_quality'], 1) . ' / 5' : '—'],
      [t('On time'), $m['on_time_pct'] !== null ? $m['on_time_pct'] . '%' : '—'],
      [t('Hours logged'), fmt_hours($m['hours'])],
      [t('Time saved with AI'), $m['saved_minutes'] ? fmt_minutes($m['saved_minutes']) : '—'],
      [t('Days worked'), $m['days_worked'] . ' / ' . $m['working_days']],
      [t('Late start'), $m['late_starts'] . ($m['days_missed'] ? ' · ' . t(':n missed', ['n' => $m['days_missed']]) : '')],
  ] as [$l, $val]): ?>
    <div class="col-6 col-md-3"><div class="metric"><div class="l"><?= e($l) ?></div><div dir="auto" class="v"><?= e((string)$val) ?></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><?= e(t('Score trend (12 months)')) ?></div>
    <div class="card-body"><div class="chart-box"><canvas data-chart="<?= e(json_encode($trendChart, JSON_UNESCAPED_UNICODE)) ?>" aria-label="<?= e(t('Score trend (12 months)')) ?>"></canvas></div></div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><?= e(t('Hours per week (12 weeks)')) ?></div>
    <div class="card-body"><div class="chart-box"><canvas data-chart="<?= e(json_encode($hoursChart, JSON_UNESCAPED_UNICODE)) ?>" aria-label="<?= e(t('Hours per week (12 weeks)')) ?>"></canvas></div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card table-card">
      <div class="card-header"><?= e(t('Task ratings')) ?> <span class="badge bg-light text-dark"><?= count($ratings) ?></span></div>
      <table class="table table-sm mb-0 small">
        <thead class="table-light"><tr><th class="ps-3"><?= e(t('Task')) ?></th><th><?= e(t('Weightage')) ?></th><th><?= e(t('Quality')) ?></th><th><?= e(t('On time')) ?></th><th><?= e(t('Completed')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($ratings as $r): ?>
          <tr>
            <td class="ps-3"><a href="<?= e(url('tasks', ['a' => 'view', 'id' => $r['task_id']])) ?>" class="text-decoration-none"><?= e($r['title']) ?></a><div class="text-muted"><?= e($r['project_name']) ?></div>
              <?php if ($r['comment']): ?><div class="fst-italic"><?= e($r['comment']) ?></div><?php endif; ?></td>
            <td><?= weight_stars((int)$r['weightage']) ?></td>
            <td><?= rating_stars((int)$r['quality']) ?></td>
            <td><?= $r['on_time'] ? '<span class="text-success">✓</span>' : '<span class="text-danger">' . e(days_late_label((int)$r['days_late'])) . '</span>' ?></td>
            <td class="text-nowrap"><?= e(fmt_date($r['completed_on'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$ratings): ?><tr><td colspan="5" class="text-center text-muted py-3"><?= e(t('No rated tasks in this period.')) ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><?= e(t('Appraisals')) ?></div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($appraisals as $a): ?>
          <li class="list-group-item d-flex gap-2 align-items-center"><a href="<?= e(url('appraisals', ['a' => 'view', 'id' => $a['id']])) ?>" class="text-decoration-none flex-grow-1"><?= e($a['title']) ?></a>
            <?= appraisal_status_badge($a['status']) ?> <?= score_badge($a['score'] !== null ? (float)$a['score'] : null) ?></li>
        <?php endforeach; ?>
        <?php if (!$appraisals): ?><li class="list-group-item text-muted"><?= e(t('No appraisals yet.')) ?></li><?php endif; ?>
      </ul>
    </div>
    <div class="card">
      <div class="card-header"><?= e(t('Recent weekly reviews')) ?></div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($reviews as $w): ?>
          <li class="list-group-item">
            <div class="d-flex"><span class="ltr"><?= e(fmt_date($w['week_start'])) ?></span><span class="ms-auto"><?= rating_stars((int)$w['rating']) ?></span></div>
            <?php if ($w['went_well']): ?><div class="text-success text-truncate" title="<?= e($w['went_well']) ?>">+ <?= e($w['went_well']) ?></div><?php endif; ?>
            <?php if ($w['to_improve']): ?><div class="text-warning-emphasis text-truncate" title="<?= e($w['to_improve']) ?>">△ <?= e($w['to_improve']) ?></div><?php endif; ?>
          </li>
        <?php endforeach; ?>
        <?php if (!$reviews): ?><li class="list-group-item text-muted"><?= e(t('No weekly reviews yet.')) ?></li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
