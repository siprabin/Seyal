<?php
$overdue = $task['status'] !== 'done' && $task['due_date'] && $task['due_date'] < today();
$userOption = fn($u) => '<option value="' . (int)$u['id'] . '">' . e($u['name']) . '</option>';
?>
<div class="mb-2 small">
  <a href="<?= e(url('projects', ['a' => 'view', 'id' => $project['id']])) ?>" class="text-decoration-none"><i class="bi bi-kanban"></i> <?= e($project['name']) ?></a>
  <?php foreach ($trail as $tr): ?> / <a href="<?= e(url('tasks', ['a' => 'view', 'id' => $tr['id']])) ?>" class="text-decoration-none"><?= e($tr['title']) ?></a><?php endforeach; ?>
</div>

<div class="page-head">
  <h1 class="<?= $task['status'] === 'done' ? 'text-decoration-line-through text-muted' : '' ?>"><?= e($task['title']) ?></h1>
  <div class="actions">
    <?php if ($canEdit): ?>
      <a href="<?= e(url('tasks', ['a' => 'edit', 'id' => $task['id']])) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> <?= e(t('Edit')) ?></a>
      <form method="post" action="<?= e(url('tasks', ['a' => 'delete', 'id' => $task['id']])) ?>" data-confirm="<?= e(t('Delete this task, its subtasks, comments and files?')) ?>">
        <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> <?= e(t('Delete')) ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <?php if ($task['description']): ?>
      <div class="card mb-3"><div class="card-body" style="white-space:pre-wrap"><?= e($task['description']) ?></div></div>
    <?php endif; ?>

    <!-- Subtasks -->
    <div class="card mb-3">
      <div class="card-header d-flex align-items-center"><i class="bi bi-diagram-2 me-2"></i><?= e(t('Subtasks')) ?>
        <span class="badge bg-light text-dark ms-2"><?= count(array_filter($subtasks, fn($s) => $s['status'] === 'done')) ?>/<?= count($subtasks) ?></span>
      </div>
      <?php if ($subtasks): ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($subtasks as $s): $sOver = $s['status'] !== 'done' && $s['due_date'] && $s['due_date'] < today(); ?>
          <li class="list-group-item d-flex align-items-center gap-2">
            <?= $s['status'] === 'done' ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-circle text-muted"></i>' ?>
            <a href="<?= e(url('tasks', ['a' => 'view', 'id' => $s['id']])) ?>" class="text-decoration-none flex-grow-1 min-w-0 text-truncate<?= $s['status'] === 'done' ? ' text-muted text-decoration-line-through' : '' ?>"><?= e($s['title']) ?></a>
            <?= badge(task_statuses(), $s['status']) ?>
            <?php if ($s['due_date']): ?><span class="small<?= $sOver ? ' text-overdue' : ' text-muted' ?>"><?= e(fmt_date($s['due_date'])) ?></span><?php endif; ?>
            <?= avatar($s['assignee_name'], 'avatar-sm') ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if ($canSub): ?>
      <div class="card-body border-top">
        <form method="post" action="<?= e(url('tasks', ['a' => 'create', 'parent' => $task['id']])) ?>" class="row g-2">
          <?= csrf_field() ?>
          <input type="hidden" name="status" value="todo"><input type="hidden" name="priority" value="<?= e($task['priority']) ?>"><input type="hidden" name="weightage" value="<?= (int)$task['weightage'] ?>">
          <div class="col-md-5"><input class="form-control form-control-sm" name="title" placeholder="<?= e(t('Quick add a subtask…')) ?>" required maxlength="255"></div>
          <div class="col-6 col-md-3">
            <?php if ($canManage): ?>
            <select class="form-select form-select-sm" name="assignee_id" aria-label="<?= e(t('Assignee')) ?>">
              <option value=""><?= e(t('Unassigned')) ?></option>
              <?php foreach ($inProject as $u): ?><option value="<?= (int)$u['id'] ?>"<?= selected($task['assignee_id'], $u['id']) ?>><?= e($u['name']) ?></option><?php endforeach; ?>
              <?php foreach ($others as $u) echo $userOption($u); ?>
            </select>
            <?php else: ?>
            <?php /* Team members' subtasks are always assigned to themselves (enforced on the server). */ ?>
            <div class="form-control form-control-sm bg-light text-truncate" title="<?= e(t('Tasks you add are assigned to you.')) ?>"><i class="bi bi-person"></i> <?= e(user()['name']) ?></div>
            <?php endif; ?>
          </div>
          <div class="col-6 col-md-2"><input type="date" class="form-control form-control-sm" name="due_date" value="<?= e($task['due_date']) ?>" aria-label="<?= e(t('Due date')) ?>"></div>
          <div class="col-md-2 d-flex gap-1">
            <button class="btn btn-sm btn-primary flex-grow-1"><?= e(t('Add')) ?></button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('tasks', ['a' => 'create', 'parent' => $task['id']])) ?>" data-carry="title,assignee_id,due_date" title="<?= e(t('More options')) ?>"><i class="bi bi-sliders"></i></a>
          </div>
        </form>
      </div>
      <?php elseif (!$subtasks): ?>
        <div class="card-body text-muted small"><?= e(t('No subtasks.')) ?></div>
      <?php endif; ?>
    </div>

    <!-- Comments -->
    <div class="card mb-3" id="comments">
      <div class="card-header"><i class="bi bi-chat me-2"></i><?= e(t('Comments')) ?> <span class="badge bg-light text-dark ms-1"><?= count($comments) ?></span></div>
      <div class="card-body">
        <?php foreach ($comments as $c): ?>
          <div class="comment">
            <?= avatar($c['user_name']) ?>
            <div class="flex-grow-1 min-w-0">
              <div class="small"><strong><?= e($c['user_name'] ?? t('Deleted user')) ?></strong> <span class="text-muted" title="<?= e(fmt_datetime($c['created_at'])) ?>"><?= e(time_ago($c['created_at'])) ?></span>
                <?php if ((int)$c['user_id'] === uid() || $canManage): ?>
                  <form method="post" action="<?= e(url('tasks', ['a' => 'delete_comment', 'cid' => $c['id']])) ?>" class="d-inline" data-confirm="<?= e(t('Delete this comment?')) ?>">
                    <?= csrf_field() ?><button class="btn btn-link btn-sm p-0 ms-2 text-danger small"><?= e(t('Delete')) ?></button>
                  </form>
                <?php endif; ?>
              </div>
              <div class="comment-body"><?= e($c['body']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
        <form method="post" action="<?= e(url('tasks', ['a' => 'comment', 'id' => $task['id']])) ?>" class="mt-2">
          <?= csrf_field() ?>
          <textarea class="form-control mb-2" name="body" rows="2" placeholder="<?= e(t('Write a comment…')) ?>" required></textarea>
          <button class="btn btn-sm btn-primary"><i class="bi bi-send"></i> <?= e(t('Post comment')) ?></button>
        </form>
      </div>
    </div>

    <!-- Files -->
    <div class="card mb-3" id="files">
      <div class="card-header"><i class="bi bi-paperclip me-2"></i><?= e(t('Files')) ?> <span class="badge bg-light text-dark ms-1"><?= count($files) ?></span></div>
      <?php if ($files): ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($files as $f): ?>
          <li class="list-group-item d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark"></i>
            <a href="<?= e(url('files', ['id' => $f['id']])) ?>" class="text-truncate"><?= e($f['original_name']) ?></a>
            <span class="small text-muted text-nowrap"><?= e(human_size((int)$f['size_bytes'])) ?> · <?= e($f['user_name'] ?? '') ?> · <?= e(time_ago($f['created_at'])) ?></span>
            <?php if ((int)$f['user_id'] === uid() || $canManage): ?>
              <form method="post" action="<?= e(url('files', ['a' => 'delete', 'id' => $f['id']])) ?>" class="ms-auto" data-confirm="<?= e(t('Delete this file?')) ?>">
                <?= csrf_field() ?><button class="btn btn-sm btn-link text-danger p-0" title="<?= e(t('Delete')) ?>"><i class="bi bi-x-lg"></i></button>
              </form>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <div class="card-body border-top">
        <form method="post" enctype="multipart/form-data" action="<?= e(url('tasks', ['a' => 'upload', 'id' => $task['id']])) ?>" class="d-flex flex-wrap gap-2 align-items-center">
          <?= csrf_field() ?>
          <input type="file" name="file" class="form-control form-control-sm" style="max-width:340px" required>
          <button class="btn btn-sm btn-outline-primary"><i class="bi bi-upload"></i> <?= e(t('Upload')) ?></button>
          <span class="small text-muted"><?= e(t('Max :n MB', ['n' => $maxMb])) ?></span>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><?= e(t('Details')) ?></div>
      <div class="card-body">
        <div class="mb-3">
          <div class="small text-muted mb-1"><?= e(t('Status')) ?></div>
          <?php if ($canStatus): ?>
            <form method="post" action="<?= e(url('tasks', ['a' => 'status', 'id' => $task['id']])) ?>" class="js-autosubmit">
              <?= csrf_field() ?>
              <select name="status" class="form-select form-select-sm" aria-label="<?= e(t('Status')) ?>">
                <?php foreach (task_statuses() as $k => $s): ?><option value="<?= e($k) ?>"<?= selected($task['status'], $k) ?>><?= e($s['label']) ?></option><?php endforeach; ?>
              </select>
            </form>
          <?php else: ?><?= badge(task_statuses(), $task['status']) ?><?php endif; ?>
        </div>
        <dl class="row small mb-0">
          <dt class="col-5 text-muted fw-normal"><?= e(t('Assignee')) ?></dt>
          <dd class="col-7"><?= avatar($info['assignee_name'], 'avatar-sm') ?> <?= e($info['assignee_name'] ?? t('Unassigned')) ?></dd>
          <dt class="col-5 text-muted fw-normal"><?= e(t('Priority')) ?></dt>
          <dd class="col-7"><?= badge(priorities(), $task['priority']) ?></dd>
          <dt class="col-5 text-muted fw-normal"><?= e(t('Weightage')) ?></dt>
          <dd class="col-7"><?= weight_stars((int)$task['weightage']) ?> <?= (int)$task['weightage'] ?>/5</dd>
          <dt class="col-5 text-muted fw-normal"><?= e(t('Estimated hours')) ?></dt>
          <dd class="col-7"><?= $task['estimated_hours'] !== null ? e(fmt_hours((float)$task['estimated_hours'])) : '—' ?></dd>
          <?php $logged = array_sum(array_map(fn($w) => (float)$w['hours'], $worklog)); ?>
          <dt class="col-5 text-muted fw-normal"><?= e(t('Hours logged')) ?></dt>
          <dd class="col-7<?= $task['estimated_hours'] !== null && $logged > (float)$task['estimated_hours'] ? ' text-overdue' : '' ?>"><?= e(fmt_hours($logged)) ?>
            <?php if ($task['estimated_hours'] !== null && (float)$task['estimated_hours'] > 0): ?><span class="text-muted small">(<?= round($logged * 100 / (float)$task['estimated_hours']) ?>%)</span><?php endif; ?></dd>
          <dt class="col-5 text-muted fw-normal"><?= e(t('Start date')) ?></dt>
          <dd class="col-7"><?= e(fmt_date($task['start_date']) ?: '—') ?></dd>
          <dt class="col-5 text-muted fw-normal"><?= e(t('Due date')) ?></dt>
          <dd class="col-7<?= $overdue ? ' text-overdue' : '' ?>"><?= e(fmt_date($task['due_date']) ?: '—') ?><?= $overdue ? ' (' . e(t('overdue')) . ')' : '' ?></dd>
          <?php if ($task['completed_at']): ?>
            <dt class="col-5 text-muted fw-normal"><?= e(t('Completed')) ?></dt>
            <dd class="col-7"><?= e(fmt_datetime($task['completed_at'])) ?></dd>
          <?php endif; ?>
          <dt class="col-5 text-muted fw-normal"><?= e(t('Created by')) ?></dt>
          <dd class="col-7"><?= e($info['creator_name'] ?? '—') ?><br><span class="text-muted"><?= e(fmt_datetime($task['created_at'])) ?></span></dd>
        </dl>
      </div>
    </div>

    <?php if ($canSeeRating && ($rating || $canRate)): ?>
    <div class="card mb-3 border-warning-subtle">
      <div class="card-header"><i class="bi bi-star me-2"></i><?= e(t('Rating')) ?></div>
      <div class="card-body small">
        <?php if ($rating): ?>
          <div class="mb-2"><?= rating_stars((int)$rating['quality']) ?> <strong><?= (int)$rating['quality'] ?>/5</strong>
            · <?= $rating['on_time'] ? '<span class="text-success">' . e(t('On time')) . '</span>' : '<span class="text-danger">' . e(days_late_label((int)$rating['days_late'])) . '</span>' ?></div>
          <?php if ($rating['comment']): ?><div class="fst-italic mb-2"><?= e($rating['comment']) ?></div><?php endif; ?>
          <div class="text-muted mb-2"><?= e(t('Rated by :name', ['name' => $rating['rater_name'] ?? '—'])) ?> · <?= e(fmt_datetime($rating['updated_at'] ?? $rating['created_at'])) ?></div>
        <?php endif; ?>
        <?php if ($canRate && !rating_locked((int)$task['assignee_id'], substr((string)$task['completed_at'], 0, 10))): ?>
          <?php $rateTask = $task; $current = $rating; require APP_ROOT . '/views/partials/rate_form.php'; ?>
        <?php elseif ($rating && $canRate): ?>
          <div class="text-muted"><i class="bi bi-lock"></i> <?= e(t('Locked by a finalised appraisal.')) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-header d-flex align-items-center"><i class="bi bi-stopwatch me-2"></i><?= e(t('Work log')) ?>
        <?php if ((int)$task['assignee_id'] === uid() || $canStatus): ?><a class="ms-auto small" href="<?= e(url('myday', ['task' => $task['id']])) ?>#entry-form"><?= e(t('Log time')) ?></a><?php endif; ?></div>
      <?php if (!$worklog): ?>
        <div class="card-body small text-muted"><?= e(t('No time logged yet.')) ?></div>
      <?php else: ?>
      <ul class="list-group list-group-flush small" style="max-height:300px;overflow-y:auto">
        <?php foreach ($worklog as $w): ?>
          <li class="list-group-item">
            <div class="d-flex gap-2"><span class="text-muted"><?= e(fmt_date($w['work_date'])) ?></span>
              <?php if (can_view_person_day((int)$w['user_id'])): ?><a href="<?= e(url('team', ['a' => 'day', 'id' => $w['day_id']])) ?>"><?= e($w['user_name']) ?></a><?php else: ?><span><?= e($w['user_name']) ?></span><?php endif; ?>
              <strong class="ms-auto"><?= e(fmt_hours((float)$w['hours'])) ?></strong></div>
            <?php if ($w['ai_used']): ?><div class="ai-line"><i class="bi bi-robot"></i> <?= e(fmt_minutes((int)$w['ai_minutes'])) ?></div><?php endif; ?>
            <?php if ($w['description']): ?><div class="text-muted text-truncate" title="<?= e($w['description']) ?>"><?= e($w['description']) ?></div><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-clock-history me-2"></i><?= e(t('History')) ?></div>
      <div class="card-body" style="max-height:420px;overflow-y:auto">
        <?php if (!$history): ?><p class="text-muted small mb-0"><?= e(t('No history yet.')) ?></p><?php endif; ?>
        <?php $showEntity = false; foreach ($history as $h) { require APP_ROOT . '/views/partials/history_item.php'; } ?>
      </div>
    </div>
  </div>
</div>
