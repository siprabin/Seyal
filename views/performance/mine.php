<?php
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
<div class="page-head">
  <h1><?= e(t('My performance')) ?></h1>
  <div class="actions"><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('reviews', ['a' => 'mine'])) ?>"><i class="bi bi-chat-square-text"></i> <?= e(t('My weekly feedback')) ?>
    <?php if ($sharedReviews): ?><span class="badge bg-secondary"><?= $sharedReviews ?></span><?php endif; ?></a></div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-award"></i> <?= e(t('My appraisals')) ?></div>
  <?php if (!$published): ?>
    <div class="card-body text-muted small"><?= e(t('Your scores and ratings appear here once your manager publishes an appraisal.')) ?></div>
  <?php else: ?>
  <ul class="list-group list-group-flush">
    <?php foreach ($published as $a): ?>
      <li class="list-group-item d-flex flex-wrap gap-2 align-items-center">
        <a href="<?= e(url('appraisals', ['a' => 'view', 'id' => $a['id']])) ?>" class="fw-semibold text-decoration-none"><?= e($a['title']) ?></a>
        <span class="small text-muted ltr"><?= e(fmt_date($a['period_from'])) ?> – <?= e(fmt_date($a['period_to'])) ?></span>
        <span class="ms-auto"><?= score_badge($a['score'] !== null ? (float)$a['score'] : null) ?></span>
        <?php if (!$a['acknowledged_at']): ?><span class="badge bg-warning text-dark"><?= e(t('Please read and acknowledge')) ?></span><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>

<?php $hidden = ['p' => 'performance', 'a' => 'mine']; require APP_ROOT . '/views/partials/period_filter.php'; ?>
<div class="row g-2 mb-3">
  <?php foreach ([
      [t('Hours logged'), fmt_hours($m['hours'])],
      [t('Tasks done'), $m['tasks_done']],
      [t('On time'), $m['on_time_pct'] !== null ? $m['on_time_pct'] . '%' : '—'],
      [t('Days worked'), $m['days_worked'] . ' / ' . $m['working_days']],
      [t('AI time'), $m['ai_minutes'] ? fmt_minutes($m['ai_minutes']) : '—'],
      [t('Time saved with AI'), $m['saved_minutes'] ? fmt_minutes($m['saved_minutes']) : '—'],
  ] as [$l, $val]): ?>
    <div class="col-6 col-md-4 col-xl-2"><div class="metric"><div class="l"><?= e($l) ?></div><div dir="auto" class="v"><?= e((string)$val) ?></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card"><div class="card-header"><?= e(t('Hours per week (12 weeks)')) ?></div>
  <div class="card-body"><div class="chart-box"><canvas data-chart="<?= e(json_encode($hoursChart, JSON_UNESCAPED_UNICODE)) ?>" aria-label="<?= e(t('Hours per week (12 weeks)')) ?>"></canvas></div></div></div>
