<div class="page-head"><h1><?= e(t('Roles & Permissions')) ?></h1></div>
<div class="alert alert-info small">
  <i class="bi bi-info-circle"></i>
  <?= e(t('Super Admin always has every permission. Whatever the settings below, everyone can always see projects they manage, are a member of, or have tasks in, and project managers always control their own projects.')) ?>
</div>
<form method="post">
  <?= csrf_field() ?>
  <div class="card table-card">
    <table class="table table-sm perm-table mb-0">
      <thead class="table-light">
        <tr>
          <th><?= e(t('Permission')) ?></th>
          <?php foreach ($roles as $r): ?><th class="rot text-center"><?= e(role_label($r['slug'])) ?></th><?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php $group = null; foreach ($perms as $key => [$g, $label]): ?>
          <?php if ($g !== $group): $group = $g; ?>
            <tr class="table-group-divider"><th colspan="<?= count($roles) + 1 ?>" class="bg-light small text-uppercase text-muted"><?= e(t($g)) ?></th></tr>
          <?php endif; ?>
          <tr>
            <td><?= e(t($label)) ?></td>
            <?php foreach ($roles as $r): ?>
              <td><input class="form-check-input" type="checkbox" name="perm[<?= (int)$r['id'] ?>][<?= e($key) ?>]" value="1"
                aria-label="<?= e(role_label($r['slug']) . ': ' . t($label)) ?>"<?= checked(!empty($current[(int)$r['id']][$key])) ?>></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex gap-2 mt-3">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= e(t('Save permissions')) ?></button>
    <button class="btn btn-outline-secondary" name="reset" value="1" formnovalidate data-confirm-click="1"
      onclick="return confirm(<?= e(json_encode(t('Reset every role to the default permissions?'), JSON_UNESCAPED_UNICODE)) ?>)"><?= e(t('Reset to defaults')) ?></button>
  </div>
</form>
