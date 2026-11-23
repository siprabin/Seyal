<?php
$tk = $task ?? [];
$prefill = $prefill ?? [];
$v = fn($k, $d = '') => old($k, $tk[$k] ?? $prefill[$k] ?? $d);
$cancel = $task ? url('tasks', ['a' => 'view', 'id' => $task['id']])
    : (($returnTo ?? null) === 'mytasks' ? url('mytasks')
    : ($parent ? url('tasks', ['a' => 'view', 'id' => $parent['id']]) : url('projects', ['a' => 'view', 'id' => $project['id']])));
$userOption = fn($u, $sel) => '<option value="' . (int)$u['id'] . '"' . selected($sel, $u['id']) . '>' . e($u['name']) . ($u['designation'] ? ' — ' . e($u['designation']) : '') . '</option>';
?>
<div class="mb-2 small">
  <a href="<?= e(url('projects', ['a' => 'view', 'id' => $project['id']])) ?>" class="text-decoration-none"><i class="bi bi-kanban"></i> <?= e($project['name']) ?></a>
  <?php if ($parent): ?> / <a href="<?= e(url('tasks', ['a' => 'view', 'id' => $parent['id']])) ?>" class="text-decoration-none"><?= e($parent['title']) ?></a><?php endif; ?>
</div>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>

<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label" for="title"><?= e(t('Title')) ?> <span class="text-danger">*</span></label>
        <input class="form-control" id="title" name="title" value="<?= e($v('title')) ?>" maxlength="255" required autofocus>
      </div>
      <div class="col-12">
        <label class="form-label" for="description"><?= e(t('Description')) ?></label>
        <textarea class="form-control" id="description" name="description" rows="4"><?= e($v('description')) ?></textarea>
      </div>
      <?php if (!$canAssign): ?>
      <div class="col-md-6">
        <label class="form-label"><?= e(t('Assignee')) ?></label>
        <div class="form-control bg-light"><i class="bi bi-person"></i> <?= e($fixedAssignee ?: t('Unassigned')) ?></div>
        <div class="form-text"><?= e($task ? t('Only a project manager can change who this task is assigned to.') : t('Tasks you add are assigned to you.')) ?></div>
      </div>
      <?php else: ?>
      <div class="col-md-6">
        <label class="form-label" for="assignee_id"><?= e(t('Assignee')) ?></label>
        <select class="form-select" id="assignee_id" name="assignee_id">
          <option value=""><?= e(t('Unassigned')) ?></option>
          <?php $sel = $v('assignee_id', $parent['assignee_id'] ?? ''); ?>
          <?php if ($inProject): ?>
            <optgroup label="<?= e(t('Project team')) ?>"><?php foreach ($inProject as $u) echo $userOption($u, $sel); ?></optgroup>
          <?php endif; ?>
          <?php if ($others): ?>
            <optgroup label="<?= e(t('Others (will be added to the team)')) ?>"><?php foreach ($others as $u) echo $userOption($u, $sel); ?></optgroup>
          <?php endif; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-6 col-md-3">
        <label class="form-label" for="status"><?= e(t('Status')) ?></label>
        <select class="form-select" id="status" name="status">
          <?php foreach (task_statuses() as $k => $s): ?><option value="<?= e($k) ?>"<?= selected($v('status', 'todo'), $k) ?>><?= e($s['label']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="priority"><?= e(t('Priority')) ?></label>
        <select class="form-select" id="priority" name="priority">
          <?php foreach (priorities() as $k => $s): ?><option value="<?= e($k) ?>"<?= selected($v('priority', 'medium'), $k) ?>><?= e($s['label']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <?php if (!$canAssign): ?>
      <div class="col-md-6">
        <label class="form-label"><?= e(t('Weightage')) ?></label>
        <div class="form-control bg-light"><?= weight_stars((int)($tk['weightage'] ?? 3)) ?></div>
        <div class="form-text"><?= e(t('Your project manager sets the weightage.')) ?></div>
      </div>
      <?php else: ?>
      <div class="col-md-6">
        <label class="form-label"><?= e(t('Weightage')) ?></label>
        <div class="d-flex flex-wrap gap-1" role="radiogroup">
          <?php $w = (int)$v('weightage', 3);
          $wLabels = [1 => t('Very small'), 2 => t('Small'), 3 => t('Medium'), 4 => t('Large'), 5 => t('Critical')];
          foreach ($wLabels as $n => $lbl): ?>
            <input type="radio" class="btn-check" name="weightage" id="w<?= $n ?>" value="<?= $n ?>"<?= checked($w === $n) ?>>
            <label class="btn btn-outline-warning text-dark" for="w<?= $n ?>"><strong><?= $n ?></strong> <span class="small d-none d-sm-inline"><?= e($lbl) ?></span></label>
          <?php endforeach; ?>
        </div>
        <div class="form-text"><?= e(t('How important or large this task is (1 = very small, 5 = critical). Used for progress % and later for performance scores.')) ?></div>
      </div>
      <?php endif; ?>
      <div class="col-md-6">
        <label class="form-label" for="estimated_hours_h"><?= e(t('Estimated time')) ?></label>
        <?php $durName = 'estimated_hours'; $durMinutes = ($tk['estimated_hours'] ?? null) !== null ? (int)round((float)$tk['estimated_hours'] * 60) : null;
        $durMaxHours = 9999; $durQuick = []; $durSmall = false; $durLabelId = 'estimated_hours_h';
        require APP_ROOT . '/views/partials/duration_input.php'; ?>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="start_date"><?= e(t('Start date')) ?></label>
        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($v('start_date', $task ? '' : today())) ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="due_date"><?= e(t('Due date')) ?></label>
        <input type="date" class="form-control" id="due_date" name="due_date" value="<?= e($v('due_date', $parent['due_date'] ?? '')) ?>" data-original="<?= e($tk['due_date'] ?? '') ?>">
      </div>
      <?php if ($task): ?>
      <div class="col-12 d-none" data-reason-for="due_date">
        <div class="alert alert-warning mb-0 py-2">
          <label class="form-label mb-1" for="due_reason"><i class="bi bi-exclamation-triangle"></i> <?= e(t('Reason for changing the due date')) ?> <span class="text-danger">*</span></label>
          <input class="form-control" id="due_reason" name="due_reason" maxlength="500" value="<?= e(old('due_reason')) ?>">
          <div class="form-text"><?= e(t('This is saved in the audit log.')) ?></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-footer bg-white d-flex flex-wrap gap-2">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= e(t('Save')) ?></button>
    <?php if (!$task): ?><button class="btn btn-outline-primary" name="save_and_new" value="1"><?= e(t('Save and add another')) ?></button><?php endif; ?>
    <a href="<?= e($cancel) ?>" class="btn btn-outline-secondary"><?= e(t('Cancel')) ?></a>
  </div>
</form>
