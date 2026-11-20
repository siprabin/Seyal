<?php
/**
 * Outgoing messages for daily work: Close Day summary, reminders and digests.
 * Every message is written to notification_log (also used to avoid duplicates).
 */
declare(strict_types=1);

function notification_sent(string $kind, ?int $userId, ?string $date, ?string $channel = null): bool
{
    $sql = 'SELECT COUNT(*) FROM notification_log WHERE kind = ? AND ref_date <=> ? AND user_id <=> ?';
    $params = [$kind, $date, $userId];
    if ($channel) {
        $sql .= ' AND channel = ?';
        $params[] = $channel;
    }
    return (bool)val($sql, $params);
}

function log_notification(string $kind, string $channel, ?int $userId, ?string $date, ?int $refId, ?string $recipient, ?string $error, bool $skipped = false): void
{
    insert('notification_log', [
        'kind' => $kind, 'channel' => $channel, 'user_id' => $userId, 'ref_date' => $date, 'ref_id' => $refId,
        'recipient' => $recipient ? mb_substr($recipient, 0, 255) : null,
        'status' => $skipped ? 'skipped' : ($error ? 'failed' : 'ok'), 'error' => $error, 'created_at' => now(),
    ]);
}

/** Run $fn with the UI language temporarily switched (emails go out in the recipient's language). */
function with_lang(?string $lang, callable $fn)
{
    $prev = $_SESSION['lang'] ?? null;
    $_SESSION['lang'] = isset(SUPPORTED_LANGS[$lang ?? '']) ? $lang : setting('default_lang', 'en');
    try {
        return $fn();
    } finally {
        if ($prev === null) {
            unset($_SESSION['lang']);
        } else {
            $_SESSION['lang'] = $prev;
        }
    }
}

function day_url(int $dayId): string
{
    return base_url() . '/index.php?' . http_build_query(['p' => 'team', 'a' => 'day', 'id' => $dayId]);
}

/* ---------- Close Day summary ---------- */

/** HTML body for the Close Day summary email (in the current language). */
function day_summary_html(array $day, array $entries, array $totals): string
{
    $status = work_day_statuses()[$day['status']]['label'] ?? $day['status'];
    $h = '<h2 style="margin:0 0 4px;font-size:18px">' . e($day['user_name']) . ' — ' . e(fmt_date($day['work_date'])) . '</h2>';
    if ($day['status'] === 'auto_closed') {
        $h .= '<p style="background:#fff3cd;padding:8px 10px;border-radius:6px;margin:8px 0">⚠ ' . e(t('This day was closed automatically because it was not closed by the person.')) . '</p>';
    }
    $h .= '<table style="border-collapse:collapse;font-size:14px;margin:10px 0">';
    foreach ([
        t('Status') => $status,
        t('Started') => fmt_time($day['started_at']),
        t('Closed') => fmt_time($day['closed_at']),
        t('Hours logged') => fmt_hours($totals['hours']),
        t('AI time') => $totals['ai_minutes'] ? fmt_minutes($totals['ai_minutes']) . ($totals['tools'] ? ' (' . implode(', ', $totals['tools']) . ')' : '') : t('Not used'),
        t('Time saved with AI') => $totals['saved_minutes'] ? fmt_minutes($totals['saved_minutes']) : '—',
    ] as $k => $v) {
        $h .= '<tr><td style="padding:3px 14px 3px 0;color:#666">' . e($k) . '</td><td style="padding:3px 0"><strong>' . e($v) . '</strong></td></tr>';
    }
    $h .= '</table>';

    if ($entries) {
        $h .= '<table style="border-collapse:collapse;width:100%;font-size:13px;margin-top:6px">'
            . '<tr style="background:#f1f3f5"><th align="left" style="padding:6px">' . e(t('Task')) . '</th><th align="right" style="padding:6px">' . e(t('Hours')) . '</th><th align="left" style="padding:6px">' . e(t('AI')) . '</th></tr>';
        foreach ($entries as $e) {
            $task = $e['task_title'] ? $e['task_title'] . ' · ' . $e['project_name'] : t('Other work');
            $ai = $e['ai_used'] ? implode(', ', $e['tools']) . ' · ' . fmt_minutes((int)$e['ai_minutes']) : '—';
            $h .= '<tr style="border-top:1px solid #e9ecef"><td style="padding:6px"><strong>' . e($task) . '</strong>'
                . ($e['task_title'] && $e['task_status'] ? ' <span style="color:#666">(' . e(task_statuses()[$e['task_status']]['label'] ?? $e['task_status']) . ')</span>' : '')
                . ($e['description'] ? '<br><span style="color:#444">' . nl2br(e($e['description'])) . '</span>' : '') . '</td>'
                . '<td align="right" style="padding:6px">' . e(fmt_hours((float)$e['hours'])) . '</td><td style="padding:6px">' . e($ai) . '</td></tr>';
        }
        $h .= '</table>';
    } else {
        $h .= '<p style="color:#666">' . e(t('No work entries were logged.')) . '</p>';
    }
    if ($totals['blockers']) {
        $h .= '<h3 style="font-size:15px;color:#b02a37;margin:16px 0 6px">' . e(t('Blockers')) . '</h3><ul style="margin:0;padding-left:18px">';
        foreach ($totals['blockers'] as $b) {
            $h .= '<li><strong>' . e($b['task']) . ':</strong> ' . e($b['text']) . '</li>';
        }
        $h .= '</ul>';
    }
    if ($day['note']) {
        $h .= '<h3 style="font-size:15px;margin:16px 0 6px">' . e(t('Notes')) . '</h3><p style="white-space:pre-wrap;margin:0">' . e($day['note']) . '</p>';
    }
    $h .= '<p style="margin-top:18px"><a href="' . e(day_url((int)$day['id'])) . '" style="background:#1f3a5f;color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none">' . e(t('Open in the app')) . '</a></p>';
    return $h;
}

