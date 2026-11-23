<?php
// Current filters as URL parameters, so tabs and the List/Board switch keep them.
$filterParams = array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'project' => $filters['project'] ?: null, 'status' => $filters['status'] ?: null,
    'priority' => $filters['priority'] ?: null, 'due' => $filters['due'] ?: null,
]);
$addUrl = fn(array $p) => url('tasks', ['a' => 'create', 'project' => $p['id'], 'return' => 'mytasks']);
?>
<div class="page-head">
  <h1><?= e(t('My Tasks')) ?></h1>
  <div class="actions d-flex flex-wrap gap-2">
    <?php if (count($addProjects) === 1): ?>
      <a href="<?= e($addUrl($addProjects[0])) ?>" class="btn btn-primary btn-sm" title="<?= e($addProjects[0]['name']) ?>"><i class="bi bi-plus-lg"></i> <?= e(t('Add task')) ?></a>
    <?php elseif ($addProjects): ?>
      <div class="dropdown">
        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="bi bi-plus-lg"></i> <?= e(t('Add task')) ?></button>
        <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:260px">
          <div class="small text-muted px-1 mb-1"><?= e(t('Choose a project')) ?></div>
          <?php if (count($addProjects) > 6): ?>
            <input type="search" class="form-control form-control-sm mb-2" placeholder="<?= e(t('Search')) ?>…" data-filter-list="#add-task-projects" aria-label="<?= e(t('Search')) ?>">
          <?php endif; ?>
          <div id="add-task-projects" style="max-height:300px;overflow-y:auto">
            <?php foreach ($addProjects as $p): ?>
              <a class="dropdown-item rounded" href="<?= e($addUrl($p)) ?>" data-filter-item><i class="bi bi-kanban me-2 text-muted"></i><?= e($p['name']) ?><?php if ($p['code']): ?> <span class="small text-muted">· <?= e($p['code']) ?></span><?php endif; ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <div class="btn-group btn-group-sm">
      <a href="<?= e(url('mytasks', $filterParams)) ?>" class="btn <?= $view === 'list' ? 'btn-primary' : 'btn-outline-secondary' ?>"><i class="bi bi-list-task"></i> <?= e(t('List')) ?></a>
      <a href="<?= e(url('mytasks', ['view' => 'board'] + $filterParams)) ?>" class="btn <?= $view === 'board' ? 'btn-primary' : 'btn-outline-secondary' ?>"><i class="bi bi-kanban"></i> <?= e(t('Board')) ?></a>
    </div>
  </div>
</div>

<form method="get" action="<?= e(url('mytasks')) ?>" class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <input type="hidden" name="p" value="mytasks">
  <?php if ($view === 'board'): ?><input type="hidden" name="view" value="board"><?php else: ?><input type="hidden" name="tab" value="<?= e($tab) ?>"><?php endif; ?>
  <div class="input-group input-group-sm" style="max-width:240px">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input type="search" class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(t('Search tasks')) ?>" aria-label="<?= e(t('Search tasks')) ?>">
  </div>
  <?php
  foreach ([
      ['project', t('Project'), $projectChoices],
      ['status', t('Status'), array_map(fn($s) => $s['label'], task_statuses())],
      ['priority', t('Priority'), array_map(fn($s) => $s['label'], priorities())],
      ['due', t('Due'), $dueChoices],
  ] as [$msName, $msLabel, $msOptions]):
      $msSelected = $filters[$msName]; $msSearch = null;
      require APP_ROOT . '/views/partials/multi_select.php';
  endforeach; ?>
  <?php if ($filtering): ?>
    <a href="<?= e(url('mytasks', $view === 'board' ? ['view' => 'board'] : ['tab' => $tab])) ?>" class="btn btn-link btn-sm"><i class="bi bi-x-circle"></i> <?= e(t('Clear filters')) ?></a>
  <?php endif; ?>
</form>

