<?php
/**
 * Dropdown with tick boxes for picking several values in a GET filter form. Posts "<name>[]".
 *
 * Expects:
 *   $msName      field name, e.g. 'status'
 *   $msLabel     button label, e.g. 'Status'
 *   $msOptions   [value => label]
 *   $msSelected  array of selected values (strings)
 * Optional:
 *   $msSearch    bool, show a search box inside the list (useful for long lists)
 */
$msSearch = $msSearch ?? count($msOptions) > 8;
$msSelected = array_map('strval', $msSelected);
$picked = array_values(array_intersect_key($msOptions, array_flip($msSelected)));
$summary = !$picked ? '' : (count($picked) === 1 ? $picked[0] : (string)count($picked));
?>
<div class="dropdown multi-select" data-multi-select>
  <button class="btn btn-sm <?= $picked ? 'btn-primary' : 'btn-outline-secondary' ?> dropdown-toggle text-truncate" style="max-width:220px" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
    <?= e($msLabel) ?><?php if ($summary !== ''): ?>: <strong><?= e($summary) ?></strong><?php endif; ?>
  </button>
  <div class="dropdown-menu p-2" style="min-width:230px">
    <?php if ($msSearch): ?>
      <input type="search" class="form-control form-control-sm mb-2" placeholder="<?= e(t('Search')) ?>…" data-multi-search aria-label="<?= e(t('Search')) ?>">
    <?php endif; ?>
    <div class="multi-select-list" style="max-height:260px;overflow-y:auto">
      <?php foreach ($msOptions as $val => $label): $id = 'ms_' . $msName . '_' . preg_replace('/[^a-z0-9_]/i', '_', (string)$val); ?>
        <div class="form-check py-1" data-multi-item>
          <input class="form-check-input" type="checkbox" name="<?= e($msName) ?>[]" value="<?= e((string)$val) ?>" id="<?= e($id) ?>"<?= checked(in_array((string)$val, $msSelected, true)) ?>>
          <label class="form-check-label w-100" for="<?= e($id) ?>"><?= e($label) ?></label>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="d-flex gap-2 border-top pt-2 mt-1">
      <button type="button" class="btn btn-link btn-sm p-0" data-multi-clear><?= e(t('Clear')) ?></button>
      <button type="submit" class="btn btn-primary btn-sm ms-auto"><?= e(t('Apply')) ?></button>
    </div>
  </div>
</div>
