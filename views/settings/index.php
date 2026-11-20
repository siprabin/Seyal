<?php
$tabLabels = [
    'general' => [t('General'), 'bi-sliders'],
    'email'   => [t('Email'), 'bi-envelope'],
    'daily'   => [t('Daily work'), 'bi-calendar-check'],
    'teams'   => [t('Microsoft Teams'), 'bi-microsoft-teams'],
    'performance' => [t('Performance'), 'bi-graph-up'],
    'files'   => [t('File uploads'), 'bi-paperclip'],
    'backup'  => [t('Backup'), 'bi-database-down'],
    'demo'    => [t('Demo data'), 'bi-magic'],
    'system'  => [t('System'), 'bi-hdd-stack'],
];
?>
<div class="page-head"><h1><?= e(t('Settings')) ?></h1></div>
<ul class="nav nav-tabs mb-3">
  <?php foreach ($tabLabels as $k => [$label, $icon]): ?>
    <li class="nav-item"><a class="nav-link<?= $tab === $k ? ' active' : '' ?>" href="<?= e(url('settings', ['tab' => $k])) ?>"><i class="bi <?= $icon ?>"></i> <?= e($label) ?></a></li>
  <?php endforeach; ?>
</ul>

<?php if ($tab === 'general'): ?>
<form method="post" action="<?= e(url('settings', ['a' => 'general'])) ?>" class="card" style="max-width:720px">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="mb-3">
      <label class="form-label" for="company_name"><?= e(t('Company name')) ?></label>
      <input class="form-control" id="company_name" name="company_name" value="<?= e(setting('company_name')) ?>" maxlength="120">
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="timezone"><?= e(t('Time zone')) ?></label>
        <select class="form-select" id="timezone" name="timezone">
          <?php foreach (timezone_identifiers_list() as $tz): ?><option<?= selected(setting('timezone', 'Asia/Kolkata'), $tz) ?>><?= e($tz) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="date_format"><?= e(t('Date format')) ?></label>
        <select class="form-select" id="date_format" name="date_format">
          <?php foreach (['d-m-Y', 'd/m/Y', 'd M Y', 'Y-m-d', 'm/d/Y'] as $fmt): ?>
            <option value="<?= e($fmt) ?>"<?= selected(setting('date_format', 'd-m-Y'), $fmt) ?>><?= e(date($fmt)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="default_lang"><?= e(t('Default language for new users')) ?></label>
        <select class="form-select" id="default_lang" name="default_lang">
          <?php foreach (SUPPORTED_LANGS as $code => $label): ?><option value="<?= e($code) ?>"<?= selected(setting('default_lang', 'en'), $code) ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
  <div class="card-footer bg-white"><button class="btn btn-primary"><?= e(t('Save')) ?></button></div>
</form>

<?php elseif ($tab === 'email'): ?>
<div class="row g-3">
  <div class="col-lg-7">
    <form method="post" action="<?= e(url('settings', ['a' => 'email'])) ?>" class="card" autocomplete="off">
      <?= csrf_field() ?>
      <div class="card-header"><?= e(t('SMTP server')) ?>
        <?= mail_configured() ? '<span class="badge bg-success ms-2">' . e(t('Configured')) . '</span>' : '<span class="badge bg-warning text-dark ms-2">' . e(t('Not set up')) . '</span>' ?></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label" for="smtp_host"><?= e(t('SMTP host')) ?></label>
            <input class="form-control" id="smtp_host" name="smtp_host" value="<?= e(setting('smtp_host')) ?>" placeholder="smtp.gmail.com">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="smtp_port"><?= e(t('Port')) ?></label>
            <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= e(setting('smtp_port', '587')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="smtp_encryption"><?= e(t('Encryption')) ?></label>
            <select class="form-select" id="smtp_encryption" name="smtp_encryption">
              <?php foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => t('None')] as $k => $l): ?><option value="<?= $k ?>"<?= selected(setting('smtp_encryption', 'tls'), $k) ?>><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label" for="smtp_user"><?= e(t('Username')) ?></label>
            <input class="form-control" id="smtp_user" name="smtp_user" value="<?= e(setting('smtp_user')) ?>" autocomplete="off">
          </div>
          <div class="col-12">
            <label class="form-label" for="smtp_pass"><?= e(t('Password / app password')) ?></label>
            <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" autocomplete="new-password" placeholder="<?= setting('smtp_pass') ? e(t('Saved — leave blank to keep')) : '' ?>">
            <?php if (setting('smtp_pass')): ?>
              <div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="clear_pass" name="clear_pass" value="1"><label class="form-check-label small" for="clear_pass"><?= e(t('Remove saved password')) ?></label></div>
            <?php endif; ?>
            <div class="form-text"><?= e(t('Stored encrypted.')) ?></div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="mail_from"><?= e(t('"From" email address')) ?></label>
            <input type="email" class="form-control" id="mail_from" name="mail_from" value="<?= e(setting('mail_from')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="mail_from_name"><?= e(t('"From" name')) ?></label>
            <input class="form-control" id="mail_from_name" name="mail_from_name" value="<?= e(setting('mail_from_name')) ?>" placeholder="<?= e(setting('company_name')) ?>">
          </div>
        </div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary"><?= e(t('Save')) ?></button></div>
    </form>
  </div>
  <div class="col-lg-5">
    <form method="post" action="<?= e(url('settings', ['a' => 'test_email'])) ?>" class="card mb-3">
      <?= csrf_field() ?>
      <div class="card-header"><?= e(t('Send a test email')) ?></div>
      <div class="card-body">
        <input type="email" class="form-control mb-2" name="to" value="<?= e(user()['email']) ?>" required>
        <button class="btn btn-outline-primary btn-sm"<?= mail_configured() ? '' : ' disabled' ?>><i class="bi bi-send"></i> <?= e(t('Send test')) ?></button>
      </div>
    </form>
    <div class="card"><div class="card-body small">
      <strong><?= e(t('Common settings')) ?></strong>
      <ul class="mb-0 mt-2 ps-3">
        <li><strong>Gmail / Google Workspace:</strong> smtp.gmail.com · 587 · TLS · <?= e(t('use an App Password (Google Account → Security → 2-Step Verification → App passwords)')) ?></li>
        <li><strong>Hostinger:</strong> smtp.hostinger.com · 465 · SSL · <?= e(t('your mailbox email and password')) ?></li>
        <li><strong>Office 365:</strong> smtp.office365.com · 587 · TLS</li>
      </ul>
    </div></div>
  </div>
