<?php /** Expects $pg from paginate(). */ if ($pg['pages'] > 1): ?>
<nav class="mt-3" aria-label="<?= e(t('Pages')) ?>">
  <ul class="pagination pagination-sm flex-wrap">
    <li class="page-item<?= $pg['page'] <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(current_url_with(['page' => $pg['page'] - 1])) ?>">&laquo;</a></li>
    <?php for ($i = max(1, $pg['page'] - 3); $i <= min($pg['pages'], $pg['page'] + 3); $i++): ?>
      <li class="page-item<?= $i === $pg['page'] ? ' active' : '' ?>"><a class="page-link" href="<?= e(current_url_with(['page' => $i])) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
    <li class="page-item<?= $pg['page'] >= $pg['pages'] ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(current_url_with(['page' => $pg['page'] + 1])) ?>">&raquo;</a></li>
  </ul>
</nav>
<?php endif; ?>
