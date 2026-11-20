<h2 class="h6 mb-3"><?= e(t('Forgot password')) ?></h2>
<?php if ($sent): ?>
  <div class="alert alert-success"><?= e(t('If that email belongs to an active account, a reset link has been sent. Check your inbox (and spam folder).')) ?></div>
  <?php if (!mail_configured()): ?>
    <div class="alert alert-warning small mb-0"><?= e(t('Email is not set up on this system yet, so no link could be sent. Please ask your administrator to reset your password.')) ?></div>
  <?php endif; ?>
<?php else: ?>
  <p class="text-muted small"><?= e(t('Enter your email and we will send you a link to choose a new password.')) ?></p>
  <form method="post" action="<?= e(url('forgot')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label" for="email"><?= e(t('Email')) ?></label>
      <input type="email" class="form-control" id="email" name="email" required autofocus>
    </div>
    <button class="btn btn-primary w-100"><?= e(t('Send reset link')) ?></button>
  </form>
<?php endif; ?>
<div class="text-center mt-3"><a href="<?= e(url('login')) ?>" class="small"><i class="bi bi-arrow-left"></i> <?= e(t('Back to login')) ?></a></div>