function day_summary_card(array $day, array $entries, array $totals): array
{
    $body = [
        card_text(t('Day closed') . ': ' . $day['user_name'], ['size' => 'Medium', 'weight' => 'Bolder']),
        card_text(fmt_date($day['work_date']), ['isSubtle' => true, 'spacing' => 'None']),
    ];
    if ($day['status'] === 'auto_closed') {
        $body[] = card_text('⚠ ' . t('This day was closed automatically because it was not closed by the person.'), ['color' => 'Warning']);
    }
    $body[] = card_facts([
        t('Started') => fmt_time($day['started_at']),
        t('Closed') => fmt_time($day['closed_at']),
        t('Hours logged') => fmt_hours($totals['hours']),
        t('AI time') => $totals['ai_minutes'] ? fmt_minutes($totals['ai_minutes']) . ($totals['tools'] ? ' (' . implode(', ', $totals['tools']) . ')' : '') : t('Not used'),
        t('Time saved with AI') => $totals['saved_minutes'] ? fmt_minutes($totals['saved_minutes']) : '—',
    ]);
    foreach (array_slice($entries, 0, 12) as $e) {
        $line = '• **' . ($e['task_title'] ?: t('Other work')) . '** — ' . fmt_hours((float)$e['hours']) . ' h';
        if ($e['ai_used'] && $e['tools']) {
            $line .= ' · AI: ' . implode(', ', $e['tools']);
        }
        if ($e['description']) {
            $line .= "\n" . mb_strimwidth($e['description'], 0, 160, '…');
        }
        $body[] = card_text($line, ['spacing' => 'Small']);
    }
    if ($totals['blockers']) {
        $body[] = card_text(t('Blockers'), ['weight' => 'Bolder', 'color' => 'Attention']);
        foreach ($totals['blockers'] as $b) {
            $body[] = card_text('• ' . $b['task'] . ': ' . $b['text'], ['color' => 'Attention', 'spacing' => 'Small']);
        }
    }
    if ($day['note']) {
        $body[] = card_text(t('Notes') . ': ' . $day['note'], ['isSubtle' => true]);
    }
    return $body;
}

/**
 * Email the summary to the person's manager (plus a copy to the person) and post it to Teams.
 * Returns warnings for anything that failed.
 */