<?php if ($view === 'board'): ?>
  <?php $canMove = fn($t) => true; $showProject = true; require APP_ROOT . '/views/partials/kanban.php'; ?>
<?php else: ?>
<ul class="nav nav-tabs mb-3">
  <?php foreach ([
      'open' => [t('Open'), (int)$counts['open_n'], 'secondary'],
      'overdue' => [t('Overdue'), (int)$counts['overdue_n'], 'danger'],
      'week' => [t('Due in 7 days'), (int)$counts['week_n'], 'warning'],
      'done' => [t('Done (last 30 days)'), null, ''],
  ] as $k => [$label, $n, $c]): ?>
    <li class="nav-item">
      <a class="nav-link<?= $tab === $k ? ' active' : '' ?>" href="<?= e(url('mytasks', ['tab' => $k] + $filterParams)) ?>"><?= e($label) ?>
        <?php if ($n): ?><span class="badge bg-<?= $c ?><?= $c === 'warning' ? ' text-dark' : '' ?> ms-1"><?= $n ?></span><?php endif; ?></a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card table-card">
  <?php if (!$tasks): ?>
    <div class="card-body text-center text-muted py-5">
      <?php if ($filtering): ?>
        <i class="bi bi-funnel fs-1 d-block mb-2"></i><?= e(t('No tasks match these filters.')) ?>
      <?php else: ?>
        <i class="bi bi-emoji-smile fs-1 d-block mb-2"></i><?= e(t('Nothing here.')) ?>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <table class="table table-hover table-sm">
    <thead class="table-light">
      <tr>
        <th class="ps-3"><?= e(t('Task')) ?></th>
        <th class="d-none d-md-table-cell"><?= e(t('Project')) ?></th>
        <th><?= e(t('Status')) ?></th>
        <th class="d-none d-md-table-cell"><?= e(t('Priority')) ?></th>
        <th class="d-none d-lg-table-cell"><?= e(t('Weightage')) ?></th>
        <th><?= e($tab === 'done' ? t('Completed') : t('Due')) ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tasks as $t): $overdue = $t['status'] !== 'done' && $t['due_date'] && $t['due_date'] < today(); ?>
        <tr class="task-row<?= $t['status'] === 'done' ? ' is-done' : '' ?>">
          <td class="ps-3">
            <a href="<?= e(url('tasks', ['a' => 'view', 'id' => $t['id']])) ?>" class="task-title text-decoration-none"><?= e($t['title']) ?></a>
            <?php if ($t['parent_title']): ?><div class="small text-muted">↳ <?= e($t['parent_title']) ?></div><?php endif; ?>
            <div class="small text-muted d-md-none"><?= e($t['project_name']) ?></div>
          </td>
          <td class="d-none d-md-table-cell"><a class="text-decoration-none text-body" href="<?= e(url('projects', ['a' => 'view', 'id' => $t['project_id']])) ?>"><?= e($t['project_name']) ?></a></td>
          <td>
            <form method="post" action="<?= e(url('tasks', ['a' => 'status', 'id' => $t['id']])) ?>" class="js-autosubmit">
              <?= csrf_field() ?>
              <select name="status" class="form-select form-select-sm" style="min-width:120px" aria-label="<?= e(t('Status')) ?>">
                <?php foreach (task_statuses() as $k => $s): ?><option value="<?= e($k) ?>"<?= selected($t['status'], $k) ?>><?= e($s['label']) ?></option><?php endforeach; ?>
              </select>
            </form>
          </td>
          <td class="d-none d-md-table-cell"><?= badge(priorities(), $t['priority']) ?></td>
          <td class="d-none d-lg-table-cell"><?= weight_stars((int)$t['weightage']) ?></td>
          <td class="text-nowrap<?= $overdue ? ' text-overdue' : '' ?>"><?= e($tab === 'done' ? fmt_date($t['completed_at']) : fmt_date($t['due_date'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>
