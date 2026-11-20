<?php $d = $dept ?? []; $v = fn($k, $def = '') => old($k, $d[$k] ?? $def); ?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>
<form method="post" class="card" style="max-width:720px">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="mb-3">
      <label class="form-label" for="name"><?= e(t('Department name')) ?> <span class="text-danger">*</span></label>
      <input class="form-control" id="name" name="name" value="<?= e($v('name')) ?>" maxlength="120" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label" for="description"><?= e(t('Description')) ?></label>
      <textarea class="form-control" id="description" name="description" rows="2"><?= e($v('description')) ?></textarea>
    </div>
    <div class="mb-1">
      <label class="form-label" for="head_user_id"><?= e(t('Department head')) ?></label>
      <select class="form-select" id="head_user_id" name="head_user_id">
        <option value="">—</option>
        <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"<?= selected($v('head_user_id'), $u['id']) ?>><?= e($u['name']) ?></option><?php endforeach; ?>
      </select>
      <div class="form-text"><?= e(t('The department head can see and manage every project of this department.')) ?></div>
    </div>
  </div>
  <div class="card-footer bg-white d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= e(t('Save')) ?></button>
    <a href="<?= e(url('departments')) ?>" class="btn btn-outline-secondary"><?= e(t('Cancel')) ?></a>
  </div>
</form>
