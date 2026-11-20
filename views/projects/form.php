<?php
$p = $project ?? [];
$v = fn($k, $d = '') => old($k, $p[$k] ?? $d);
?>
<div class="page-head">
  <h1><?= e($pageTitle) ?></h1>
</div>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label" for="name"><?= e(t('Project name')) ?> <span class="text-danger">*</span></label>
        <input class="form-control" id="name" name="name" value="<?= e($v('name')) ?>" maxlength="200" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="code"><?= e(t('Code')) ?></label>
        <input class="form-control" id="code" name="code" value="<?= e($v('code')) ?>" maxlength="30" placeholder="<?= e(t('e.g. WEB-2026')) ?>">
      </div>
      <div class="col-12">
        <label class="form-label" for="description"><?= e(t('Description')) ?></label>
        <textarea class="form-control" id="description" name="description" rows="3"><?= e($v('description')) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="client_id"><?= e(t('Client')) ?></label>
        <select class="form-select" id="client_id" name="client_id">
          <option value=""><?= e(t('Internal (no client)')) ?></option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= (int)$c['id'] ?>"<?= selected($v('client_id'), $c['id']) ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4 order-md-last">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="client_visible" name="client_visible" value="1"<?= checked($project ? $v('client_visible', 1) : old('client_visible', 1)) ?>>
          <label class="form-check-label small" for="client_visible"><?= e(t('Show in client portal')) ?></label>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="department_id"><?= e(t('Department')) ?></label>
        <select class="form-select" id="department_id" name="department_id">
          <option value="">—</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= (int)$d['id'] ?>"<?= selected($v('department_id', user()['department_id']), $d['id']) ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="manager_id"><?= e(t('Project manager')) ?></label>
        <select class="form-select" id="manager_id" name="manager_id">
          <option value="">—</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"<?= selected($v('manager_id', $project ? '' : uid()), $u['id']) ?>><?= e($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="status"><?= e(t('Status')) ?></label>
        <select class="form-select" id="status" name="status">
          <?php foreach (project_statuses() as $k => $s): ?>
            <option value="<?= e($k) ?>"<?= selected($v('status', 'planning'), $k) ?>><?= e($s['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="priority"><?= e(t('Priority')) ?></label>
        <select class="form-select" id="priority" name="priority">
          <?php foreach (priorities() as $k => $s): ?>
            <option value="<?= e($k) ?>"<?= selected($v('priority', 'medium'), $k) ?>><?= e($s['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="start_date"><?= e(t('Start date')) ?></label>
        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($v('start_date')) ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="target_date"><?= e(t('Target date')) ?></label>
        <input type="date" class="form-control" id="target_date" name="target_date" value="<?= e($v('target_date')) ?>" data-original="<?= e($p['target_date'] ?? '') ?>">
      </div>
      <?php if ($project): ?>
      <div class="col-12 d-none" data-reason-for="target_date">
        <div class="alert alert-warning mb-0 py-2">
          <label class="form-label mb-1" for="target_reason"><i class="bi bi-exclamation-triangle"></i> <?= e(t('Reason for changing the target date')) ?> <span class="text-danger">*</span></label>
          <input class="form-control" id="target_reason" name="target_reason" maxlength="500" value="<?= e(old('target_reason')) ?>">
          <div class="form-text"><?= e(t('This is saved in the audit log.')) ?></div>
        </div>
      </div>
      <?php endif; ?>
      <div class="col-12">
        <label class="form-label" for="members"><?= e(t('Team members')) ?></label>
        <select class="form-select" id="members" name="members[]" multiple size="8">
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"<?= in_array((int)$u['id'], $memberIds, true) ? ' selected' : '' ?>>
              <?= e($u['name']) ?><?= $u['designation'] ? ' — ' . e($u['designation']) : '' ?><?= $u['department_name'] ? ' (' . e($u['department_name']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text"><?= e(t('Hold Ctrl (Cmd on Mac) to pick several people. Anyone assigned a task is added automatically.')) ?></div>
      </div>
    </div>
  </div>
  <div class="card-footer bg-white d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= e(t('Save')) ?></button>
    <a href="<?= e($project ? url('projects', ['a' => 'view', 'id' => $project['id']]) : url('projects')) ?>" class="btn btn-outline-secondary"><?= e(t('Cancel')) ?></a>
  </div>
</form>
