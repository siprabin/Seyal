<?php
/**
 * Footer for printouts and "Save as PDF". When printed it repeats at the bottom of every page;
 * the .print-spacer row/cell in the page reserves room for it so it never covers content.
 */
?>
<div class="print-credit">
  <span><?= e(setting('company_name', APP_NAME)) ?> · <span class="ltr"><?= e(fmt_datetime(now())) ?></span></span>
  <span class="ltr" dir="ltr"><?= e(powered_by()) ?> · <?= e(preg_replace('#^https?://#', '', APP_AUTHOR_URL)) ?></span>
</div>
