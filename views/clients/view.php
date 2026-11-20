<div class="mb-2 small"><a href="<?= e(url('clients')) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('Clients')) ?></a></div>
<div class="page-head">
  <h1><?= e($client['name']) ?> <?php if (!$client['is_active']): ?><span class="badge bg-secondary fs-6"><?= e(t('Inactive')) ?></span><?php endif; ?></h1>
  <div class="actions">
    <?php if (can('clients.manage')): ?>
      <a href="<?= e(url('clients', ['a' => 'edit', 'id' => $client['id']])) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> <?= e(t('Edit')) ?></a>
      <form method="post" action="<?= e(url('clients', ['a' => 'delete', 'id' => $client['id']])) ?>" data-confirm="<?= e(t('Delete this client?')) ?>">
        <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> <?= e(t('Delete')) ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><?= e(t('Contact details')) ?></div>
      <div class="card-body">
        <dl class="mb-0 small">
          <dt class="text-muted fw-normal"><?= e(t('Contact person')) ?></dt><dd><?= e($client['contact_person'] ?: '—') ?></dd>
          <dt class="text-muted fw-normal"><?= e(t('Email')) ?></dt><dd><?= $client['email'] ? '<a href="mailto:' . e($client['email']) . '">' . e($client['email']) . '</a>' : '—' ?></dd>
          <dt class="text-muted fw-normal"><?= e(t('Phone')) ?></dt><dd><?= e($client['phone'] ?: '—') ?></dd>
          <dt class="text-muted fw-normal"><?= e(t('Address')) ?></dt><dd style="white-space:pre-wrap"><?= e($client['address'] ?: '—') ?></dd>
          <?php if ($client['notes']): ?><dt class="text-muted fw-normal"><?= e(t('Notes')) ?></dt><dd style="white-space:pre-wrap"><?= e($client['notes']) ?></dd><?php endif; ?>
        </dl>
      </div>
    </div>

    <div class="card mt-3" id="portal">
      <div class="card-header"><i class="bi bi-door-open"></i> <?= e(t('Client portal access')) ?></div>
      <div class="card-body small">
        <p class="text-muted"><?= e(t('People at this client can log in to see progress of their projects (read-only). They never see hours, ratings, comments, files or AI usage.')) ?></p>
        <?php foreach ($portalUsers as $pu): ?>
          <div class="border rounded p-2 mb-2">
            <div class="d-flex align-items-center gap-2">
              <strong class="flex-grow-1"><?= e($pu['name']) ?></strong>
              <?= $pu['is_active'] ? '<span class="badge bg-success">' . e(t('Active')) . '</span>' : '<span class="badge bg-secondary">' . e(t('Inactive')) . '</span>' ?>
            </div>
            <div class="text-muted ltr"><?= e($pu['email']) ?></div>
            <div class="text-muted"><?= e(t('Last login')) ?>: <?= e($pu['last_login_at'] ? time_ago($pu['last_login_at']) : t('Never')) ?></div>
            <?php if (can('clients.manage')): ?>
            <div class="d-flex flex-wrap gap-1 mt-2">
              <form method="post" action="<?= e(url('clients', ['a' => 'portal_user', 'id' => $client['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="uid" value="<?= (int)$pu['id'] ?>"><input type="hidden" name="toggle" value="1">
                <button class="btn btn-sm btn-outline-secondary py-0"><?= e($pu['is_active'] ? t('Deactivate') : t('Activate')) ?></button></form>
              <form method="post" action="<?= e(url('clients', ['a' => 'portal_user', 'id' => $client['id']])) ?>" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="uid" value="<?= (int)$pu['id'] ?>">
                <input type="password" name="password" class="form-control form-control-sm py-0" minlength="8" required placeholder="<?= e(t('New password')) ?>" autocomplete="new-password" style="max-width:150px">
                <button class="btn btn-sm btn-outline-primary py-0"><?= e(t('Set')) ?></button></form>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (!$portalUsers): ?><p class="text-muted mb-2"><?= e(t('No portal logins yet.')) ?></p><?php endif; ?>
        <?php if (can('clients.manage')): ?>
          <form method="post" action="<?= e(url('clients', ['a' => 'portal_user', 'id' => $client['id']])) ?>" class="border-top pt-2 mt-2" autocomplete="off">
            <?= csrf_field() ?>
            <div class="fw-semibold mb-1"><?= e(t('Add a portal login')) ?></div>
            <input class="form-control form-control-sm mb-1" name="name" required maxlength="120" placeholder="<?= e(t('Full name')) ?>">
            <input type="email" class="form-control form-control-sm mb-1" name="email" required placeholder="<?= e(t('Email')) ?>">
            <input type="password" class="form-control form-control-sm mb-1" name="password" minlength="8" required placeholder="<?= e(t('Password')) ?>" autocomplete="new-password">
            <select class="form-select form-select-sm mb-2" name="lang" aria-label="<?= e(t('Language')) ?>">
              <?php foreach (SUPPORTED_LANGS as $code => $label): ?><option value="<?= e($code) ?>"<?= selected(setting('default_lang', 'en'), $code) ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> <?= e(t('Create login')) ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card table-card">
      <div class="card-header"><?= e(t('Projects')) ?></div>
      <?php if (!$projects): ?>
        <div class="card-body text-muted"><?= e(t('No projects for this client yet.')) ?></div>
      <?php else: ?>
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th><?= e(t('Project')) ?></th><th><?= e(t('Status')) ?></th><th style="min-width:120px"><?= e(t('Progress')) ?></th><th><?= e(t('Target date')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($projects as $p): $pr = $progress[(int)$p['id']]; ?>
          <tr>
            <td><a href="<?= e(url('projects', ['a' => 'view', 'id' => $p['id']])) ?>" class="text-decoration-none"><?= e($p['name']) ?></a><div class="small text-muted"><?= e($p['manager_name']) ?></div></td>
            <td><?= badge(project_statuses(), $p['status']) ?></td>
            <td><?= progress_bar($pr['pct']) ?><span class="small text-muted"><?= $pr['pct'] ?>%</span></td>
            <td><?= e(fmt_date($p['target_date'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
