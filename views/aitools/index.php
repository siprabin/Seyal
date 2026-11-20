<div class="page-head"><h1><?= e(t('AI Tools')) ?></h1></div>
<p class="text-muted small"><?= e(t('These are the tools people can pick when they log AI usage. Hidden tools stay in old records but cannot be chosen any more.')) ?></p>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card table-card">
      <table class="table table-sm align-middle">
        <thead class="table-light"><tr>
          <th class="ps-3"><?= e(t('Order')) ?></th><th><?= e(t('Name')) ?></th><th class="text-end"><?= e(t('Times used')) ?></th>
          <th class="d-none d-md-table-cell"><?= e(t('Last used')) ?></th><th><?= e(t('Status')) ?></th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($tools as $tool): ?>
          <tr class="<?= $tool['is_active'] ? '' : 'text-muted' ?>">
            <td class="ps-3" colspan="2">
              <form method="post" action="<?= e(url('aitools', ['a' => 'save'])) ?>" class="d-flex gap-1">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$tool['id'] ?>">
                <input type="number" class="form-control form-control-sm" name="sort_order" value="<?= (int)$tool['sort_order'] ?>" style="width:70px" aria-label="<?= e(t('Order')) ?>">
                <input class="form-control form-control-sm" name="name" value="<?= e($tool['name']) ?>" maxlength="80" required aria-label="<?= e(t('Name')) ?>">
                <button class="btn btn-sm btn-outline-secondary" title="<?= e(t('Save')) ?>"><i class="bi bi-check-lg"></i></button>
              </form>
            </td>
            <td class="text-end"><?= (int)$tool['uses'] ?></td>
            <td class="d-none d-md-table-cell small"><?= e($tool['last_used'] ? time_ago($tool['last_used']) : t('Never')) ?></td>
            <td><?= $tool['is_active'] ? '<span class="badge bg-success">' . e(t('Active')) . '</span>' : '<span class="badge bg-secondary">' . e(t('Hidden')) . '</span>' ?></td>
            <td class="text-end text-nowrap">
              <form method="post" action="<?= e(url('aitools', ['a' => 'toggle'])) ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$tool['id'] ?>">
                <button class="btn btn-sm btn-outline-secondary py-0"><?= e($tool['is_active'] ? t('Hide') : t('Show')) ?></button></form>
              <?php if (!$tool['uses']): ?>
              <form method="post" action="<?= e(url('aitools', ['a' => 'delete'])) ?>" class="d-inline" data-confirm="<?= e(t('Delete this tool?')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$tool['id'] ?>">
                <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button></form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <form method="post" action="<?= e(url('aitools', ['a' => 'save'])) ?>" class="card">
      <?= csrf_field() ?>
      <div class="card-header"><?= e(t('Add a tool')) ?></div>
      <div class="card-body">
        <input class="form-control mb-2" name="name" maxlength="80" required placeholder="<?= e(t('e.g. GitHub Copilot, Perplexity')) ?>">
        <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(t('Add')) ?></button>
      </div>
    </form>
  </div>
</div>
