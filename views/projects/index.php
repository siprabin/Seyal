<div class="page-head">
  <h1><?= e(t('Projects')) ?></h1>
  <div class="actions">
    <?php if (can('projects.create')): ?>
      <a href="<?= e(url('projects', ['a' => 'create'])) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(t('New project')) ?></a>
    <?php endif; ?>
  </div>
</div>

<form class="row g-2 mb-3 filters js-autosubmit" method="get">
  <input type="hidden" name="p" value="projects">
  <div class="col-12 col-md-4 col-lg-3">
    <input type="search" class="form-control" name="q" value="<?= e($search) ?>" placeholder="<?= e(t('Search name or code')) ?>">
  </div>
  <div class="col-6 col-md-auto">
    <select name="status" class="form-select">
      <option value="open"<?= selected($status, 'open') ?>><?= e(t('Open projects')) ?></option>
      <option value="all"<?= selected($status, 'all') ?>><?= e(t('All statuses')) ?></option>
      <?php foreach (project_statuses() as $k => $s): ?>
        <option value="<?= e($k) ?>"<?= selected($status, $k) ?>><?= e($s['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto">
    <select name="client" class="form-select">
      <option value=""><?= e(t('All clients')) ?></option>
      <option value="-1"<?= selected($clientId, -1) ?>><?= e(t('Internal')) ?></option>
      <?php foreach ($clients as $c): ?>
        <option value="<?= (int)$c['id'] ?>"<?= selected($clientId, $c['id']) ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto">
    <select name="department" class="form-select">
      <option value=""><?= e(t('All departments')) ?></option>
      <?php foreach ($departments as $d): ?>
        <option value="<?= (int)$d['id'] ?>"<?= selected($deptId, $d['id']) ?>><?= e($d['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button></div>
</form>

<?php if (!$projects): ?>
  <div class="card"><div class="card-body text-center text-muted py-5">
    <i class="bi bi-kanban fs-1 d-block mb-2"></i><?= e(t('No projects found.')) ?>
  </div></div>
<?php else: ?>
<div class="card table-card">
  <table class="table table-hover">
    <thead class="table-light">
      <tr>
        <th><?= e(t('Project')) ?></th>
        <th class="d-none d-md-table-cell"><?= e(t('Client')) ?></th>
        <th class="d-none d-lg-table-cell"><?= e(t('Manager')) ?></th>
        <th><?= e(t('Status')) ?></th>
        <th style="min-width:140px"><?= e(t('Progress')) ?></th>
        <th class="d-none d-md-table-cell"><?= e(t('Target date')) ?></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($projects as $p): $pr = $progress[(int)$p['id']];
      $late = $p['target_date'] && $p['target_date'] < today() && !in_array($p['status'], ['completed', 'cancelled'], true); ?>
      <tr>
        <td>
          <a href="<?= e(url('projects', ['a' => 'view', 'id' => $p['id']])) ?>" class="fw-semibold text-decoration-none"><?= e($p['name']) ?></a>
          <?php if ($p['code']): ?><span class="text-muted small ms-1"><?= e($p['code']) ?></span><?php endif; ?>
          <?php if ($p['department_name']): ?><div class="small text-muted"><?= e($p['department_name']) ?></div><?php endif; ?>
        </td>
        <td class="d-none d-md-table-cell"><?= $p['client_name'] ? e($p['client_name']) : '<span class="text-muted">' . e(t('Internal')) . '</span>' ?></td>
        <td class="d-none d-lg-table-cell"><?= $p['manager_name'] ? avatar($p['manager_name'], 'avatar-sm') . ' ' . e($p['manager_name']) : '' ?></td>
        <td><?= badge(project_statuses(), $p['status']) ?></td>
        <td>
          <?= progress_bar($pr['pct']) ?>
          <div class="small text-muted mt-1"><?= $pr['pct'] ?>% · <?= e(t(':done of :total tasks', ['done' => $pr['done'], 'total' => $pr['total']])) ?>
            <?php if ($pr['overdue']): ?><span class="text-overdue"> · <?= e(t(':n overdue', ['n' => $pr['overdue']])) ?></span><?php endif; ?>
          </div>
        </td>
        <td class="d-none d-md-table-cell<?= $late ? ' text-overdue' : '' ?>"><?= e(fmt_date($p['target_date'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
