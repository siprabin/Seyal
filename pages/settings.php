<?php
/** System settings: general, email, files, demo data, system/updates. */
declare(strict_types=1);

require_perm('settings.manage');
require_once APP_ROOT . '/app/demo.php';

$tab = (string)input('tab', 'general');
$tabs = ['general', 'email', 'daily', 'teams', 'performance', 'files', 'backup', 'demo', 'system'];
if (setting('cron_key', '') === '' && empty($GLOBALS['APP_UPDATE_PENDING'])) {
    set_setting('cron_key', bin2hex(random_bytes(16)));
}
if (!in_array($tab, $tabs, true)) {
    $tab = 'general';
}

/** Save settings and log which keys changed (never the secret values). */
function save_settings(array $values, array $secretKeys = []): void
{
    $u = user();
    foreach ($values as $k => $v) {
        $old = setting($k);
        if ((string)$old === (string)$v) {
            continue;
        }
        set_setting($k, $v);
        $secret = in_array($k, $secretKeys, true);
        audit_raw((int)$u['id'], $u['name'], 'settings', null, $k, 'update', 'setting', $secret ? '••••' : $old, $secret ? '••••' : $v);
    }
}

if (is_post()) {
    switch ($action) {
        case 'general':
            $tz = (string)input('timezone', 'Asia/Kolkata');
            if (!in_array($tz, timezone_identifiers_list(), true)) {
                $tz = 'Asia/Kolkata';
            }
            $fmt = in_array(input('date_format'), ['d-m-Y', 'd/m/Y', 'Y-m-d', 'm/d/Y', 'd M Y'], true) ? input('date_format') : 'd-m-Y';
            $name = mb_substr((string)input('company_name', ''), 0, 120);
            save_settings([
                'company_name' => $name !== '' ? $name : 'Project Management',
                'timezone'     => $tz,
                'date_format'  => $fmt,
                'default_lang' => isset(SUPPORTED_LANGS[input('default_lang')]) ? input('default_lang') : 'en',
            ]);
            flash('success', t('Settings saved.'));
            redirect(url('settings', ['tab' => 'general']));

        case 'email':
            $vals = [
                'smtp_host'       => mb_substr((string)input('smtp_host', ''), 0, 190),
                'smtp_port'       => (string)max(1, min(65535, (int)input('smtp_port', 587))),
                'smtp_encryption' => in_array(input('smtp_encryption'), ['tls', 'ssl', 'none'], true) ? input('smtp_encryption') : 'tls',
                'smtp_user'       => mb_substr((string)input('smtp_user', ''), 0, 190),
                'mail_from'       => mb_substr((string)input('mail_from', ''), 0, 190),
                'mail_from_name'  => mb_substr((string)input('mail_from_name', ''), 0, 120),
            ];
            if ($vals['mail_from'] !== '' && !filter_var($vals['mail_from'], FILTER_VALIDATE_EMAIL)) {
                flash('danger', t('"From" email address is not valid.'));
                redirect(url('settings', ['tab' => 'email']));
            }
            $pass = (string)($_POST['smtp_pass'] ?? '');
            if ($pass !== '') {
                $vals['smtp_pass'] = encrypt_secret($pass);
            } elseif (input('clear_pass')) {
                $vals['smtp_pass'] = '';
            }
            save_settings($vals, ['smtp_pass']);
            flash('success', t('Email settings saved. Send a test email to check them.'));
            redirect(url('settings', ['tab' => 'email']));

        case 'test_email':
            $to = (string)input('to', user()['email']);
            $err = filter_var($to, FILTER_VALIDATE_EMAIL)
                ? send_mail($to, t('Test email'), '<p>' . e(t('Your email settings work. You can now receive notifications from the app.')) . '</p>')
                : t('Please enter a valid email address.');
            $err ? flash('danger', $err) : flash('success', t('Test email sent to :to.', ['to' => $to]));
            redirect(url('settings', ['tab' => 'email']));

        case 'daily':
            $roles = array_values(array_intersect((array)($_POST['daylog_roles'] ?? []), [ROLE_SENIOR, ROLE_DEPT_HEAD, ROLE_MANAGER, ROLE_MEMBER]));
            $days = array_values(array_intersect(array_map('intval', (array)($_POST['working_days'] ?? [])), range(1, 7)));
            $time = fn($k, $d) => preg_match('/^\d{2}:\d{2}$/', (string)input($k)) ? (string)input($k) : $d;
            $holidayLines = [];
            foreach (preg_split('/\r?\n/', (string)input('holidays', '')) as $line) {
                if (preg_match('/^\s*(\d{4}-\d{2}-\d{2})\s*(.*)$/', $line, $m)) {
                    $holidayLines[] = trim($m[1] . ' ' . mb_substr(trim($m[2]), 0, 80));
                }
            }
            sort($holidayLines);
            save_settings([
                'daylog_roles'        => implode(',', $roles),
                'working_days'        => implode(',', $days),
                'holidays'            => implode("\n", $holidayLines),
                'remind_start_time'   => $time('remind_start_time', '11:00'),
                'remind_close_time'   => $time('remind_close_time', '19:00'),
                'digest_time'         => $time('digest_time', '19:30'),
                'auto_close_time'     => $time('auto_close_time', '23:30'),
                'reminders_enabled'   => input('reminders_enabled') ? '1' : '0',
                'digest_enabled'      => input('digest_enabled') ? '1' : '0',
                'email_close_summary' => input('email_close_summary') ? '1' : '0',
                'email_close_copy'    => input('email_close_copy') ? '1' : '0',
                'builtin_scheduler'   => input('builtin_scheduler') ? '1' : '0',
            ]);
            flash('success', t('Settings saved.'));
            redirect(url('settings', ['tab' => 'daily']));

        case 'cron_key':
            save_settings(['cron_key' => bin2hex(random_bytes(16))], ['cron_key']);
            flash('success', t('A new key was created. Update your cron job URL if you use one.'));
            redirect(url('settings', ['tab' => 'daily']));

        case 'teams':
            $hook = trim((string)($_POST['teams_webhook_url'] ?? ''));
            $vals = [
                'teams_post_close'  => input('teams_post_close') ? '1' : '0',
                'teams_post_digest' => input('teams_post_digest') ? '1' : '0',
            ];
            if ($hook !== '') {
                if (!preg_match('#^https://\S+$#', $hook)) {
                    flash('danger', t('The webhook URL must start with https://'));
                    redirect(url('settings', ['tab' => 'teams']));
                }
                $vals['teams_webhook_url'] = encrypt_secret($hook);
            } elseif (input('clear_hook')) {
                $vals['teams_webhook_url'] = '';
            }
            save_settings($vals, ['teams_webhook_url']);
            flash('success', t('Teams settings saved. Send a test message to check them.'));
            redirect(url('settings', ['tab' => 'teams']));

        case 'teams_test':
            $err = teams_post([
                card_text('✅ ' . t('Test message from :app', ['app' => setting('company_name', 'Project Management')]), ['weight' => 'Bolder', 'size' => 'Medium']),
                card_text(t('Daily reports will appear in this channel.')),
                card_facts([t('Sent by') => user()['name'], t('Time') => fmt_datetime(now())]),
            ]);
            log_notification('teams_test', 'teams', uid(), today(), null, 'Teams', $err);
            $err ? flash('danger', $err) : flash('success', t('Test message posted. Check your Teams channel.'));
            redirect(url('settings', ['tab' => 'teams']));

        case 'performance':
            save_settings([
                'late_factor'    => (string)max(0, min(1, round((float)input('late_factor', 0.7), 2))),
                'rag_amber_days' => (string)max(0, min(90, (int)input('rag_amber_days', 7))),
            ]);
            flash('success', t('Settings saved.'));
            redirect(url('settings', ['tab' => 'performance']));

        case 'backup_settings':
            save_settings([
                'auto_backup' => input('auto_backup') ? '1' : '0',
                'backup_keep' => (string)max(1, min(90, (int)input('backup_keep', 14))),
            ]);
            flash('success', t('Settings saved.'));
            redirect(url('settings', ['tab' => 'backup']));

        case 'backup_now':
            try {
                $name = create_backup('manual');
                audit('system', null, $name, 'backup');
                flash('success', t('Backup created: :name', ['name' => $name]));
            } catch (Throwable $e) {
                flash('danger', $e->getMessage());
            }
            redirect(url('settings', ['tab' => 'backup']));

        case 'backup_download':
            $path = backup_path((string)input('file')) ?? abort(404);
            audit('system', null, basename($path), 'backup', 'Downloaded');
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;

        case 'backup_delete':
            $path = backup_path((string)input('file')) ?? abort(404);
            @unlink($path);
            audit('system', null, basename($path), 'delete', 'Backup file deleted');
            flash('success', t('Backup deleted.'));
            redirect(url('settings', ['tab' => 'backup']));

        case 'uploads_zip':
            $tmp = zip_uploads();
            if (!$tmp) {
                flash('danger', t('The ZIP extension is not available on this server.'));
                redirect(url('settings', ['tab' => 'backup']));
            }
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="uploads-' . date('Y-m-d') . '.zip"');
            header('Content-Length: ' . filesize($tmp));
            readfile($tmp);
            @unlink($tmp);
            exit;

        case 'run_scheduler':
            $lines = run_scheduled_tasks();
            flash('info', implode(' | ', $lines));
            redirect(url('settings', ['tab' => 'system']));

        case 'files':
            $exts = preg_replace('/[^a-z0-9,]/', '', strtolower(str_replace([' ', '.'], '', (string)input('upload_extensions', ''))));
            $exts = implode(',', array_diff(array_filter(explode(',', $exts)), ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'pht', 'exe', 'bat', 'cmd', 'sh', 'js', 'html', 'htm', 'svg', 'htaccess']));
            save_settings([
                'upload_max_mb'     => (string)max(1, min(200, (int)input('upload_max_mb', 10))),
                'upload_extensions' => $exts,
            ]);
            flash('success', t('Settings saved.'));
            redirect(url('settings', ['tab' => 'files']));

        case 'demo_load':
            try {
                demo_load();
                audit('system', null, 'Demo data', 'demo_load');
                flash('success', t('Demo data loaded. Demo users can log in with password :pw', ['pw' => DEMO_PASSWORD]));
            } catch (Throwable $e) {
                flash('danger', $e->getMessage());
            }
            redirect(url('settings', ['tab' => 'demo']));

        case 'demo_wipe':
            demo_wipe();
            audit('system', null, 'Demo data', 'demo_wipe');
            flash('success', t('Demo data removed. Your own data was not touched.'));
            redirect(url('settings', ['tab' => 'demo']));

        case 'migrate':
            $applied = run_migrations(db());
            flash('success', $applied ? t('Database updated: :list', ['list' => implode(', ', $applied)]) : t('Database is already up to date.'));
            redirect(url('settings', ['tab' => 'system']));
    }
    abort(404);
}

$data = ['pageTitle' => t('Settings'), 'tab' => $tab];
if ($tab === 'backup') {
    $data['backups'] = list_backups();
}
if ($tab === 'demo') {
    $data['demoLoaded'] = demo_loaded();
    $data['demoUsers'] = rows('SELECT u.name, u.email, r.slug AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.is_demo = 1 ORDER BY r.level, u.name');
}
if ($tab === 'system') {
    $data['pending'] = array_map('basename', pending_migrations(db()));
    $data['applied'] = rows('SELECT * FROM migrations ORDER BY id');
    $data['dbVersion'] = (string)val('SELECT VERSION()');
    $data['uploadsWritable'] = is_writable(APP_ROOT . '/uploads');
    $data['installDirExists'] = is_dir(APP_ROOT . '/install') && is_file(APP_ROOT . '/install/index.php');
    $data['notifications'] = $data['pending'] ? [] : rows(
        'SELECT n.*, u.name AS user_name FROM notification_log n LEFT JOIN users u ON u.id = n.user_id ORDER BY n.id DESC LIMIT 40'
    );
}
render('settings/index', $data);
