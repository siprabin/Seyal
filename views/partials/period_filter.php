<?php
/** Period selector. Expects $period, $from, $to; optional $hidden (extra hidden fields) and $extraFilters (HTML). */
$hidden = $hidden ?? [];
?>
<form class="row g-2 mb-3 align-items-end filters" method="get">
  <?php foreach ($hidden as $k => $hv): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($hv) ?>"><?php endforeach; ?>
  <div class="col-6 col-md-auto">
    <label class="form-label small mb-1" for="period"><?= e(t('Period')) ?></label>
    <select name="period" id="period" class="form-select form-select-sm" onchange="this.form.querySelectorAll('.custom-range').forEach(function(el){el.classList.toggle('d-none', this.value!=='custom')}, this); if(this.value!=='custom') this.form.submit();">
      <?php foreach (period_options() as $k => $l): ?><option value="<?= e($k) ?>"<?= selected($period, $k) ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-auto custom-range<?= $period === 'custom' ? '' : ' d-none' ?>"><label class="form-label small mb-1" for="from"><?= e(t('From')) ?></label><input type="date" class="form-control form-control-sm" id="from" name="from" value="<?= e($from) ?>"></div>
  <div class="col-6 col-md-auto custom-range<?= $period === 'custom' ? '' : ' d-none' ?>"><label class="form-label small mb-1" for="to"><?= e(t('To')) ?></label><input type="date" class="form-control form-control-sm" id="to" name="to" value="<?= e($to) ?>"></div>
  <?= $extraFilters ?? '' ?>
  <div class="col-auto"><button class="btn btn-sm btn-primary"><?= e(t('Show')) ?></button></div>
  <div class="col-auto small text-muted ltr pb-1"><?= e(fmt_date($from)) ?> – <?= e(fmt_date($to)) ?></div>
</form>
