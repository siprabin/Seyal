<div class="page-head">
  <h1><?= e(t('Departments')) ?></h1>
  <div class="actions"><a href="<?= e(url('departments', ['a' => 'create'])) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(t('New department')) ?></a></div>
</div>
<div class="card table-card">
  <?php if (!$departments): ?>
    <div class="card-body text-center text-muted py-5"><?= e(t('No departments yet.')) ?></div>
  <?php else: ?>
  <table class="table table-hover">
    <thead class="table-light"><tr>
      <th><?= e(t('Department')) ?></th><th><?= e(t('Department head')) ?></th>
      <th class="text-end"><?= e(t('People')) ?></th><th class="text-end"><?= e(t('Projects')) ?></th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($departments as $d): ?>
      <tr>
        <td><strong><?= e($d['name']) ?></strong><?php if ($d['description']): ?><div class="small text-muted"><?= e($d['description']) ?></div><?php endif; ?></td>
        <td><?= $d['head_name'] ? avatar($d['head_name'], 'avatar-sm') . ' ' . e($d['head_name']) : '<span class="text-muted">—</span>' ?></td>
        <td class="text-end"><a href="<?= e(url('users', ['department' => $d['id']])) ?>"><?= (int)$d['user_count'] ?></a></td>
        <td class="text-end"><a href="<?= e(url('projects', ['department' => $d['id'], 'status' => 'all'])) ?>"><?= (int)$d['project_count'] ?></a></td>
        <td class="text-end text-nowrap">
          <a href="<?= e(url('departments', ['a' => 'edit', 'id' => $d['id']])) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
          <form method="post" action="<?= e(url('departments', ['a' => 'delete', 'id' => $d['id']])) ?>" class="d-inline" data-confirm="<?= e(t('Delete this department? People and projects in it will be left without a department.')) ?>">
            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
