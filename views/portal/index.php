<div class="page-head">
  <div><h1><?= e(t('My projects')) ?></h1><div class="text-muted"><?= e($client['name']) ?></div></div>
</div>
<?php if (!$projects): ?>
  <div class="card"><div class="card-body text-center text-muted py-5"><i class="bi bi-kanban fs-1 d-block mb-2"></i><?= e(t('There are no projects to show yet.')) ?></div></div>
<?php endif; ?>
<div class="row g-3">
  <?php foreach ($projects as $p): $pr = $progress[(int)$p['id']]; ?>
    <div class="col-md-6 col-lg-4">
      <a href="<?= e(url('portal', ['a' => 'project', 'id' => $p['id']])) ?>" class="card h-100 text-decoration-none text-body">
        <div class="card-body">
          <div class="d-flex gap-2 align-items-start mb-2">
            <div class="fw-semibold flex-grow-1"><?= e($p['name']) ?></div><?= badge(project_statuses(), $p['status']) ?>
          </div>
          <div class="d-flex align-items-center gap-2 mb-2"><div class="flex-grow-1"><?= progress_bar($pr['pct']) ?></div><strong><?= $pr['pct'] ?>%</strong></div>
          <div class="small text-muted"><?= e(t(':done of :total tasks', ['done' => $pr['done'], 'total' => $pr['total']])) ?></div>
          <div class="small mt-2"><i class="bi bi-calendar-event"></i> <span class="ltr"><?= e(fmt_date($p['start_date']) ?: '—') ?> → <?= e(fmt_date($p['target_date']) ?: '—') ?></span></div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>