function send_day_summary(int $dayId): array
{
    $day = find_work_day($dayId);
    if (!$day) {
        return [];
    }
    $entries = day_entries($dayId);
    $totals = day_totals($entries);
    $warnings = [];
    $date = $day['work_date'];
    $uid = (int)$day['user_id'];

    // Email to manager
    $manager = summary_recipient($day);
    if ($manager && setting('email_close_summary', '1') === '1') {
        if (!mail_configured()) {
            log_notification('day_summary', 'email', $uid, $date, $dayId, $manager['email'], 'Email is not set up', true);
        } else {
            $err = with_lang($manager['lang'], fn() => send_mail(
                $manager['email'],
                t('Daily report: :name — :date', ['name' => $day['user_name'], 'date' => fmt_date($date)]),
                day_summary_html($day, $entries, $totals)
            ));
            log_notification('day_summary', 'email', $uid, $date, $dayId, $manager['email'], $err);
            if ($err) {
                $warnings[] = t('Could not email your manager: :err', ['err' => $err]);
            }
        }
    }
    // Copy to the person
    if (setting('email_close_copy', '1') === '1' && mail_configured() && $day['status'] === 'closed') {
        $err = with_lang($day['user_lang'], fn() => send_mail(
            $day['user_email'],
            t('Your daily report — :date', ['date' => fmt_date($date)]),
            day_summary_html($day, $entries, $totals)
        ));
        log_notification('day_summary_copy', 'email', $uid, $date, $dayId, $day['user_email'], $err);
    }
    // Teams
    if (setting('teams_post_close', '1') === '1' && teams_configured()) {
        $err = with_lang(setting('default_lang', 'en'), fn() => teams_post(
            day_summary_card($day, $entries, $totals),
            [card_open_url(t('Open in the app'), day_url($dayId))]
        ));
        log_notification('day_summary', 'teams', $uid, $date, $dayId, 'Teams', $err);
        if ($err) {
            $warnings[] = t('Could not post to Teams: :err', ['err' => $err]);
        }
    }
    return $warnings;
}

/* ---------- Reminders ---------- */

function send_reminder(string $kind, array $person, string $date): ?string
{
    $link = base_url() . '/index.php?p=myday';
    $err = with_lang($person['lang'], function () use ($kind, $person, $date, $link) {
        if ($kind === 'remind_start') {
            $subject = t('Reminder: start your day');
            $body = t('You have not started your work day yet (:date). Please open the app and click Start Day.', ['date' => fmt_date($date)]);
        } else {
            $subject = t('Reminder: close your day');
            $body = t('Your work day (:date) is still open. Please log your work and click Close Day.', ['date' => fmt_date($date)]);
        }
        $html = '<p>' . e(t('Hello :name,', ['name' => $person['name']])) . '</p><p>' . e($body) . '</p>'
            . '<p><a href="' . e($link) . '" style="background:#1f3a5f;color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none">' . e(t('Open My Day')) . '</a></p>';
        return send_mail($person['email'], $subject, $html);
    });
    log_notification($kind, 'email', (int)$person['id'], $date, null, $person['email'], $err);
    return $err;
}

/* ---------- Digests ---------- */

