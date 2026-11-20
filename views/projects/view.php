<?php
require __DIR__ . '/_header.php';
$filtered = $filters['status'] !== '' || $filters['assignee'] || $filters['priority'] !== '' || $filters['due'] !== '';
// Without filters, show each subtask directly under its parent.
if (!$filtered) {
    $byParent = [];
    foreach ($tasks as $t) {
        $byParent[(int)$t['parent_id']][] = $t;
    }
    $ordered = [];
    $walk = function ($pid, $depth) use (&$walk, &$ordered, $byParent) {
        foreach ($byParent[$pid] ?? [] as $t) {
            $t['_depth'] = $depth;
            $ordered[] = $t;
            $walk((int)$t['id'], $depth + 1);
        }
    };
    $walk(0, 0);
    $tasks = $ordered;
}
?>
<form class="row g-2 mb-3 filters js-autosubmit" method="get">
  <input type="hidden" name="p" value="projects"><input type="hidden" name="a" value="view"><input type="hidden" name="id" value="<?= (int)$project['id'] ?>">
  <div class="col-6 col-md-auto">
    <select name="status" class="form-select form-select-sm">
      <option value=""><?= e(t('All statuses')) ?></option>
      <option value="open"<?= selected($filters['status'], 'open') ?>><?= e(t('Not done')) ?></option>
      <?php foreach (task_statuses() as $k => $s): ?><option value="<?= e($k) ?>"<?= selected($filters['status'], $k) ?>><?= e($s['label']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto">
    <select name="assignee" class="form-select form-select-sm">
      <option value=""><?= e(t('Everyone')) ?></option>
      <option value="-1"<?= selected($filters['assignee'], -1) ?>><?= e(t('Unassigned')) ?></option>
      <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"<?= selected($filters['assignee'], $m['id']) ?>><?= e($m['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto">
    <select name="priority" class="form-select form-select-sm">
      <option value=""><?= e(t('All priorities')) ?></option>
      <?php foreach (priorities() as $k => $s): ?><option value="<?= e($k) ?>"<?= selected($filters['priority'], $k) ?>><?= e($s['label']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto">
    <select name="due" class="form-select form-select-sm">
      <option value=""><?= e(t('Any due date')) ?></option>
      <option value="overdue"<?= selected($filters['due'], 'overdue') ?>><?= e(t('Overdue')) ?></option>
      <option value="week"<?= selected($filters['due'], 'week') ?>><?= e(t('Due in 7 days')) ?></option>
    </select>
  </div>
  <?php if ($filtered): ?>
    <div class="col-auto"><a class="btn btn-sm btn-link" href="<?= e(url('projects', ['a' => 'view', 'id' => $project['id']])) ?>"><?= e(t('Clear filters')) ?></a></div>
  <?php endif; ?>
</form>

<div class="row g-3">
  <div class="col-xl-9">
    <div class="card table-card">
      <?php if (!$tasks): ?>
        <div class="card-body text-center text-muted py-5">
          <i class="bi bi-list-check fs-1 d-block mb-2"></i>
          <?= e($filtered ? t('No tasks match these filters.') : t('No tasks yet.')) ?>
          <?php if (can_create_task($project) && !$filtered): ?>
            <div class="mt-3"><a class="btn btn-primary btn-sm" href="<?= e(url('tasks', ['a' => 'create', 'project' => $project['id']])) ?>"><?= e(t('Add the first task')) ?></a></div>
          <?php endif; ?>
        </div>
      <?php else: ?>
      <table class="table table-hover table-sm">
        <thead class="table-light">
          <tr>
            <th class="ps-3"><?= e(t('Task')) ?></th>
            <th><?= e(t('Assignee')) ?></th>
            <th><?= e(t('Status')) ?></th>
            <th class="d-none d-md-table-cell"><?= e(t('Priority')) ?></th>
            <th class="d-none d-lg-table-cell"><?= e(t('Weightage')) ?></th>
            <th class="d-none d-lg-table-cell text-end"><?= e(t('Est. hrs')) ?></th>
            <th><?= e(t('Due')) ?></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tasks as $t):
          $overdue = $t['status'] !== 'done' && $t['due_date'] && $t['due_date'] < today();
          $depth = (int)($t['_depth'] ?? 0);
          $canStatus = $canManage || (int)$t['assignee_id'] === uid(); ?>
          <tr class="task-row<?= $t['status'] === 'done' ? ' is-done' : '' ?>">
            <td class="ps-3" style="<?= $depth ? 'padding-inline-start:' . (1 + $depth * 1.4) . 'rem!important' : '' ?>">
              <?php if ($depth): ?><span class="text-muted">↳</span><?php endif; ?>
              <a href="<?= e(url('tasks', ['a' => 'view', 'id' => $t['id']])) ?>" class="task-title text-decoration-none"><?= e($t['title']) ?></a>
              <?php if ($filtered && $t['parent_title']): ?><div class="small text-muted"><?= e(t('in')) ?> <?= e($t['parent_title']) ?></div><?php endif; ?>
              <span class="small text-muted ms-1">
                <?php if ($t['sub_count']): ?><span title="<?= e(t('Subtasks')) ?>"><i class="bi bi-diagram-2"></i> <?= (int)$t['sub_done'] ?>/<?= (int)$t['sub_count'] ?></span><?php endif; ?>
                <?php if ($t['comment_count']): ?><span class="ms-1" title="<?= e(t('Comments')) ?>"><i class="bi bi-chat"></i> <?= (int)$t['comment_count'] ?></span><?php endif; ?>
                <?php if ($t['file_count']): ?><span class="ms-1" title="<?= e(t('Files')) ?>"><i class="bi bi-paperclip"></i> <?= (int)$t['file_count'] ?></span><?php endif; ?>
              </span>
            </td>
            <td class="text-nowrap"><?= avatar($t['assignee_name'], 'avatar-sm') ?> <span class="d-none d-xl-inline small"><?= e($t['assignee_name'] ?? '') ?></span></td>
            <td>
              <?php if ($canStatus): ?>
                <form method="post" action="<?= e(url('tasks', ['a' => 'status', 'id' => $t['id']])) ?>" class="js-autosubmit">
                  <?= csrf_field() ?>
                  <select name="status" class="form-select form-select-sm" style="min-width:120px" aria-label="<?= e(t('Status')) ?>">
                    <?php foreach (task_statuses() as $k => $s): ?><option value="<?= e($k) ?>"<?= selected($t['status'], $k) ?>><?= e($s['label']) ?></option><?php endforeach; ?>
                  </select>
                </form>
              <?php else: ?>
                <?= badge(task_statuses(), $t['status']) ?>
              <?php endif; ?>
            </td>
            <td class="d-none d-md-table-cell"><?= badge(priorities(), $t['priority']) ?></td>
            <td class="d-none d-lg-table-cell"><?= weight_stars((int)$t['weightage']) ?></td>
            <td class="d-none d-lg-table-cell text-end"><?= $t['estimated_hours'] !== null ? e(rtrim(rtrim($t['estimated_hours'], '0'), '.')) : '' ?></td>
            <td class="text-nowrap<?= $overdue ? ' text-overdue' : '' ?>"><?= e(fmt_date($t['due_date'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-xl-3">
    <div class="card">
      <div class="card-header d-flex align-items-center"><?= e(t('Team')) ?> <span class="badge bg-light text-dark ms-2"><?= count($members) ?></span>
        <?php if ($canManage): ?><a class="ms-auto small" href="<?= e(url('projects', ['a' => 'edit', 'id' => $project['id']])) ?>#members"><?= e(t('Edit')) ?></a><?php endif; ?>
      </div>
      <ul class="list-group list-group-flush">
        <?php foreach ($members as $m): ?>
          <li class="list-group-item d-flex align-items-center gap-2">
            <?= avatar($m['name'], 'avatar-sm') ?>
            <div class="min-w-0"><div class="text-truncate"><?= e($m['name']) ?>
              <?php if ((int)$m['id'] === (int)$project['manager_id']): ?><span class="badge bg-primary-subtle text-primary-emphasis ms-1"><?= e(t('PM')) ?></span><?php endif; ?></div>
              <?php if ($m['designation']): ?><div class="small text-muted text-truncate"><?= e($m['designation']) ?></div><?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
        <?php if (!$members): ?><li class="list-group-item text-muted small"><?= e(t('No members yet.')) ?></li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
