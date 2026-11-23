<?php
/** My Day: Start Day, log work (with AI usage), Close Day, and my past days. */
declare(strict_types=1);

$me = user();

/** The day being worked on: today, or an earlier day a manager reopened. */
function my_day_for(?string $date): ?array
{
    return get_work_day(uid(), $date ?: today());
}

function require_open_day(?array $day): array
{
    if (!$day) {
        flash('info', t('Start your day first.'));
        redirect(url('myday'));
    }
    if ((int)$day['user_id'] !== uid()) {
        abort(404);
    }
    if ($day['status'] !== 'open') {
        flash('warning', t('This day is closed, so it can no longer be changed. Ask your manager to reopen it if needed.'));
        redirect(url('myday', ['date' => $day['work_date']]));
    }
    return $day;
}

function entry_form_data(): array
{
    $aiUsed = input('ai_used') ? 1 : 0;
    $purpose = (string)input('ai_purpose', '');
    $quality = input_int('output_quality');
    // Times come from hours + minutes pickers; hours are stored as decimal hours (4 decimals keep minutes exact).
    $minutes = duration_input('hours', 24 * 60 + 1) ?? 0;
    return [
        'task_id'            => input_int('task_id') ?: null,
        'description'        => trim((string)input('description', '')) ?: null,
        'hours'              => round($minutes / 60, 4),
        'ai_used'            => $aiUsed,
        'ai_minutes'         => $aiUsed ? (duration_input('ai_minutes') ?? 0) : 0,
        'ai_purpose'         => $aiUsed && isset(ai_purposes()[$purpose]) ? $purpose : null,
        'time_saved_minutes' => $aiUsed ? (duration_input('time_saved_minutes') ?? 0) : 0,
        'output_quality'     => $aiUsed && $quality >= 1 && $quality <= 5 ? $quality : null,
        'blockers'           => trim((string)input('blockers', '')) ?: null,
    ];
}

