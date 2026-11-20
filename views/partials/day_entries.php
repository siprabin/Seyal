<?php
/**
 * Work-log entries of one day + totals. Expects $entries, $totals.
 * Optional: $editable (bool) shows edit/delete for the owner, $dayDate for links.
 */
$editable = $editable ?? false;
$qualities = quality_labels();
$purposes = ai_purposes();
?>
<div class="row g-2 mb-3 day-stats">
  <?php foreach ([
      [t('Hours logged'), fmt_hours($totals['hours']), 'bi-clock'],
      [t('Entries'), $totals['count'], 'bi-list-check'],
      [t('AI time'), $totals['ai_minutes'] ? fmt_minutes($totals['ai_minutes']) : '—', 'bi-robot'],
      [t('Time saved with AI'), $totals['saved_minutes'] ? fmt_minutes($totals['saved_minutes']) : '—', 'bi-lightning-charge'],
  ] as [$label, $value, $icon]): ?>
    <div class="col-6 col-md-3">
      <div class="border rounded bg-white px-3 py-2 h-100">
        <div class="small text-muted"><i class="bi <?= $icon ?>"></i> <?= e($label) ?></div>
        <div dir="auto" class="fs-5 fw-semibold"><?= e((string)$value) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (!$entries): ?>
  <p class="text-muted"><?= e(t('No work entries were logged.')) ?></p>
<?php else: ?>
<div class="list-group mb-3">
  <?php foreach ($entries as $en): ?>
    <div class="list-group-item">
      <div class="d-flex flex-wrap align-items-start gap-2">
        <div class="flex-grow-1 min-w-0">
          <?php if ($en['task_id']): ?>
            <a href="<?= e(url('tasks', ['a' => 'view', 'id' => $en['task_id']])) ?>" class="fw-semibold text-decoration-none"><?= e($en['task_title']) ?></a>
            <span class="small text-muted">· <?= e($en['project_name']) ?></span>
            <?= $en['task_status'] ? badge(task_statuses(), $en['task_status']) : '' ?>
          <?php else: ?>
            <span class="fw-semibold"><?= e(t('Other work')) ?></span>
          <?php endif; ?>
          <?php if ($en['description']): ?><div class="small mt-1" style="white-space:pre-wrap"><?= e($en['description']) ?></div><?php endif; ?>
          <?php if ($en['ai_used']): ?>
            <div class="small mt-1 ai-line">
              <i class="bi bi-robot"></i>
              <?= e(implode(', ', $en['tools'])) ?> · <?= e(fmt_minutes((int)$en['ai_minutes'])) ?>
              <?php if ($en['ai_purpose']): ?> · <?= e($purposes[$en['ai_purpose']] ?? $en['ai_purpose']) ?><?php endif; ?>
              <?php if ($en['time_saved_minutes']): ?> · <?= e(t('saved :t', ['t' => fmt_minutes((int)$en['time_saved_minutes'])])) ?><?php endif; ?>
              <?php if ($en['output_quality']): ?> · <?= e(t('quality: :q', ['q' => $en['output_quality'] . '/5 ' . ($qualities[(int)$en['output_quality']] ?? '')])) ?><?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($en['blockers']): ?>
            <div class="small mt-1 text-danger"><i class="bi bi-sign-stop"></i> <strong><?= e(t('Blocker')) ?>:</strong> <?= e($en['blockers']) ?></div>
          <?php endif; ?>
        </div>
        <div class="text-end">
          <div class="fw-semibold"><?= e(fmt_hours((float)$en['hours'])) ?> <span class="small text-muted"><?= e(t('h')) ?></span></div>
          <?php if ($editable): ?>
            <div class="d-flex gap-1 mt-1 justify-content-end">
              <a class="btn btn-sm btn-outline-secondary py-0" href="<?= e(url('myday', array_filter(['date' => $dayDate !== today() ? $dayDate : null, 'edit' => $en['id']]))) ?>#entry-form" title="<?= e(t('Edit')) ?>"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= e(url('myday', ['a' => 'delete_entry', 'id' => $en['id']])) ?>" data-confirm="<?= e(t('Delete this entry?')) ?>">
                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger py-0" title="<?= e(t('Delete')) ?>"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