</div>

<?php elseif ($tab === 'daily'):
  $roles = daylog_roles();
  $wdays = array_map('intval', explode(',', (string)setting('working_days', '1,2,3,4,5,6')));
  $cronPath = str_replace('\\', '/', APP_ROOT) . '/cron.php';
  $cronUrl = base_url() . '/cron.php?key=' . setting('cron_key', '');
  $isWin = DIRECTORY_SEPARATOR === '\\';
?>
<form method="post" action="<?= e(url('settings', ['a' => 'daily'])) ?>" class="row g-3">
  <?= csrf_field() ?>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><?= e(t('Who logs their day')) ?></div>
      <div class="card-body">
        <p class="small text-muted"><?= e(t('People in these roles get reminders, appear in digests, and are flagged when they do not start their day. Anyone can still use My Day.')) ?></p>
        <?php foreach ([ROLE_SENIOR, ROLE_DEPT_HEAD, ROLE_MANAGER, ROLE_MEMBER] as $r): ?>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="daylog_roles[]" value="<?= e($r) ?>" id="dr_<?= e($r) ?>"<?= checked(in_array($r, $roles, true)) ?>>
            <label class="form-check-label" for="dr_<?= e($r) ?>"><?= e(role_label($r)) ?></label></div>
        <?php endforeach; ?>
        <hr>
        <div class="form-label"><?= e(t('Working days')) ?></div>
        <div class="d-flex flex-wrap gap-1">
          <?php foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $n => $dname): ?>
            <input type="checkbox" class="btn-check" name="working_days[]" value="<?= $n ?>" id="wd<?= $n ?>"<?= checked(in_array($n, $wdays, true)) ?>>
            <label class="btn btn-sm btn-outline-primary" for="wd<?= $n ?>"><?= e(t($dname)) ?></label>
          <?php endforeach; ?>
        </div>
        <div class="mt-3">
          <label class="form-label" for="holidays"><?= e(t('Holidays')) ?></label>
          <textarea class="form-control font-monospace small" id="holidays" name="holidays" rows="5" placeholder="2027-01-15 Pongal&#10;2027-01-26 Republic Day"><?= e(setting('holidays', '')) ?></textarea>
          <div class="form-text"><?= e(t('One per line: date (YYYY-MM-DD) then an optional name. No reminders or missed-day flags on these dates.')) ?></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card mb-3">
      <div class="card-header"><?= e(t('Times')) ?></div>
      <div class="card-body row g-3">
        <?php foreach ([
            'remind_start_time' => [t('"Start your day" reminder after'), '11:00', t('Also the cut-off for "late start".')],
            'remind_close_time' => [t('"Close your day" reminder after'), '19:00', ''],
            'digest_time'       => [t('Manager digest sent at'), '19:30', ''],
            'auto_close_time'   => [t('Auto-close open days at'), '23:30', t('Open days are closed and flagged.')],
        ] as $k => [$label, $def, $hint]): ?>
          <div class="col-sm-6">
            <label class="form-label small" for="<?= $k ?>"><?= e($label) ?></label>
            <input type="time" class="form-control" id="<?= $k ?>" name="<?= $k ?>" value="<?= e(time_setting($k, $def)) ?>">
            <?php if ($hint): ?><div class="form-text"><?= e($hint) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><?= e(t('Emails')) ?></div>
      <div class="card-body">
        <?php foreach ([
            'email_close_summary' => t('Email the Close Day summary to the person\'s manager'),
            'email_close_copy'    => t('Send the person a copy of their own summary'),
            'reminders_enabled'   => t('Send start / close reminders'),
            'digest_enabled'      => t('Send each manager a daily team digest'),
        ] as $k => $label): ?>
          <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="<?= $k ?>" name="<?= $k ?>" value="1"<?= checked(setting($k, '1') === '1') ?>>
            <label class="form-check-label" for="<?= $k ?>"><?= e($label) ?></label></div>
        <?php endforeach; ?>
        <?php if (!mail_configured()): ?><div class="alert alert-warning small mt-2 mb-0"><?= e(t('Email is not set up yet, so no emails will be sent.')) ?> <a href="<?= e(url('settings', ['tab' => 'email'])) ?>"><?= e(t('Set up email')) ?></a></div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card">
      <div class="card-header"><i class="bi bi-alarm"></i> <?= e(t('Scheduler (reminders, digests, auto-close)')) ?></div>
      <div class="card-body">
        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="builtin_scheduler" name="builtin_scheduler" value="1"<?= checked(setting('builtin_scheduler', '1') === '1') ?>>
          <label class="form-check-label" for="builtin_scheduler"><?= e(t('Built-in scheduler: run the jobs while people use the app (no setup needed)')) ?></label></div>
        <p class="small text-muted"><?= e(t('The built-in scheduler only runs while someone has the app open. For reminders on time every day, also set up a cron job to run every 5 minutes:')) ?></p>
        <div class="row g-3 small">
          <div class="col-lg-6">
            <strong>Hostinger</strong> — hPanel → Advanced → Cron Jobs → <?= e(t('every 5 minutes')) ?>:
            <pre class="bg-light border rounded p-2 mt-1 mb-0 user-select-all">/usr/bin/php <?= e($isWin ? '/home/USER/domains/DOMAIN/public_html/cron.php' : $cronPath) ?></pre>
          </div>
          <div class="col-lg-6">
            <strong>XAMPP (Windows)</strong> — <?= e(t('Task Scheduler → Create Task → trigger every 5 minutes → action:')) ?>
            <pre class="bg-light border rounded p-2 mt-1 mb-0 user-select-all">C:\xampp\php\php.exe "<?= e($isWin ? str_replace('/', '\\', $cronPath) : 'C:\xampp\htdocs\pm\cron.php') ?>"</pre>
          </div>
          <div class="col-12">
            <?= e(t('Or, if your host only allows a URL:')) ?>
            <pre class="bg-light border rounded p-2 mt-1 mb-1 user-select-all"><?= e($cronUrl) ?></pre>
            <span class="text-muted"><?= e(t('Last run:')) ?> <?= e(setting('scheduler_last_run') ? fmt_datetime(setting('scheduler_last_run')) : t('Never')) ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary"><?= e(t('Save')) ?></button>
  </div>
