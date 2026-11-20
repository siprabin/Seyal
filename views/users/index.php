<div class="page-head">
  <h1><?= e(t('Users')) ?></h1>
  <div class="actions">
    <a href="<?= e(url('orgchart')) ?>" class="btn btn-outline-secondary"><i class="bi bi-diagram-3"></i> <?= e(t('Org Chart')) ?></a>
    <?php if (can('users.manage')): ?>
      <a href="<?= e(url('users', ['a' => 'create'])) ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> <?= e(t('New user')) ?></a>
    <?php endif; ?>
  </div>
</div>
<form class="row g-2 mb-3 filters js-autosubmit" method="get">
  <input type="hidden" name="p" value="users">
  <div class="col-12 col-md-4 col-lg-3"><input type="search" class="form-control" name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search name, email, designation')) ?>"></div>
  <div class="col-6 col-md-auto">
    <select name="department" class="form-select">
      <option value=""><?= e(t('All departments')) ?></option>
      <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>"<?= selected($f['department'], $d['id']) ?>><?= e($d['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto">
    <select name="role" class="form-select">
      <option value=""><?= e(t('All roles')) ?></option>
      <?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>"<?= selected($f['role'], $r['id']) ?>><?= e(role_label($r['slug'])) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto">
    <select name="show" class="form-select">
      <option value="active"<?= selected($f['show'], 'active') ?>><?= e(t('Active')) ?></option>
      <option value="inactive"<?= selected($f['show'], 'inactive') ?>><?= e(t('Inactive')) ?></option>
      <option value="all"<?= selected($f['show'], 'all') ?>><?= e(t('All')) ?></option>
    </select>
  </div>
</form>
<div class="card table-card">
  <table class="table table-hover">
    <thead class="table-light"><tr>
      <th><?= e(t('Name')) ?></th><th class="d-none d-md-table-cell"><?= e(t('Role')) ?></th>
      <th class="d-none d-md-table-cell"><?= e(t('Department')) ?></th><th class="d-none d-lg-table-cell"><?= e(t('Reports to')) ?></th>
      <th class="d-none d-lg-table-cell"><?= e(t('Last login')) ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td>
          <div class="d-flex align-items-center gap-2">
            <?= avatar($u['name']) ?>
            <div class="min-w-0">
              <a href="<?= e(url('users', ['a' => 'view', 'id' => $u['id']])) ?>" class="fw-semibold text-decoration-none"><?= e($u['name']) ?></a>
              <?php if (!$u['is_active']): ?><span class="badge bg-secondary ms-1"><?= e(t('Inactive')) ?></span><?php endif; ?>
              <div class="small text-muted text-truncate"><?= e($u['designation'] ?: $u['email']) ?></div>
            </div>
          </div>
        </td>
        <td class="d-none d-md-table-cell small"><?= e(role_label($u['role'])) ?></td>
        <td class="d-none d-md-table-cell small"><?= e($u['department_name']) ?></td>
        <td class="d-none d-lg-table-cell small"><?= e($u['manager_name']) ?></td>
        <td class="d-none d-lg-table-cell small text-muted"><?= e($u['last_login_at'] ? time_ago($u['last_login_at']) : t('Never')) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$users): ?><tr><td colspan="5" class="text-center text-muted py-4"><?= e(t('No users found.')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
