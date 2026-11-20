<div class="mb-2 small"><a href="<?= e(url('portal')) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('My projects')) ?></a></div>
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-3 align-items-start">
      <div class="flex-grow-1">
        <h1 class="h4 mb-1"><?= e($project['name']) ?> <?php if ($project['code']): ?><span class="text-muted fs-6 fw-normal"><?= e($project['code']) ?></span><?php endif; ?></h1>
        <div class="small text-muted"><?= badge(project_statuses(), $project['status']) ?> <i class="bi bi-calendar-event ms-2"></i> <span class="ltr"><?= e(fmt_date($project['start_date']) ?: '—') ?> → <?= e(fmt_date($project['target_date']) ?: '—') ?></span></div>
      </div>
      <div style="min-width:220px">
        <div class="d-flex justify-content-between small mb-1"><span><?= e(t('Progress')) ?></span><strong><?= $progress['pct'] ?>%</strong></div>
        <?= progress_bar($progress['pct']) ?>
        <div class="small text-muted mt-1"><?= e(t(':done of :total tasks', ['done' => $progress['done'], 'total' => $progress['total']])) ?></div>
      </div>
    </div>
    <?php if ($project['description']): ?><p class="mt-3 mb-0" style="white-space:pre-wrap"><?= e($project['description']) ?></p><?php endif; ?>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card table-card">
      <div class="card-header"><?= e(t('Milestones')) ?></div>
      <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th class="ps-3"><?= e(t('Milestone')) ?></th><th><?= e(t('Status')) ?></th><th style="min-width:110px"><?= e(t('Progress')) ?></th><th><?= e(t('Due date')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($milestones as $m):
          $pct = $m['status'] === 'done' ? 100 : ((int)$m['subs'] ? (int)round($m['subs_done'] * 100 / $m['subs']) : 0); ?>
          <tr>
            <td class="ps-3"><?= e($m['title']) ?></td>
            <td><?= badge(task_statuses(), $m['status']) ?></td>
            <td><?= progress_bar($pct) ?><span class="small text-muted"><?= $pct ?>%</span></td>
            <td class="text-nowrap small"><?= e($m['status'] === 'done' && $m['completed_at'] ? fmt_date($m['completed_at']) . ' ✓' : (fmt_date($m['due_date']) ?: '—')) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$milestones): ?><tr><td colspan="4" class="text-center text-muted py-3"><?= e(t('Nothing here.')) ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><?= e(t('Recently completed')) ?></div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($recent as $r): ?><li class="list-group-item d-flex gap-2"><i class="bi bi-check-circle-fill text-success"></i><span class="flex-grow-1"><?= e($r['title']) ?></span><span class="text-muted text-nowrap"><?= e(fmt_date($r['completed_at'])) ?></span></li><?php endforeach; ?>
        <?php if (!$recent): ?><li class="list-group-item text-muted"><?= e(t('Nothing here.')) ?></li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