</form>
<form method="post" action="<?= e(url('settings', ['a' => 'cron_key'])) ?>" class="mt-2" data-confirm="<?= e(t('Create a new key? The old cron URL will stop working.')) ?>">
  <?= csrf_field() ?><button class="btn btn-sm btn-link px-0"><?= e(t('Create a new cron key')) ?></button>
</form>

<?php elseif ($tab === 'teams'): ?>
<div class="row g-3">
  <div class="col-lg-6">
    <form method="post" action="<?= e(url('settings', ['a' => 'teams'])) ?>" class="card mb-3" autocomplete="off">
      <?= csrf_field() ?>
      <div class="card-header"><?= e(t('Teams channel')) ?>
        <?= teams_configured() ? '<span class="badge bg-success ms-2">' . e(t('Configured')) . '</span>' : '<span class="badge bg-warning text-dark ms-2">' . e(t('Not set up')) . '</span>' ?></div>
      <div class="card-body">
        <label class="form-label" for="teams_webhook_url"><?= e(t('Workflow webhook URL')) ?></label>
        <input type="url" class="form-control" id="teams_webhook_url" name="teams_webhook_url" placeholder="<?= teams_configured() ? e(t('Saved — leave blank to keep')) : 'https://…' ?>">
        <?php if (teams_configured()): ?>
          <div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="clear_hook" name="clear_hook" value="1"><label class="form-check-label small" for="clear_hook"><?= e(t('Remove saved URL')) ?></label></div>
        <?php endif; ?>
        <div class="form-text"><?= e(t('Stored encrypted. Anyone with this URL can post to the channel, so keep it private.')) ?></div>
        <hr>
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="teams_post_close" name="teams_post_close" value="1"<?= checked(setting('teams_post_close', '1') === '1') ?>>
          <label class="form-check-label" for="teams_post_close"><?= e(t('Post each person\'s Close Day summary')) ?></label></div>
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="teams_post_digest" name="teams_post_digest" value="1"<?= checked(setting('teams_post_digest', '1') === '1') ?>>
          <label class="form-check-label" for="teams_post_digest"><?= e(t('Post a team summary at digest time')) ?></label></div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary"><?= e(t('Save')) ?></button></div>
    </form>
    <form method="post" action="<?= e(url('settings', ['a' => 'teams_test'])) ?>">
      <?= csrf_field() ?><button class="btn btn-outline-primary"<?= teams_configured() ? '' : ' disabled' ?>><i class="bi bi-send"></i> <?= e(t('Send a test message')) ?></button>
    </form>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-list-ol"></i> <?= e(t('How to get the webhook URL')) ?></div>
      <div class="card-body small">
        <ol class="ps-3 mb-2">
          <li><?= e(t('In Microsoft Teams, go to the channel where reports should appear.')) ?></li>
          <li><?= e(t('Click ••• (More options) next to the channel name → Workflows.')) ?></li>
          <li><?= e(t('Choose the template "Post to a channel when a webhook request is received".')) ?></li>
          <li><?= e(t('Give it a name (e.g. "Daily reports"), check the team and channel, then click Add workflow / Create.')) ?></li>
          <li><?= e(t('Copy the URL that Teams shows at the end.')) ?></li>
          <li><?= e(t('Paste it here, click Save, then Send a test message.')) ?></li>
        </ol>
        <p class="text-muted mb-0"><?= e(t('The workflow posts as the person who created it ("via Workflows"). If that person leaves the company, create the workflow again and paste the new URL.')) ?></p>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'performance'): ?>
