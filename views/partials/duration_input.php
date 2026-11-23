<?php
/**
 * Hours + minutes picker for a duration. Posts "<name>_h" and "<name>_m"; read them with duration_input($name).
 *
 * Expects:
 *   $durName      field base name, e.g. 'hours'
 *   $durMinutes   current value in minutes, or null for empty
 *   $durMaxHours  largest number of hours (24 for a day; larger shows a number box instead of a list)
 * Optional:
 *   $durSmall bool, $durQuick array of minute values for quick-pick buttons, $durLabelId id for the label
 */
$durSmall = $durSmall ?? false;
$durQuick = $durQuick ?? [];
$durLabelId = $durLabelId ?? $durName . '_h';
// After a failed save, show what the person had picked.
$oldH = old($durName . '_h', null);
$oldM = old($durName . '_m', null);
if ($oldH !== null || $oldM !== null) {
    $durMinutes = ($oldH === '' && $oldM === '') ? null : (int)$oldH * 60 + (int)$oldM;
}
$selH = $durMinutes === null ? '' : intdiv((int)$durMinutes, 60);
$selM = $durMinutes === null ? '' : (int)$durMinutes % 60;
$mins = range(0, 55, 5);
if ($selM !== '' && !in_array($selM, $mins, true)) {
    $mins[] = $selM;   // keep an exact older value (e.g. 19 min) when editing
    sort($mins);
}
$sz = $durSmall ? '-sm' : '';
?>
<div class="duration-input" data-duration="<?= e($durName) ?>">
  <div class="input-group input-group<?= $sz ?>">
    <?php if ($durMaxHours <= 24): ?>
      <select class="form-select" id="<?= e($durLabelId) ?>" name="<?= e($durName) ?>_h" aria-label="<?= e(t('Hours')) ?>">
        <option value=""<?= $selH === '' ? ' selected' : '' ?>>–</option>
        <?php for ($i = 0; $i <= $durMaxHours; $i++): ?><option value="<?= $i ?>"<?= $selH === $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?>
      </select>
    <?php else: ?>
      <input type="number" class="form-control" id="<?= e($durLabelId) ?>" name="<?= e($durName) ?>_h" min="0" max="<?= (int)$durMaxHours ?>" step="1" inputmode="numeric" value="<?= e((string)$selH) ?>" placeholder="0" aria-label="<?= e(t('Hours')) ?>">
    <?php endif; ?>
    <span class="input-group-text"><?= e(t('h')) ?></span>
    <select class="form-select" name="<?= e($durName) ?>_m" aria-label="<?= e(t('Minutes')) ?>">
      <option value=""<?= $selM === '' ? ' selected' : '' ?>>–</option>
      <?php foreach ($mins as $mm): ?><option value="<?= $mm ?>"<?= $selM === $mm ? ' selected' : '' ?>><?= sprintf('%02d', $mm) ?></option><?php endforeach; ?>
    </select>
    <span class="input-group-text"><?= e(t('min')) ?></span>
  </div>
  <?php if ($durQuick): ?>
    <div class="d-flex flex-wrap gap-1 mt-1">
      <?php foreach ($durQuick as $q): ?>
        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 duration-quick" data-minutes="<?= (int)$q ?>"><?= e(fmt_minutes((int)$q)) ?></button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
