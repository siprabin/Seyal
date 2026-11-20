<?php $v = fn($k) => old($k, $a[$k] ?? ''); ?>
<div class="mb-2 small"><a href="<?= e(url('appraisals')) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('Appraisals')) ?></a></div>
<div class="page-head">
  <div class="d-flex align-items-center gap-2"><?= avatar($a['user_name']) ?>
    <h1><?= e($a['user_name']) ?> <span class="text-muted fs-6 fw-normal ltr"><?= e(fmt_date($a['period_from'])) ?> – <?= e(fmt_date($a['period_to'])) ?></span></h1></div>
  <div class="actions"><?= appraisal_status_badge($a['status']) ?></div>
</div>
<div class="alert alert-info small"><i class="bi bi-info-circle"></i> <?= e(t('Figures below are live while this is a draft. They are frozen when you finalise. :name will not see anything until the appraisal is published.', ['name' => $a['user_name']])) ?></div>
<div class="mb-3"><?php require __DIR__ . '/_metrics.php'; ?></div>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="card-body row g-3">
    <div class="col-md-8"><label class="form-label" for="title"><?= e(t('Title')) ?></label><input class="form-control" id="title" name="title" maxlength="160" value="<?= e($v('title')) ?>"></div>
    <div class="col-md-4">
      <div class="form-label"><?= e(t('Overall rating')) ?></div>
      <div class="btn-group rate-btns" role="radiogroup">
        <?php foreach (quality_labels() as $n => $label): ?>
          <input type="radio" class="btn-check" name="overall_rating" id="or<?= $n ?>" value="<?= $n ?>"<?= checked((int)$v('overall_rating') === $n) ?>>
          <label class="btn btn-outline-warning text-dark" for="or<?= $n ?>" title="<?= e($label) ?>"><?= $n ?>★</label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php foreach ([['strengths', t('Strengths')], ['improvements', t('Areas to improve')], ['goals', t('Goals for the next period')], ['manager_comments', t('Manager\'s comments')]] as [$k, $l]): ?>
      <div class="col-md-6"><label class="form-label" for="<?= $k ?>"><?= e($l) ?></label><textarea class="form-control" id="<?= $k ?>" name="<?= $k ?>" rows="4"><?= e($v($k)) ?></textarea></div>
    <?php endforeach; ?>
  </div>
  <div class="card-footer bg-white d-flex flex-wrap gap-2">
    <button class="btn btn-outline-primary"><i class="bi bi-save"></i> <?= e(t('Save draft')) ?></button>
    <button class="btn btn-primary" name="finalise" value="1" onclick="return confirm(<?= e(json_encode(t('Finalise this appraisal? The figures will be frozen and it can no longer be edited (unless reopened).'), JSON_UNESCAPED_UNICODE)) ?>)"><i class="bi bi-lock"></i> <?= e(t('Finalise')) ?></button>
    <a class="btn btn-link" href="<?= e(url('performance', ['a' => 'person', 'id' => $a['user_id'], 'period' => 'custom', 'from' => $a['period_from'], 'to' => $a['period_to']])) ?>"><?= e(t('See details and ratings')) ?></a>
  </div>
</form>
<form method="post" action="<?= e(url('appraisals', ['a' => 'delete', 'id' => $a['id']])) ?>" class="mt-2" data-confirm="<?= e(t('Delete this draft?')) ?>">
  <?= csrf_field() ?><button class="btn btn-sm btn-link text-danger px-0"><?= e(t('Delete draft')) ?></button>
</form>