<form method="post" action="<?= e(url('settings', ['a' => 'performance'])) ?>" class="card" style="max-width:760px">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="alert alert-light border small"><i class="bi bi-calculator"></i>
      <?= e(t('Performance score = Σ(weightage × quality × on-time) ÷ Σ(weightage × 5) × 100, over the tasks rated in the period.')) ?></div>
    <div class="mb-3">
      <label class="form-label" for="late_factor"><?= e(t('Value of a late task')) ?></label>
      <input type="number" step="0.05" min="0" max="1" class="form-control" id="late_factor" name="late_factor" value="<?= e((string)late_factor()) ?>" style="max-width:140px">
      <div class="form-text"><?= e(t('1 = lateness is ignored, 0.7 = a late task counts 70% (default), 0 = late tasks earn nothing.')) ?></div>
    </div>
    <div class="mb-1">
      <label class="form-label" for="rag_amber_days"><?= e(t('"At risk" margin (days)')) ?></label>
      <input type="number" min="0" max="90" class="form-control" id="rag_amber_days" name="rag_amber_days" value="<?= amber_days() ?>" style="max-width:140px">
      <div class="form-text"><?= e(t('A project whose forecast is up to this many days after its target date is 🟡 At risk; later than that is 🔴 Off track.')) ?></div>
    </div>
  </div>
  <div class="card-footer bg-white"><button class="btn btn-primary"><?= e(t('Save')) ?></button></div>
