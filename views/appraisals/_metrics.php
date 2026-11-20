<?php
/** Metrics grid for an appraisal. Expects $m (person_metrics array). */
$tiles = [
    [t('Score'), isset($m['score']) && $m['score'] !== null ? number_format((float)$m['score'], 1) . ' / 100' : '—', 'bi-speedometer'],
    [t('Rated tasks'), ($m['tasks_rated'] ?? 0) . ' / ' . ($m['tasks_done'] ?? 0), 'bi-check2-square'],
    [t('Avg quality'), isset($m['avg_quality']) && $m['avg_quality'] !== null ? number_format((float)$m['avg_quality'], 1) . ' / 5' : '—', 'bi-star'],
    [t('On time'), isset($m['on_time_pct']) && $m['on_time_pct'] !== null ? $m['on_time_pct'] . '%' : '—', 'bi-alarm'],
    [t('Hours logged'), fmt_hours((float)($m['hours'] ?? 0)), 'bi-clock'],
    [t('Days worked'), ($m['days_worked'] ?? 0) . ' / ' . ($m['working_days'] ?? 0), 'bi-calendar-check'],
    [t('Late start'), (string)($m['late_starts'] ?? 0), 'bi-hourglass-split'],
    [t('Weekly review'), isset($m['review_avg']) && $m['review_avg'] !== null ? number_format((float)$m['review_avg'], 1) . ' / 5 (' . (int)$m['reviews'] . ')' : '—', 'bi-chat-square-text'],
    [t('AI time'), !empty($m['ai_minutes']) ? fmt_minutes((int)$m['ai_minutes']) : '—', 'bi-robot'],
    [t('Time saved with AI'), !empty($m['saved_minutes']) ? fmt_minutes((int)$m['saved_minutes']) : '—', 'bi-lightning-charge'],
];
?>
<div class="row g-2">
  <?php foreach ($tiles as [$l, $val, $icon]): ?>
    <div class="col-6 col-md-4 col-xl"><div class="metric"><div class="l text-truncate"><i class="bi <?= $icon ?>"></i> <?= e($l) ?></div><div dir="auto" class="v" style="font-size:1.1rem"><?= e($val) ?></div></div></div>
  <?php endforeach; ?>
</div>
