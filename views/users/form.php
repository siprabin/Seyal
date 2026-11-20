<?php $p = $person ?? []; $v = fn($k, $d = '') => old($k, $p[$k] ?? $d); ?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>
<form method="post" class="card" autocomplete="off">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="name"><?= e(t('Full name')) ?> <span class="text-danger">*</span></label>
        <input class="form-control" id="name" name="name" value="<?= e($v('name')) ?>" maxlength="120" required autofocus>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="email"><?= e(t('Email (used to log in)')) ?> <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email" value="<?= e($v('email')) ?>" maxlength="190" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="designation"><?= e(t('Designation')) ?></label>
        <input class="form-control" id="designation" name="designation" value="<?= e($v('designation')) ?>" maxlength="120" placeholder="<?= e(t('e.g. Senior Developer')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="phone"><?= e(t('Phone')) ?></label>
        <input class="form-control" id="phone" name="phone" value="<?= e($v('phone')) ?>" maxlength="40">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="role_id"><?= e(t('Role')) ?> <span class="text-danger">*</span></label>
        <select class="form-select" id="role_id" name="role_id" required<?= $isSelf ? ' disabled' : '' ?>>
          <option value="">—</option>
          <?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>"<?= selected($v('role_id', val("SELECT id FROM roles WHERE slug = 'member'")), $r['id']) ?>><?= e(role_label($r['slug'])) ?></option><?php endforeach; ?>
        </select>
        <?php if ($isSelf): ?><div class="form-text"><?= e(t('You cannot change your own role.')) ?></div><?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="department_id"><?= e(t('Department')) ?></label>
        <select class="form-select" id="department_id" name="department_id">
          <option value="">—</option>
          <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>"<?= selected($v('department_id'), $d['id']) ?>><?= e($d['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="reports_to"><?= e(t('Reports to')) ?></label>
        <select class="form-select" id="reports_to" name="reports_to">
          <option value="">—</option>
          <?php foreach ($managers as $m): ?><option value="<?= (int)$m['id'] ?>"<?= selected($v('reports_to'), $m['id']) ?>><?= e($m['name']) ?><?= $m['designation'] ? ' — ' . e($m['designation']) : '' ?></option><?php endforeach; ?>
        </select>
        <div class="form-text"><?= e(t('Their direct manager. Used for daily reports and task ratings.')) ?></div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="lang"><?= e(t('Language')) ?></label>
        <select class="form-select" id="lang" name="lang">
          <?php foreach (SUPPORTED_LANGS as $code => $label): ?><option value="<?= e($code) ?>"<?= selected($v('lang', setting('default_lang', 'en')), $code) ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-8 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"<?= checked($v('is_active', 1)) ?><?= $isSelf ? ' disabled' : '' ?>>
          <label class="form-check-label" for="is_active"><?= e(t('Active (can log in)')) ?></label>
        </div>
      </div>
      <div class="col-12"><hr class="my-1"></div>
      <div class="col-md-6">
        <label class="form-label" for="password"><?= e($person ? t('New password (leave blank to keep the current one)') : t('Password')) ?><?= $person ? '' : ' <span class="text-danger">*</span>' ?></label>
        <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password"<?= $person ? '' : ' required' ?>>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="password_confirm"><?= e(t('Confirm password')) ?></label>
        <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="8" autocomplete="new-password"<?= $person ? '' : ' required' ?>>
      </div>
    </div>
  </div>
  <div class="card-footer bg-white d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= e(t('Save')) ?></button>
    <a href="<?= e($person ? url('users', ['a' => 'view', 'id' => $person['id']]) : url('users')) ?>" class="btn btn-outline-secondary"><?= e(t('Cancel')) ?></a>
  </div>
</form>
