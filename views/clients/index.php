<div class="page-head">
  <h1><?= e(t('Clients')) ?></h1>
  <div class="actions">
    <?php if (can('clients.manage')): ?>
      <a href="<?= e(url('clients', ['a' => 'create'])) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(t('New client')) ?></a>
    <?php endif; ?>
  </div>
</div>
<form class="row g-2 mb-3 filters js-autosubmit" method="get">
  <input type="hidden" name="p" value="clients">
  <div class="col-12 col-md-4"><input type="search" class="form-control" name="q" value="<?= e($search) ?>" placeholder="<?= e(t('Search')) ?>"></div>
  <div class="col-auto">
    <select name="show" class="form-select">
      <option value="active"<?= selected($show, 'active') ?>><?= e(t('Active')) ?></option>
      <option value="inactive"<?= selected($show, 'inactive') ?>><?= e(t('Inactive')) ?></option>
      <option value="all"<?= selected($show, 'all') ?>><?= e(t('All')) ?></option>
    </select>
  </div>
</form>
<div class="card table-card">
  <?php if (!$clients): ?>
    <div class="card-body text-center text-muted py-5"><i class="bi bi-building fs-1 d-block mb-2"></i><?= e(t('No clients found.')) ?></div>
  <?php else: ?>
  <table class="table table-hover">
    <thead class="table-light"><tr>
      <th><?= e(t('Client')) ?></th><th class="d-none d-md-table-cell"><?= e(t('Contact person')) ?></th>
      <th class="d-none d-lg-table-cell"><?= e(t('Email')) ?></th><th class="d-none d-lg-table-cell"><?= e(t('Phone')) ?></th>
      <th class="text-end"><?= e(t('Projects')) ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($clients as $c): ?>
      <tr>
        <td><a href="<?= e(url('clients', ['a' => 'view', 'id' => $c['id']])) ?>" class="fw-semibold text-decoration-none"><?= e($c['name']) ?></a>
          <?php if (!$c['is_active']): ?><span class="badge bg-secondary ms-1"><?= e(t('Inactive')) ?></span><?php endif; ?></td>
        <td class="d-none d-md-table-cell"><?= e($c['contact_person']) ?></td>
        <td class="d-none d-lg-table-cell"><?= e($c['email']) ?></td>
        <td class="d-none d-lg-table-cell"><?= e($c['phone']) ?></td>
        <td class="text-end"><?= (int)$c['open_count'] ?> <span class="text-muted small">/ <?= (int)$c['project_count'] ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<p class="small text-muted mt-2"><?= e(t('Projects column shows open / total projects.')) ?></p>
