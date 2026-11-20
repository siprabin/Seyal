<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('login')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="email"><?= e(t('Email')) ?></label>
    <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username">
  </div>
  <div class="mb-3">
    <label class="form-label" for="password"><?= e(t('Password')) ?></label>
    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
  </div>
  <button class="btn btn-primary w-100"><?= e(t('Log in')) ?></button>
  <div class="text-center mt-3"><a href="<?= e(url('forgot')) ?>" class="small"><?= e(t('Forgot password?')) ?></a></div>
</form>