switch ($action) {
    case 'index':
        auto_close_stale_days(uid());
        $date = input_date('date') ?? today();
        $day = my_day_for($date);
        if ($date !== today() && !$day) {
            redirect(url('myday'));
        }
        $entries = $day && !in_array($day['status'], ['missed', 'leave'], true) ? day_entries((int)$day['id']) : [];
        $edit = null;
        if ($day && $day['status'] === 'open' && ($editId = input_int('edit'))) {
            foreach ($entries as $e) {
                if ((int)$e['id'] === $editId) {
                    $edit = $e;
                }
            }
        }
        [$myTasks, $otherTasks] = worklog_task_options(uid());
        // Other open days of mine (e.g. reopened by my manager).
        $otherOpen = rows("SELECT work_date FROM work_days WHERE user_id = ? AND status = 'open' AND work_date <> ? ORDER BY work_date", [uid(), $date]);
        render('myday/index', [
            'pageTitle' => t('My Day'),
            'date' => $date, 'day' => $day, 'entries' => $entries, 'totals' => day_totals($entries), 'edit' => $edit,
            'myTasks' => $myTasks, 'otherTasks' => $otherTasks, 'tools' => active_ai_tools(),
            'otherOpen' => $otherOpen, 'presetTask' => input_int('task'),
            'workingDay' => is_working_day($date), 'holiday' => holidays()[$date] ?? null,
        ]);
        break;

    case 'start':
        if (!is_post()) {
            abort(405);
        }
        auto_close_stale_days(uid());
        $existing = get_work_day(uid(), today());
        if ($existing && in_array($existing['status'], ['closed', 'auto_closed'], true)) {
            flash('info', t('Today is already closed.'));
        } elseif (!$existing || $existing['status'] !== 'open') {
            $location = location_from_request('start');
            if (location_missing($location, 'start')) {
                flash('danger', t('Your location is needed to start or close your day. Allow location access in your browser and try again.'));
                back(url('myday'));
            }
            $day = start_day(uid(), $location);
            audit('workday', (int)$day['id'], $me['name'] . ' — ' . today(), 'start_day');
            flash('success', t('Good morning! Your day started at :time.', ['time' => fmt_time($day['started_at'])]));
        }
        back(url('myday'));

    case 'save_entry':
        if (!is_post()) {
            abort(405);
        }
        $day = require_open_day(my_day_for(input_date('date')));
        $d = entry_form_data();
        $logId = input_int('log_id');
        $back = url('myday', array_filter(['date' => $day['work_date'] !== today() ? $day['work_date'] : null]));
        $errors = [];
        $task = null;
        if ($d['task_id']) {
            $task = row('SELECT * FROM tasks WHERE id = ?', [$d['task_id']]);
            $project = $task ? row('SELECT * FROM projects WHERE id = ?', [$task['project_id']]) : null;
            if (!$task || !$project || !can_view_project($project)) {
                $errors[] = t('Please choose a task you have access to.');
            }
        } elseif (!$d['description']) {
            $errors[] = t('Choose a task, or describe the work you did.');
        }
        $entryMinutes = (int)round($d['hours'] * 60);
        if ($entryMinutes < 1) {
            $errors[] = t('Enter how long you worked (hours and minutes).');
        } elseif ($entryMinutes > 24 * 60) {
            $errors[] = t('One entry cannot be more than 24 hours.');
        }
        $otherHours = (float)val('SELECT COALESCE(SUM(hours), 0) FROM work_logs WHERE work_day_id = ? AND id <> ?', [$day['id'], $logId ?? 0]);
        if ((int)round(($otherHours + $d['hours']) * 60) > 24 * 60) {
            $errors[] = t('The total for one day cannot be more than 24 hours.');
        }
        $toolIds = array_values(array_intersect(array_map('intval', (array)($_POST['tools'] ?? [])), array_map('intval', array_column(active_ai_tools(), 'id'))));
        if ($d['ai_used']) {
            if (!$toolIds) {
                $errors[] = t('Select which AI tool(s) you used.');
            }
            if ($d['ai_minutes'] < 1) {
                $errors[] = t('Enter how long you spent using AI.');
            } elseif ($d['ai_minutes'] > $entryMinutes) {
                $errors[] = t('Time spent with AI cannot be more than the hours of this entry.');
            }
            if (!$d['ai_purpose']) {
                $errors[] = t('Choose what you used AI for.');
            }
            if (!$d['output_quality']) {
                $errors[] = t('Rate the quality of the AI output (1–5).');
            }
        }
        if ($errors) {
            foreach ($errors as $err) {
                flash('danger', $err);
            }
            remember_old();
            redirect($back . ($logId ? '&edit=' . $logId : '') . '#entry-form');
        }

        if ($logId) {
            $entry = row('SELECT * FROM work_logs WHERE id = ? AND work_day_id = ?', [$logId, $day['id']]) ?? abort(404);
            update('work_logs', $d + ['updated_at' => now()], ['id' => $entry['id']]);
            q('DELETE FROM work_log_ai_tools WHERE work_log_id = ?', [$entry['id']]);
        } else {
            $logId = insert('work_logs', $d + ['work_day_id' => $day['id'], 'user_id' => uid(), 'created_at' => now()]);
        }
        if ($d['ai_used']) {
            foreach ($toolIds as $tid) {
                q('INSERT IGNORE INTO work_log_ai_tools (work_log_id, ai_tool_id) VALUES (?, ?)', [$logId, $tid]);
            }
        }
        // Optional: move the task to a new status at the same time.
        $newStatus = (string)input('task_status', '');
        if ($task && $newStatus !== '' && isset(task_statuses()[$newStatus]) && $newStatus !== $task['status']) {
            if (can_update_task_status($task, $project)) {
                change_task_status($task, $newStatus);
            }
        }
        flash('success', t('Entry saved.'));
        redirect($back);

    case 'delete_entry':
        if (!is_post()) {
            abort(405);
        }
        $entry = row('SELECT * FROM work_logs WHERE id = ? AND user_id = ?', [(int)input('id'), uid()]) ?? abort(404);
        $day = require_open_day(row('SELECT * FROM work_days WHERE id = ?', [$entry['work_day_id']]));
        q('DELETE FROM work_logs WHERE id = ?', [$entry['id']]);
        flash('success', t('Entry deleted.'));
        redirect(url('myday', array_filter(['date' => $day['work_date'] !== today() ? $day['work_date'] : null])));

    case 'close':
        $day = require_open_day(my_day_for(input_date('date')));
        $entries = day_entries((int)$day['id']);
        $totals = day_totals($entries);
        if (is_post()) {
            $note = trim((string)input('note', ''));
            if (!$entries && $note === '') {
                flash('danger', t('You have not logged any work. Add at least one entry, or write a note explaining why.'));
                redirect(url('myday', ['a' => 'close', 'date' => $day['work_date']]));
            }
            $location = location_from_request('close');
            if (location_missing($location, 'close')) {
                flash('danger', t('Your location is needed to start or close your day. Allow location access in your browser and try again.'));
                remember_old();
                redirect(url('myday', ['a' => 'close', 'date' => $day['work_date']]));
            }
            $warnings = close_day($day, uid(), 'closed', $note !== '' ? $note : null, null, $location);
            flash('success', t('Your day is closed. Thank you!'));
            foreach ($warnings as $w) {
                flash('warning', $w);
            }
            redirect(url('myday', ['date' => $day['work_date']]));
        }
        $day['user_name'] = $me['name'];
        render('myday/close', [
            'pageTitle' => t('Close Day'), 'day' => $day, 'entries' => $entries, 'totals' => $totals,
            'recipient' => summary_recipient(['reports_to' => $me['reports_to'], 'department_id' => $me['department_id'], 'user_id' => uid()]),
        ]);
        break;

    case 'history':
        $from = input_date('from') ?? date('Y-m-d', strtotime('-30 days'));
        $to = input_date('to') ?? today();
        $days = rows(
            'SELECT wd.*, COALESCE(SUM(wl.hours), 0) AS hours, COUNT(wl.id) AS entries,
                    COALESCE(SUM(CASE WHEN wl.ai_used = 1 THEN wl.ai_minutes ELSE 0 END), 0) AS ai_minutes,
                    COALESCE(SUM(CASE WHEN wl.ai_used = 1 THEN wl.time_saved_minutes ELSE 0 END), 0) AS saved_minutes
             FROM work_days wd LEFT JOIN work_logs wl ON wl.work_day_id = wd.id
             WHERE wd.user_id = ? AND wd.work_date BETWEEN ? AND ?
             GROUP BY wd.id ORDER BY wd.work_date DESC',
            [uid(), $from, $to]
        );
        render('myday/history', ['pageTitle' => t('My past days'), 'days' => $days, 'from' => $from, 'to' => $to]);
        break;

    default:
        abort(404);
}