</form>

<?php elseif ($tab === 'backup'): ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-database-down"></i> <?= e(t('Database backups')) ?></div>
      <div class="card-body">
        <p class="small text-muted"><?= e(t('A backup is a complete copy of the database (all users, projects, tasks, logs and settings) as an SQL file. Download it and keep it somewhere safe.')) ?></p>
        <form method="post" action="<?= e(url('settings', ['a' => 'backup_now'])) ?>" class="mb-3"><?= csrf_field() ?>
          <button class="btn btn-primary"><i class="bi bi-database-check"></i> <?= e(t('Back up now')) ?></button></form>
        <table class="table table-sm small mb-0">
          <thead class="table-light"><tr><th><?= e(t('File')) ?></th><th><?= e(t('Created')) ?></th><th class="text-end"><?= e(t('Size')) ?></th><th></th></tr></thead>
          <tbody>
          <?php foreach ($backups as $b): ?>
            <tr>
              <td class="ltr text-break"><?= e($b['name']) ?></td>
              <td class="text-nowrap"><?= e(fmt_datetime($b['time'])) ?></td>
              <td class="text-end text-nowrap"><?= e(human_size((int)$b['size'])) ?></td>
              <td class="text-end text-nowrap">
                <form method="post" action="<?= e(url('settings', ['a' => 'backup_download'])) ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="file" value="<?= e($b['name']) ?>"><button class="btn btn-sm btn-outline-primary py-0" title="<?= e(t('Download')) ?>"><i class="bi bi-download"></i></button></form>
                <form method="post" action="<?= e(url('settings', ['a' => 'backup_delete'])) ?>" class="d-inline" data-confirm="<?= e(t('Delete this backup file?')) ?>"><?= csrf_field() ?><input type="hidden" name="file" value="<?= e($b['name']) ?>"><button class="btn btn-sm btn-outline-danger py-0" title="<?= e(t('Delete')) ?>"><i class="bi bi-trash"></i></button></form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$backups): ?><tr><td colspan="4" class="text-muted text-center py-3"><?= e(t('No backups yet.')) ?></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><i class="bi bi-paperclip"></i> <?= e(t('File attachments')) ?></div>
      <div class="card-body small">
        <p class="text-muted"><?= e(t('Task attachments are stored as files, not in the database. Download them as a ZIP to keep with your database backup.')) ?></p>
        <form method="post" action="<?= e(url('settings', ['a' => 'uploads_zip'])) ?>"><?= csrf_field() ?><button class="btn btn-outline-primary btn-sm"><i class="bi bi-file-zip"></i> <?= e(t('Download attachments (ZIP)')) ?></button></form>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <form method="post" action="<?= e(url('settings', ['a' => 'backup_settings'])) ?>" class="card mb-3">
      <?= csrf_field() ?>
      <div class="card-header"><?= e(t('Automatic backup')) ?></div>
      <div class="card-body">
        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="auto_backup" name="auto_backup" value="1"<?= checked(setting('auto_backup', '1') === '1') ?>>
          <label class="form-check-label" for="auto_backup"><?= e(t('Make a backup automatically every night')) ?></label></div>
        <label class="form-label small" for="backup_keep"><?= e(t('Keep the last (automatic backups)')) ?></label>
        <input type="number" min="1" max="90" class="form-control form-control-sm" id="backup_keep" name="backup_keep" value="<?= e(setting('backup_keep', '14')) ?>" style="max-width:120px">
        <div class="form-text"><?= e(t('Runs with the scheduler (see Settings → Daily work). Manual backups are never deleted automatically.')) ?></div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary btn-sm"><?= e(t('Save')) ?></button></div>
    </form>
    <div class="card"><div class="card-body small">
      <strong><?= e(t('How to restore')) ?></strong>
      <ol class="ps-3 mb-0 mt-2">
        <li><?= e(t('Open phpMyAdmin (XAMPP: http://localhost/phpmyadmin, Hostinger: hPanel → Databases → phpMyAdmin).')) ?></li>
        <li><?= e(t('Select the app\'s database, choose Import, and pick the backup file (.sql.gz works directly).')) ?></li>
        <li><?= e(t('For attachments, unzip them back into the app\'s uploads folder.')) ?></li>
      </ol>
    </div></div>
  </div>
