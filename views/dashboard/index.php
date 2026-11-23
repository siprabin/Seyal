<?php $u = user(); ?>
<div class="page-head">
  <h1><?= e(t('Welcome, :name', ['name' => $u['name']])) ?></h1>
  <div class="actions text-muted small align-self-center"><?= e(fmt_date(today())) ?></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-<?= $teamToday ? '7' : '12' ?>">
    <div class="card h-100">
      <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <i class="bi bi-sunrise fs-2 text-warning"></i>
        <div class="flex-grow-1">
          <div class="fw-semibold"><?= e(t('My Day')) ?></div>
          <div class="small text-muted">
            <?php if (!$myDay || $myDay['status'] === 'missed'): ?><?= e(t('You have not started your day yet.')) ?>
            <?php elseif ($myDay['status'] === 'open'): ?><?= e(t('Working since :time', ['time' => fmt_time($myDay['started_at'])])) ?> · <?= e(t(':t logged', ['t' => fmt_hours($myDayTotals['hours'])])) ?>
            <?php elseif ($myDay['status'] === 'leave'): ?><?= e(t('On leave')) ?>
            <?php else: ?><?= e(t('Day closed')) ?> · <?= e(t(':t logged', ['t' => fmt_hours($myDayTotals['hours'])])) ?><?php endif; ?>
          </div>
        </div>
        <?php if (!$myDay || $myDay['status'] === 'missed'): ?>
          <form method="post" action="<?= e(url('myday', ['a' => 'start'])) ?>"<?= location_form_attr() ?>><?= csrf_field() ?><button class="btn btn-success"><i class="bi bi-play-circle"></i> <?= e(t('Start Day')) ?></button></form>
        <?php elseif ($myDay['status'] === 'open'): ?>
          <a href="<?= e(url('myday')) ?>#entry-form" class="btn btn-primary"><i class="bi bi-plus-circle"></i> <?= e(t('Log work')) ?></a>
          <a href="<?= e(url('myday', ['a' => 'close'])) ?>" class="btn btn-outline-danger"><i class="bi bi-stop-circle"></i> <?= e(t('Close Day')) ?></a>
        <?php else: ?>
          <a href="<?= e(url('myday')) ?>" class="btn btn-outline-secondary"><?= e(t('View')) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if ($teamToday): ?>
  <div class="col-lg-5">
    <a href="<?= e(url('team')) ?>" class="card h-100 text-decoration-none text-body">
      <div class="card-body">
        <div class="fw-semibold mb-2"><i class="bi bi-people-fill"></i> <?= e(t('My team today')) ?></div>
        <div class="d-flex flex-wrap gap-3 small">
          <span><span class="fs-5 fw-semibold text-primary"><?= $teamToday['working'] ?></span> <?= e(t('working')) ?></span>
          <span><span class="fs-5 fw-semibold text-success"><?= $teamToday['closed'] ?></span> <?= e(t('closed')) ?></span>
          <span><span class="fs-5 fw-semibold text-danger"><?= $teamToday['none'] ?></span> <?= e(t('not started')) ?></span>
          <?php if ($teamToday['leave']): ?><span><span class="fs-5 fw-semibold text-secondary"><?= $teamToday['leave'] ?></span> <?= e(t('on leave')) ?></span><?php endif; ?>
        </div>
      </div>
    </a>
  </div>
  <?php endif; ?>
</div>

