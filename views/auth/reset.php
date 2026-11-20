<h2 class="h6 mb-3"><?= e(t('Reset password')) ?></h2>
<?php if (!$reset): ?>
  <div class="alert alert-warning"><?= e(t('This reset link is invalid or has expired. Please request a new one.')) ?></div>
  <a href="<?= e(url('forgot')) ?>" class="btn btn-primary w-100"><?= e(t('Request a new link')) ?></a>
<?php else: ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <p class="small text-muted"><?= e($reset['email']) ?></p>
  <form method="post" action="<?= e(url('reset', ['token' => $token])) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label" for="password"><?= e(t('New password')) ?></label>
      <input type="password" class="form-control" id="password" name="password" minlength="8" required autocomplete="new-password">
      <div class="form-text"><?= e(t('At least 8 characters.')) ?></div>
    </div>
    <div class="mb-3">
      <label class="form-label" for="password_confirm"><?= e(t('Confirm new password')) ?></label>
      <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
    </div>
    <button class="btn btn-primary w-100"><?= e(t('Save new password')) ?></button>
  </form>
<?php endif; ?>
<div class="text-center mt-3"><a href="<?= e(url('login')) ?>" class="small"><i class="bi bi-arrow-left"></i> <?= e(t('Back to login')) ?></a></div>
