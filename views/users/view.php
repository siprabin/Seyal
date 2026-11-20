<div class="mb-2 small"><a href="<?= e(url('users')) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('Users')) ?></a></div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body text-center">
        <?= avatar($person['name'], 'avatar-lg') ?>
        <h1 class="h5 mt-2 mb-0"><?= e($person['name']) ?></h1>
        <div class="text-muted small"><?= e($person['designation']) ?></div>
        <div class="mt-2"><span class="badge bg-primary-subtle text-primary-emphasis"><?= e(role_label($person['role'])) ?></span>
          <?php if (!$person['is_active']): ?><span class="badge bg-secondary"><?= e(t('Inactive')) ?></span><?php endif; ?></div>
      </div>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item"><i class="bi bi-envelope me-2 text-muted"></i><a href="mailto:<?= e($person['email']) ?>"><?= e($person['email']) ?></a></li>
        <?php if ($person['phone']): ?><li class="list-group-item"><i class="bi bi-telephone me-2 text-muted"></i><?= e($person['phone']) ?></li><?php endif; ?>
        <li class="list-group-item"><i class="bi bi-collection me-2 text-muted"></i><?= e($person['department_name'] ?: '—') ?></li>
        <li class="list-group-item"><i class="bi bi-person-up me-2 text-muted"></i><?= e(t('Reports to')) ?>:
          <?= $person['reports_to'] ? '<a href="' . e(url('users', ['a' => 'view', 'id' => $person['reports_to']])) . '">' . e($person['manager_name']) . '</a>' : '—' ?></li>
        <li class="list-group-item text-muted"><i class="bi bi-clock me-2"></i><?= e(t('Last login')) ?>: <?= e($person['last_login_at'] ? fmt_datetime($person['last_login_at']) : t('Never')) ?></li>
      </ul>
      <?php if (can('users.manage') && ($person['role'] !== ROLE_SUPER_ADMIN || is_super_admin())): ?>
        <div class="card-footer bg-white"><a href="<?= e(url('users', ['a' => 'edit', 'id' => $person['id']])) ?>" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-pencil"></i> <?= e(t('Edit user')) ?></a></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><?= e(t('Direct reports')) ?> <span class="badge bg-light text-dark"><?= count($reports) ?></span></div>
      <?php if (!$reports): ?><div class="card-body text-muted small"><?= e(t('Nobody reports to this person.')) ?></div><?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($reports as $r): ?>
          <li class="list-group-item d-flex align-items-center gap-2"><?= avatar($r['name'], 'avatar-sm') ?>
            <a href="<?= e(url('users', ['a' => 'view', 'id' => $r['id']])) ?>" class="text-decoration-none"><?= e($r['name']) ?></a>
            <span class="small text-muted"><?= e($r['designation']) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-header"><?= e(t('Open tasks')) ?> <span class="badge bg-light text-dark"><?= count($tasks) ?></span></div>
      <?php if (!$tasks): ?><div class="card-body text-muted small"><?= e(t('No open tasks you can see.')) ?></div><?php else: ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($tasks as $t): $over = $t['due_date'] && $t['due_date'] < today(); ?>
          <li class="list-group-item d-flex align-items-center gap-2">
            <div class="min-w-0 flex-grow-1"><a href="<?= e(url('tasks', ['a' => 'view', 'id' => $t['id']])) ?>" class="text-decoration-none"><?= e($t['title']) ?></a>
              <div class="small text-muted"><?= e($t['project_name']) ?></div></div>
            <?= badge(task_statuses(), $t['status']) ?>
            <span class="small text-nowrap<?= $over ? ' text-overdue' : ' text-muted' ?>"><?= e(fmt_date($t['due_date'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
