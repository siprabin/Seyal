<?php require __DIR__ . '/_header.php'; ?>
<div class="card">
  <div class="card-body">
    <?php if (!$history): ?>
      <p class="text-muted mb-0"><?= e(t('No history yet.')) ?></p>
    <?php else: ?>
      <?php foreach ($history as $h) { require APP_ROOT . '/views/partials/history_item.php'; } ?>
    <?php endif; ?>
  </div>
</div>
