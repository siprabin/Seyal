<?php
/** Shared project header with summary and tabs. Expects $project, $info, $progress, $canManage, $tab. */
$late = $project['target_date'] && $project['target_date'] < today() && !in_array($project['status'], ['completed', 'cancelled'], true);
?>
<div class="mb-2 small"><a href="<?= e(url('projects')) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('Projects')) ?></a></div>
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-3 align-items-start">
      <div class="flex-grow-1 min-w-0">
        <h1 class="h4 mb-1"><?= e($project['name']) ?>
          <?php if ($project['code']): ?><span class="text-muted fs-6 fw-normal"><?= e($project['code']) ?></span><?php endif; ?>
        </h1>
        <div class="d-flex flex-wrap gap-2 align-items-center small text-muted">
          <?= badge(project_statuses(), $project['status']) ?>
          <?= badge(priorities(), $project['priority']) ?>
          <span><i class="bi bi-building"></i> <?= e($info['client_name'] ?: t('Internal')) ?></span>
          <?php if ($info['department_name']): ?><span><i class="bi bi-collection"></i> <?= e($info['department_name']) ?></span><?php endif; ?>
          <?php if ($info['manager_name']): ?><span><i class="bi bi-person-badge"></i> <?= e($info['manager_name']) ?></span><?php endif; ?>
          <span><i class="bi bi-calendar-event"></i> <?= e(fmt_date($project['start_date']) ?: '—') ?> → <span class="<?= $late ? 'text-overdue' : '' ?>"><?= e(fmt_date($project['target_date']) ?: '—') ?></span></span>
        </div>
      </div>
      <div style="min-width:200px">
        <div class="d-flex justify-content-between small mb-1"><span><?= e(t('Progress')) ?></span><strong><?= $progress['pct'] ?>%</strong></div>
        <?= progress_bar($progress['pct']) ?>
        <div class="small text-muted mt-1"><?= e(t(':done of :total tasks', ['done' => $progress['done'], 'total' => $progress['total']])) ?>
          <?php if ($progress['overdue']): ?><span class="text-overdue"> · <?= e(t(':n overdue', ['n' => $progress['overdue']])) ?></span><?php endif; ?></div>
      </div>
      <?php if ($canManage || can('projects.delete')): ?>
      <div class="dropdown">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php if ($canManage): ?>
            <li><a class="dropdown-item" href="<?= e(url('projects', ['a' => 'edit', 'id' => $project['id']])) ?>"><i class="bi bi-pencil me-2"></i><?= e(t('Edit project')) ?></a></li>
          <?php endif; ?>
          <?php if (can('projects.delete')): ?>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="<?= e(url('projects', ['a' => 'delete', 'id' => $project['id']])) ?>" data-confirm="<?= e(t('Delete this project and ALL its tasks, comments and files? This cannot be undone.')) ?>">
                <?= csrf_field() ?><button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i><?= e(t('Delete project')) ?></button>
              </form>
            </li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($project['description']): ?>
      <p class="mt-3 mb-0 text-body-secondary" style="white-space:pre-wrap"><?= e($project['description']) ?></p>
    <?php endif; ?>
  </div>
</div>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <ul class="nav nav-pills">
    <li class="nav-item"><a class="nav-link<?= $tab === 'view' ? ' active' : '' ?>" href="<?= e(url('projects', ['a' => 'view', 'id' => $project['id']])) ?>"><i class="bi bi-list-task"></i> <?= e(t('List')) ?></a></li>
    <li class="nav-item"><a class="nav-link<?= $tab === 'board' ? ' active' : '' ?>" href="<?= e(url('projects', ['a' => 'board', 'id' => $project['id']])) ?>"><i class="bi bi-kanban"></i> <?= e(t('Board')) ?></a></li>
    <li class="nav-item"><a class="nav-link<?= $tab === 'history' ? ' active' : '' ?>" href="<?= e(url('projects', ['a' => 'history', 'id' => $project['id']])) ?>"><i class="bi bi-clock-history"></i> <?= e(t('History')) ?></a></li>
  </ul>
  <?php if (can_create_task($project)): ?>
    <a href="<?= e(url('tasks', ['a' => 'create', 'project' => $project['id']])) ?>" class="btn btn-primary btn-sm ms-auto"><i class="bi bi-plus-lg"></i> <?= e(t('Add task')) ?></a>
  <?php endif; ?>
</div>
