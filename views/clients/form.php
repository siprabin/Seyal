<?php $c = $client ?? []; $v = fn($k, $d = '') => old($k, $c[$k] ?? $d); ?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="name"><?= e(t('Client name')) ?> <span class="text-danger">*</span></label>
        <input class="form-control" id="name" name="name" value="<?= e($v('name')) ?>" maxlength="160" required autofocus>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="contact_person"><?= e(t('Contact person')) ?></label>
        <input class="form-control" id="contact_person" name="contact_person" value="<?= e($v('contact_person')) ?>" maxlength="120">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="email"><?= e(t('Email')) ?></label>
        <input type="email" class="form-control" id="email" name="email" value="<?= e($v('email')) ?>" maxlength="190">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="phone"><?= e(t('Phone')) ?></label>
        <input class="form-control" id="phone" name="phone" value="<?= e($v('phone')) ?>" maxlength="40">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="address"><?= e(t('Address')) ?></label>
        <textarea class="form-control" id="address" name="address" rows="3"><?= e($v('address')) ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="notes"><?= e(t('Notes')) ?></label>
        <textarea class="form-control" id="notes" name="notes" rows="3"><?= e($v('notes')) ?></textarea>
      </div>
      <div class="col-12">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"<?= checked($v('is_active', 1)) ?>>
          <label class="form-check-label" for="is_active"><?= e(t('Active')) ?></label>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer bg-white d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= e(t('Save')) ?></button>
    <a href="<?= e($client ? url('clients', ['a' => 'view', 'id' => $client['id']]) : url('clients')) ?>" class="btn btn-outline-secondary"><?= e(t('Cancel')) ?></a>
  </div>
</form>
