<?php
$host = fn(string $u) => preg_replace('#^https?://#', '', $u);
$ext = fn(string $u, ?string $text = null) => '<a href="' . e($u) . '" target="_blank" rel="noopener" class="ltr">' . e($text ?? $host($u)) . '</a>';
?>
<div class="row justify-content-center">
  <div class="col-lg-8 col-xl-7">
    <div class="card shadow-sm mb-3">
      <div class="card-body p-4 text-center">
        <img src="<?= asset('img/logo-mark.svg') ?>" alt="" width="72" height="72" class="mb-3" style="border-radius:16px">
        <h1 class="h3 mb-1"><?= e(APP_NAME) ?></h1>
        <p class="text-muted mb-2" dir="ltr">Plan it. Do it. Close the day.</p>
        <p class="mb-0 small"><?= powered_by_html(false) ?></p>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('App version')) ?></span><span class="ms-auto ltr"><?= e(APP_VERSION) ?></span></li>
        <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('Developed by')) ?></span><span class="ms-auto"><?= $ext(APP_AUTHOR_URL, APP_AUTHOR) ?></span></li>
        <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('Website')) ?></span><span class="ms-auto"><?= $ext(APP_AUTHOR_URL) ?></span></li>
        <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('Support')) ?></span><span class="ms-auto"><?= $ext(APP_SUPPORT_URL) ?></span></li>
        <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('Source code')) ?></span><span class="ms-auto"><?= $ext(APP_REPO_URL) ?></span></li>
        <?php if ($license !== ''): ?>
          <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('License')) ?></span><span class="ms-auto ltr"><?= $ext(APP_REPO_URL . '/blob/main/LICENSE', $license) ?></span></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold"><?= e(t('Third-party components')) ?></div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($components as [$name, $ver, $lic, $url]): ?>
          <li class="list-group-item d-flex gap-2"><span class="ltr"><?= $ext($url, trim($name . ' ' . $ver)) ?></span><span class="ms-auto text-muted ltr"><?= e($lic) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
