<div class="mb-2 small"><a href="<?= e(url('appraisals')) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> <?= e(t('Appraisals')) ?></a></div>
<div class="page-head"><h1><?= e(t('New appraisal')) ?></h1></div>
<form method="post" class="card" style="max-width:720px">
  <?= csrf_field() ?>
  <div class="card-body row g-3">
    <div class="col-12">
      <label class="form-label" for="user_id"><?= e(t('Person')) ?> <span class="text-danger">*</span></label>
      <select class="form-select" id="user_id" name="user_id" required>
        <option value="">—</option>
        <?php foreach ($people as $p): ?><option value="<?= (int)$p['id'] ?>"<?= selected(old('user_id', $userId), $p['id']) ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><label class="form-label" for="period_from"><?= e(t('Period from')) ?></label><input type="date" class="form-control" id="period_from" name="period_from" value="<?= e(old('period_from', $from)) ?>" required></div>
    <div class="col-md-6"><label class="form-label" for="period_to"><?= e(t('Period to')) ?></label><input type="date" class="form-control" id="period_to" name="period_to" value="<?= e(old('period_to', $to)) ?>" required></div>
    <div class="col-12"><label class="form-label" for="title"><?= e(t('Title')) ?> <span class="text-danger">*</span></label><input class="form-control" id="title" name="title" maxlength="160" value="<?= e(old('title', t('Appraisal :period', ['period' => $defaultTitle]))) ?>" required></div>
  </div>
  <div class="card-footer bg-white"><button class="btn btn-primary"><?= e(t('Create draft')) ?></button></div>
</form>
