<?php
/**
 * Timed jobs: reminders, manager digests, Teams digest, auto-close and missed-day flags.
 * Triggered by cron.php (recommended: every 5 minutes), or, if no cron is set up,
 * by normal page visits ("built-in scheduler" in Settings → Daily work).
 * Every job is safe to run repeatedly; notification_log prevents duplicates.
 */
declare(strict_types=1);

function run_scheduled_tasks(int $budget = 60): array
{
    $log = [];
    if (!(int)val("SELECT GET_LOCK('seyal_scheduler', 0)")) {
        return ['Another scheduler run is in progress; skipped.'];
    }
    try {
        set_setting('scheduler_last_run', now());
        $today = today();
        $now = date('H:i');
        $sent = 0;

        // 1. Days left open on earlier dates → auto-close and flag.
        if ($n = auto_close_stale_days(null, $budget)) {
            $log[] = "Auto-closed $n day(s) from earlier dates.";
            $sent += $n;
        }

        // 2. Yesterday (or the last few days): flag people who never started on a working day.
        for ($i = 1; $i <= 3; $i++) {
            $d = date('Y-m-d', strtotime("-$i days"));
            if ($n = mark_missed_days($d)) {
                $log[] = "Marked $n person(s) as not started on $d.";
            }
        }

        // Nightly backup: once per day, after 01:00.
        if (setting('auto_backup', '1') === '1' && $now >= '01:00' && setting('last_auto_backup') !== $today) {
            try {
                set_setting('last_auto_backup', $today);
                $name = create_backup('auto');
                prune_backups((int)setting('backup_keep', '14'));
                $log[] = "Automatic backup: $name";
            } catch (Throwable $e) {
                $log[] = 'Automatic backup FAILED: ' . $e->getMessage();
                error_log('Auto backup failed: ' . $e->getMessage());
            }
        }

        if (!is_working_day($today)) {
            $log[] = "Today ($today) is not a working day; no reminders.";
            return $log;
        }
        $remindersOn = setting('reminders_enabled', '1') === '1' && mail_configured();
        $people = daylog_required_users();

        // 3. Start reminder
        if ($remindersOn && $now >= time_setting('remind_start_time', '11:00')) {
            $started = array_map('intval', q('SELECT user_id FROM work_days WHERE work_date = ?', [$today])->fetchAll(PDO::FETCH_COLUMN));
            foreach ($people as $p) {
                if ($sent >= $budget) {
                    break;
                }
                if (!in_array((int)$p['id'], $started, true) && !notification_sent('remind_start', (int)$p['id'], $today)) {
                    $err = send_reminder('remind_start', $p, $today);
                    $log[] = "Start reminder → {$p['email']}" . ($err ? " FAILED: $err" : '');
                    $sent++;
                }
            }
        }

        // 4. Close reminder
        if ($remindersOn && $now >= time_setting('remind_close_time', '19:00')) {
            $open = array_map('intval', q("SELECT user_id FROM work_days WHERE work_date = ? AND status = 'open'", [$today])->fetchAll(PDO::FETCH_COLUMN));
            foreach ($people as $p) {
                if ($sent >= $budget) {
                    break;
                }
                if (in_array((int)$p['id'], $open, true) && !notification_sent('remind_close', (int)$p['id'], $today)) {
                    $err = send_reminder('remind_close', $p, $today);
                    $log[] = "Close reminder → {$p['email']}" . ($err ? " FAILED: $err" : '');
                    $sent++;
                }
            }
        }

        // 5. Digests
        if ($now >= time_setting('digest_time', '19:30')) {
            if (setting('digest_enabled', '1') === '1' && mail_configured()) {
                $byManager = [];
                foreach ($people as $p) {
                    if ($p['reports_to']) {
                        $byManager[(int)$p['reports_to']][] = $p;
                    }
                }
                foreach ($byManager as $mid => $reports) {
                    if ($sent >= $budget) {
                        break;
                    }
                    if (notification_sent('digest', $mid, $today)) {
                        continue;
                    }
                    $m = row('SELECT id, name, email, lang FROM users WHERE id = ? AND is_active = 1', [$mid]);
                    if ($m) {
                        $err = send_manager_digest($m, $reports, $today);
                        $log[] = "Digest → {$m['email']} (" . count($reports) . ' people)' . ($err ? " FAILED: $err" : '');
                        $sent++;
                    }
                }
            }
            if (setting('teams_post_digest', '1') === '1' && teams_configured() && !notification_sent('teams_digest', null, $today)) {
                $err = post_teams_digest($today);
                $log[] = 'Teams digest posted' . ($err ? " FAILED: $err" : '');
            }
        }

        // 6. End of day: auto-close today's open days and flag people who never started.
        if ($now >= time_setting('auto_close_time', '23:30')) {
            foreach (rows("SELECT * FROM work_days WHERE work_date = ? AND status = 'open'", [$today]) as $day) {
                if ($sent >= $budget) {
                    break;
                }
                close_day($day, null, 'auto_closed', null, 'Day was not closed by the person');
                $log[] = "Auto-closed day of user {$day['user_id']}.";
                $sent++;
            }
            if ($n = mark_missed_days($today)) {
                $log[] = "Marked $n person(s) as not started today.";
            }
        }
    } finally {
        q("SELECT RELEASE_LOCK('seyal_scheduler')");
    }
    if (!$log) {
        $log[] = 'Nothing to do.';
    }
    return $log;
}

/**
 * Built-in scheduler for servers without cron: after the page has been sent,
 * run the jobs if the last run was more than 5 minutes ago.
 */
function maybe_run_builtin_scheduler(): void
{
    if (setting('builtin_scheduler', '1') !== '1') {
        return;
    }
    $last = setting('scheduler_last_run');
    if ($last && strtotime($last) > time() - 300) {
        return;
    }
    register_shutdown_function(function () {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        ignore_user_abort(true);
        try {
            run_scheduled_tasks(15);
        } catch (Throwable $e) {
            error_log('Built-in scheduler: ' . $e->getMessage());
        }
    });
}