<div class="row g-3 mb-3">
  <?php foreach ([
      [t('My open tasks'), (int)$mine['open_n'], 'bi-list-check', url('mytasks'), ''],
      [t('My overdue tasks'), (int)$mine['overdue_n'], 'bi-exclamation-octagon', url('mytasks', ['tab' => 'overdue']), (int)$mine['overdue_n'] ? 'text-danger' : ''],
      [t('Due in 7 days'), (int)$mine['week_n'], 'bi-calendar-week', url('mytasks', ['tab' => 'week']), ''],
      [t('Active projects'), $activeCount, 'bi-kanban', url('projects'), ''],
  ] as [$label, $n, $icon, $link, $cls]): ?>
    <div class="col-6 col-lg-3">
      <a href="<?= e($link) ?>" class="card stat-card text-decoration-none text-body h-100">
        <div class="card-body d-flex align-items-center">
          <div><div class="stat-value <?= $cls ?>"><?= $n ?></div><div class="stat-label"><?= e($label) ?></div></div>
          <i class="bi <?= $icon ?> ms-auto"></i>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex"><?= e(t('My next tasks')) ?><a class="ms-auto small" href="<?= e(url('mytasks')) ?>"><?= e(t('View all')) ?></a></div>
      <?php if (!$myTasks): ?>
        <div class="card-body text-muted"><?= e(t('You have no open tasks.')) ?></div>
      <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($myTasks as $t): $over = $t['due_date'] && $t['due_date'] < today(); ?>
          <li class="list-group-item d-flex align-items-center gap-2">
            <div class="min-w-0 flex-grow-1">
              <a href="<?= e(url('tasks', ['a' => 'view', 'id' => $t['id']])) ?>" class="text-decoration-none d-block text-truncate"><?= e($t['title']) ?></a>
              <div class="small text-muted text-truncate"><?= e($t['project_name']) ?></div>
            </div>
            <?= badge(task_statuses(), $t['status']) ?>
            <span class="small text-nowrap<?= $over ? ' text-overdue' : ' text-muted' ?>"><?= e(fmt_date($t['due_date'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex"><?= e(t('Projects')) ?><a class="ms-auto small" href="<?= e(url('projects')) ?>"><?= e(t('View all')) ?></a></div>
      <?php if (!$projects): ?>
        <div class="card-body text-muted"><?= e(t('No active projects.')) ?></div>
      <?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($projects as $p): $pr = $progress[(int)$p['id']]; $late = $p['target_date'] && $p['target_date'] < today(); ?>
          <li class="list-group-item">
            <div class="d-flex align-items-center gap-2 mb-1">
              <a href="<?= e(url('projects', ['a' => 'view', 'id' => $p['id']])) ?>" class="text-decoration-none fw-semibold text-truncate"><?= e($p['name']) ?></a>
              <?= badge(project_statuses(), $p['status']) ?>
              <span class="ms-auto small text-nowrap<?= $late ? ' text-overdue' : ' text-muted' ?>"><?= e(fmt_date($p['target_date'])) ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="flex-grow-1"><?= progress_bar($pr['pct']) ?></div>
              <span class="small text-muted" style="width:3rem;text-align:right"><?= $pr['pct'] ?>%</span>
            </div>
            <?php if ($pr['overdue']): ?><div class="small text-overdue"><?= e(t(':n overdue', ['n' => $pr['overdue']])) ?></div><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($teamOverdue): ?>
  <div class="col-12">
    <div class="card border-danger-subtle">
      <div class="card-header text-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= e(t('Overdue tasks in projects you manage')) ?></div>
      <div class="table-card">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th class="ps-3"><?= e(t('Task')) ?></th><th><?= e(t('Project')) ?></th><th><?= e(t('Assignee')) ?></th><th><?= e(t('Due')) ?></th></tr></thead>
          <tbody>
          <?php foreach ($teamOverdue as $t): ?>
            <tr>
              <td class="ps-3"><a href="<?= e(url('tasks', ['a' => 'view', 'id' => $t['id']])) ?>" class="text-decoration-none"><?= e($t['title']) ?></a></td>
              <td><?= e($t['project_name']) ?></td>
              <td><?= avatar($t['assignee_name'], 'avatar-sm') ?> <?= e($t['assignee_name'] ?? t('Unassigned')) ?></td>
              <td class="text-overdue text-nowrap"><?= e(fmt_date($t['due_date'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
