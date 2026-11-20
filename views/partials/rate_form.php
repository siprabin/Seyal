<?php
/** Rating form for one task. Expects $rateTask (task row) and optional $current (existing rating). */
$current = $current ?? null;
$q = (int)($current['quality'] ?? 0);
$uidForm = 'rf' . (int)$rateTask['id'];
?>
<form method="post" action="<?= e(url('ratings', ['a' => 'save'])) ?>" class="rate-form">
  <?= csrf_field() ?><input type="hidden" name="task" value="<?= (int)$rateTask['id'] ?>">
  <div class="btn-group btn-group-sm rate-btns flex-wrap mb-1" role="radiogroup" aria-label="<?= e(t('Quality')) ?>">
    <?php foreach (quality_labels() as $n => $label): ?>
      <input type="radio" class="btn-check" name="quality" id="<?= $uidForm ?>q<?= $n ?>" value="<?= $n ?>"<?= checked($q === $n) ?> required>
      <label class="btn btn-outline-warning text-dark" for="<?= $uidForm ?>q<?= $n ?>" title="<?= e($label) ?>"><?= $n ?>★</label>
    <?php endforeach; ?>
  </div>
  <div class="input-group input-group-sm">
    <input class="form-control" name="comment" maxlength="2000" value="<?= e($current['comment'] ?? '') ?>" placeholder="<?= e(t('Comment (optional)')) ?>">
    <button class="btn btn-primary"><?= e($current ? t('Update') : t('Rate')) ?></button>
  </div>
</form>
