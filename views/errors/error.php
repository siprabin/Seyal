<?php $pageTitle = $title; ?>
<div class="text-center py-5">
  <div class="display-4 text-muted mb-2"><?= (int)$code ?></div>
  <h1 class="h4"><?= e($title) ?></h1>
  <p class="text-muted"><?= e($message) ?></p>
  <a href="<?= e(url('dashboard')) ?>" class="btn btn-primary"><?= e(t('Go to Dashboard')) ?></a>
</div>
