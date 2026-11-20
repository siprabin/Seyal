<div class="page-head"><h1><?= e(t('Audit Log')) ?></h1>
  <div class="actions small text-muted align-self-center"><?= e(t(':n entries', ['n' => $pg['total']])) ?></div>
</div>
<form class="row g-2 mb-3 filters align-items-center" method="get">
  <input type="hidden" name="p" value="audit">
  <div class="col-12 col-md-3"><input type="search" class="form-control form-control-sm" name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('Search')) ?>"></div>
  <div class="col-6 col-md-auto">
    <select name="type" class="form-select form-select-sm">
      <option value=""><?= e(t('Everything')) ?></option>
      <?php foreach ($types as $tp): ?><option value="<?= e($tp) ?>"<?= selected($f['type'], $tp) ?>><?= e(audit_entity_label($tp)) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto">
    <select name="user" class="form-select form-select-sm">
      <option value=""><?= e(t('All people')) ?></option>
      <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"<?= selected($f['user'], $u['id']) ?>><?= e($u['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto"><input type="date" class="form-control form-control-sm" name="from" value="<?= e($f['from']) ?>" aria-label="<?= e(t('From')) ?>"></div>
  <div class="col-6 col-md-auto"><input type="date" class="form-control form-control-sm" name="to" value="<?= e($f['to']) ?>" aria-label="<?= e(t('To')) ?>"></div>
  <div class="col-auto">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="due" name="due" value="1"<?= checked($f['due']) ?>>
      <label class="form-check-label small" for="due"><?= e(t('Only due-date changes')) ?></label>
    </div>
  </div>
  <div class="col-auto"><button class="btn btn-sm btn-primary"><?= e(t('Filter')) ?></button>
    <a class="btn btn-sm btn-link" href="<?= e(url('audit')) ?>"><?= e(t('Clear')) ?></a></div>
</form>
<div class="card"><div class="card-body">
  <?php if (!$entries): ?><p class="text-muted mb-0"><?= e(t('No matching entries.')) ?></p><?php endif; ?>
  <?php foreach ($entries as $h) { require APP_ROOT . '/views/partials/history_item.php'; } ?>
</div></div>
<?php require APP_ROOT . '/views/partials/pagination.php'; ?>