</div>

<?php elseif ($tab === 'files'): ?>
<form method="post" action="<?= e(url('settings', ['a' => 'files'])) ?>" class="card" style="max-width:720px">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="mb-3">
      <label class="form-label" for="upload_max_mb"><?= e(t('Maximum file size (MB)')) ?></label>
      <input type="number" min="1" max="200" class="form-control" id="upload_max_mb" name="upload_max_mb" value="<?= e(setting('upload_max_mb', '10')) ?>" style="max-width:160px">
      <div class="form-text"><?= e(t('Your server allows up to :n per upload (upload_max_filesize in php.ini).', ['n' => ini_get('upload_max_filesize')])) ?></div>
    </div>
    <div class="mb-1">
      <label class="form-label" for="upload_extensions"><?= e(t('Allowed file types')) ?></label>
      <input class="form-control" id="upload_extensions" name="upload_extensions" value="<?= e(setting('upload_extensions', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,png,jpg,jpeg,gif,webp,zip,rar,7z,mp4,mp3')) ?>">
      <div class="form-text"><?= e(t('Comma separated. Script and web-page types are always blocked for safety.')) ?></div>
    </div>
  </div>
  <div class="card-footer bg-white"><button class="btn btn-primary"><?= e(t('Save')) ?></button></div>
</form>

