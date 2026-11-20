<div class="page-head"><h1><?= e(t('My Profile')) ?></h1></div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body text-center">
        <?= avatar($me['name'], 'avatar-lg') ?>
        <h2 class="h5 mt-2 mb-0"><?= e($me['name']) ?></h2>
        <div class="text-muted small"><?= e($me['designation']) ?></div>
        <span class="badge bg-primary-subtle text-primary-emphasis mt-2"><?= e(role_label($me['role'])) ?></span>
      </div>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item"><i class="bi bi-envelope me-2 text-muted"></i><?= e($me['email']) ?></li>
        <li class="list-group-item"><i class="bi bi-collection me-2 text-muted"></i><?= e($me['department_name'] ?: '—') ?></li>
        <li class="list-group-item"><i class="bi bi-person-up me-2 text-muted"></i><?= e(t('Reports to')) ?>: <?= e($manager['name'] ?? '—') ?></li>
      </ul>
      <div class="card-footer bg-white small text-muted"><?= e(t('To change your name, email, role or manager, ask an administrator.')) ?></div>
    </div>
  </div>
  <div class="col-lg-8">
    <form method="post" action="<?= e(url('profile')) ?>" class="card mb-3">
      <?= csrf_field() ?>
      <div class="card-header"><?= e(t('Preferences')) ?></div>
      <div class="card-body row g-3">
        <div class="col-md-6">
          <label class="form-label" for="phone"><?= e(t('Phone')) ?></label>
          <input class="form-control" id="phone" name="phone" value="<?= e($me['phone']) ?>" maxlength="40">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="lang"><?= e(t('Language')) ?></label>
          <select class="form-select" id="lang" name="lang">
            <?php foreach (SUPPORTED_LANGS as $code => $label): ?><option value="<?= e($code) ?>"<?= selected(current_lang(), $code) ?>><?= e($label) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary"><?= e(t('Save')) ?></button></div>
    </form>
    <form method="post" action="<?= e(url('profile', ['a' => 'password'])) ?>" class="card">
      <?= csrf_field() ?>
      <div class="card-header"><?= e(t('Change password')) ?></div>
      <div class="card-body row g-3">
        <div class="col-md-4">
          <label class="form-label" for="current_password"><?= e(t('Current password')) ?></label>
          <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="password"><?= e(t('New password')) ?></label>
          <input type="password" class="form-control" id="password" name="password" minlength="8" required autocomplete="new-password">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="password_confirm"><?= e(t('Confirm new password')) ?></label>
          <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
        </div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary"><?= e(t('Change password')) ?></button></div>
    </form>
  </div>
</div>