/** One line per person for a date: status, start/close, hours, AI minutes, blockers. */
function team_day_rows(array $userIds, string $date): array
{
    if (!$userIds) {
        return [];
    }
    $out = rows(
        'SELECT u.id AS user_id, u.name, u.designation, wd.id AS day_id, wd.status, wd.started_at, wd.closed_at, wd.flagged, wd.flag_reason,
                COALESCE(SUM(wl.hours), 0) AS hours,
                COALESCE(SUM(CASE WHEN wl.ai_used = 1 THEN wl.ai_minutes ELSE 0 END), 0) AS ai_minutes,
                COALESCE(SUM(CASE WHEN wl.ai_used = 1 THEN wl.time_saved_minutes ELSE 0 END), 0) AS saved_minutes,
                COUNT(wl.id) AS entries,
                SUM(CASE WHEN wl.blockers IS NOT NULL AND wl.blockers <> \'\' THEN 1 ELSE 0 END) AS blockers
         FROM users u
         LEFT JOIN work_days wd ON wd.user_id = u.id AND wd.work_date = ?
         LEFT JOIN work_logs wl ON wl.work_day_id = wd.id
         WHERE u.id IN (' . in_list($userIds) . ')
         GROUP BY u.id, u.name, u.designation, wd.id, wd.status, wd.started_at, wd.closed_at, wd.flagged, wd.flag_reason
         ORDER BY u.name',
        array_merge([$date], array_values($userIds))
    );
    return $out;
}

function digest_status_label(array $r): string
{
    if (!$r['status']) {
        return t('Not started');
    }
    return work_day_statuses()[$r['status']]['label'] ?? $r['status'];
}

function send_manager_digest(array $manager, array $reports, string $date): ?string
{
    $rowsData = team_day_rows(array_column($reports, 'id'), $date);
    $err = with_lang($manager['lang'], function () use ($manager, $rowsData, $date) {
        $closed = count(array_filter($rowsData, fn($r) => in_array($r['status'], ['closed', 'auto_closed'], true)));
        $h = '<p>' . e(t('Hello :name,', ['name' => $manager['name']])) . '</p>'
            . '<p>' . e(t('Here is your team\'s day for :date. :closed of :total have closed their day.', ['date' => fmt_date($date), 'closed' => $closed, 'total' => count($rowsData)])) . '</p>'
            . '<table style="border-collapse:collapse;width:100%;font-size:13px">'
            . '<tr style="background:#f1f3f5"><th align="left" style="padding:6px">' . e(t('Name')) . '</th><th align="left" style="padding:6px">' . e(t('Status')) . '</th>'
            . '<th align="left" style="padding:6px">' . e(t('Start')) . '–' . e(t('Close')) . '</th><th align="right" style="padding:6px">' . e(t('Hours')) . '</th>'
            . '<th align="right" style="padding:6px">' . e(t('AI time')) . '</th><th align="right" style="padding:6px">' . e(t('Blockers')) . '</th></tr>';
        foreach ($rowsData as $r) {
            $bad = !$r['status'] || in_array($r['status'], ['open', 'missed', 'auto_closed'], true);
            $h .= '<tr style="border-top:1px solid #e9ecef">'
                . '<td style="padding:6px">' . ($r['day_id'] ? '<a href="' . e(day_url((int)$r['day_id'])) . '">' . e($r['name']) . '</a>' : e($r['name'])) . '</td>'
                . '<td style="padding:6px;' . ($bad ? 'color:#b02a37;font-weight:600' : 'color:#146c43') . '">' . e(digest_status_label($r)) . '</td>'
                . '<td style="padding:6px">' . e(fmt_time($r['started_at'])) . ' – ' . e(fmt_time($r['closed_at'])) . '</td>'
                . '<td align="right" style="padding:6px">' . e(fmt_hours((float)$r['hours'])) . '</td>'
                . '<td align="right" style="padding:6px">' . e($r['ai_minutes'] ? fmt_minutes((int)$r['ai_minutes']) : '—') . '</td>'
                . '<td align="right" style="padding:6px;' . ($r['blockers'] ? 'color:#b02a37;font-weight:600' : '') . '">' . (int)$r['blockers'] . '</td></tr>';
        }
        $h .= '</table><p style="margin-top:16px"><a href="' . e(base_url() . '/index.php?' . http_build_query(['p' => 'team', 'date' => $date])) . '">' . e(t('Open Team Day')) . '</a></p>';
        return send_mail($manager['email'], t('Team daily digest — :date', ['date' => fmt_date($date)]), $h);
    });
    log_notification('digest', 'email', (int)$manager['id'], $date, null, $manager['email'], $err);
    return $err;
}

/** Company-wide summary card for Teams. */
function post_teams_digest(string $date): ?string
{
    $people = daylog_required_users();
    $rowsData = team_day_rows(array_column($people, 'id'), $date);
    $err = with_lang(setting('default_lang', 'en'), function () use ($rowsData, $date) {
        $count = fn($pred) => count(array_filter($rowsData, $pred));
        $hours = array_sum(array_map(fn($r) => (float)$r['hours'], $rowsData));
        $ai = array_sum(array_map(fn($r) => (int)$r['ai_minutes'], $rowsData));
        $body = [
            card_text(t('Team daily digest — :date', ['date' => fmt_date($date)]), ['size' => 'Medium', 'weight' => 'Bolder']),
            card_facts([
                t('Closed their day') => $count(fn($r) => $r['status'] === 'closed') . ' / ' . count($rowsData),
                t('Still working') => $count(fn($r) => $r['status'] === 'open'),
                t('Not started') => $count(fn($r) => !$r['status'] || $r['status'] === 'missed'),
                t('On leave') => $count(fn($r) => $r['status'] === 'leave'),
                t('Hours logged') => fmt_hours($hours),
                t('AI time') => fmt_minutes((int)$ai),
            ]),
        ];
        $pending = array_filter($rowsData, fn($r) => !$r['status'] || in_array($r['status'], ['open', 'missed'], true));
        if ($pending) {
            $body[] = card_text(t('Not closed yet') . ': ' . implode(', ', array_column($pending, 'name')), ['color' => 'Warning']);
        }
        return teams_post($body, [card_open_url(t('Open Team Day'), base_url() . '/index.php?' . http_build_query(['p' => 'team', 'date' => $date]))]);
    });
    log_notification('teams_digest', 'teams', null, $date, null, 'Teams', $err);
    return $err;
}