<?php elseif ($tab === 'demo'): ?>
<div class="card" style="max-width:820px">
  <div class="card-body">
    <p><?= e(t('Demo data adds a sample company (3 departments, 9 people, 3 clients, 4 projects with tasks and subtasks, plus a week of daily work logs) so you can try every screen. Wiping removes only the demo records; anything you created yourself stays.')) ?></p>
    <?php if (!$demoLoaded): ?>
      <form method="post" action="<?= e(url('settings', ['a' => 'demo_load'])) ?>">
        <?= csrf_field() ?><button class="btn btn-primary"><i class="bi bi-magic"></i> <?= e(t('Load demo data')) ?></button>
      </form>
    <?php else: ?>
      <div class="alert alert-info small"><?= e(t('Demo data is loaded. All demo users have the password:')) ?> <code><?= e(DEMO_PASSWORD) ?></code></div>
      <div class="table-card mb-3">
        <table class="table table-sm small">
          <thead class="table-light"><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Email')) ?></th><th><?= e(t('Role')) ?></th></tr></thead>
          <tbody><?php foreach ($demoUsers as $du): ?><tr><td><?= e($du['name']) ?></td><td><code><?= e($du['email']) ?></code></td><td><?= e(role_label($du['role'])) ?></td></tr><?php endforeach; ?></tbody>
        </table>
      </div>
      <form method="post" action="<?= e(url('settings', ['a' => 'demo_wipe'])) ?>" data-confirm="<?= e(t('Remove all demo data?')) ?>">
        <?= csrf_field() ?><button class="btn btn-outline-danger"><i class="bi bi-trash"></i> <?= e(t('Wipe demo data')) ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'system'): ?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><?= e(t('System information')) ?></div>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('App version')) ?></span><span class="ms-auto"><?= e(APP_NAME) ?> <?= e(APP_VERSION) ?></span></li>
        <li class="list-group-item d-flex"><span class="text-muted">PHP</span><span class="ms-auto"><?= e(PHP_VERSION) ?></span></li>
        <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('Database')) ?></span><span class="ms-auto"><?= e($dbVersion) ?></span></li>
        <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('Uploads folder writable')) ?></span><span class="ms-auto"><?= $uploadsWritable ? '<span class="text-success">' . e(t('Yes')) . '</span>' : '<span class="text-danger">' . e(t('No')) . '</span>' ?></span></li>
        <li class="list-group-item d-flex"><span class="text-muted"><?= e(t('Server time')) ?></span><span class="ms-auto"><?= e(fmt_datetime(now())) ?></span></li>
      </ul>
      <?php if ($installDirExists): ?>
        <div class="card-body"><div class="alert alert-warning small mb-0"><i class="bi bi-shield-exclamation"></i> <?= e(t('For extra safety you can delete the install/index.php file now. The installer is locked anyway, but removing it is good practice. Keep the install/migrations folder — updates need it.')) ?></div></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><?= e(t('Database updates')) ?></div>
      <div class="card-body">
        <?php if ($pending): ?>
          <div class="alert alert-warning small"><?= e(t('New updates are ready to install:')) ?> <code><?= e(implode(', ', $pending)) ?></code></div>
          <form method="post" action="<?= e(url('settings', ['a' => 'migrate'])) ?>" data-confirm="<?= e(t('Take a database backup first. Run the updates now?')) ?>">
            <?= csrf_field() ?><button class="btn btn-primary"><i class="bi bi-arrow-up-circle"></i> <?= e(t('Run database updates')) ?></button>
          </form>
        <?php else: ?>
          <p class="text-success small"><i class="bi bi-check-circle"></i> <?= e(t('Database is up to date.')) ?></p>
        <?php endif; ?>
        <ul class="small text-muted mt-3 mb-0 ps-3">
          <?php foreach ($applied as $m): ?><li><?= e($m['id']) ?> — <?= e(fmt_datetime($m['applied_at'])) ?></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
  <?php if (!$pending): ?>
  <div class="col-12">
    <div class="card table-card">
      <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <span><i class="bi bi-send"></i> <?= e(t('Recent emails and Teams posts')) ?></span>
        <span class="small text-muted"><?= e(t('Scheduler last run:')) ?> <?= e(setting('scheduler_last_run') ? fmt_datetime(setting('scheduler_last_run')) : t('Never')) ?></span>
        <form method="post" action="<?= e(url('settings', ['a' => 'run_scheduler'])) ?>" class="ms-auto"><?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-primary"><i class="bi bi-play"></i> <?= e(t('Run scheduler now')) ?></button></form>
      </div>
      <table class="table table-sm small mb-0">
        <thead class="table-light"><tr><th class="ps-3"><?= e(t('Time')) ?></th><th><?= e(t('Type')) ?></th><th><?= e(t('To')) ?></th><th><?= e(t('Status')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($notifications as $n): ?>
          <tr>
            <td class="ps-3 text-nowrap"><?= e(fmt_datetime($n['created_at'])) ?></td>
            <td><i class="bi <?= $n['channel'] === 'teams' ? 'bi-microsoft-teams' : 'bi-envelope' ?>"></i> <?= e($n['kind']) ?><?= $n['user_name'] ? ' · ' . e($n['user_name']) : '' ?></td>
            <td class="text-break"><?= e($n['recipient']) ?></td>
            <td><?= $n['status'] === 'ok' ? '<span class="text-success">' . e(t('Sent')) . '</span>' : '<span class="text-danger">' . e($n['status'] === 'skipped' ? t('Skipped') : t('Failed')) . '</span>' ?>
              <?php if ($n['error']): ?><div class="text-danger"><?= e(mb_strimwidth($n['error'], 0, 160, '…')) ?></div><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$notifications): ?><tr><td colspan="4" class="text-center text-muted py-3"><?= e(t('Nothing sent yet.')) ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
