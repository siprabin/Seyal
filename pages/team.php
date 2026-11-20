<?php
/** Team Day: managers see their team's daily status and each person's day; reopen days; mark leave. */
declare(strict_types=1);

switch ($action) {
    case 'index':
        $ids = team_member_ids();
        if (!$ids) {
            abort(403, t('Nobody reports to you, so there is no team to show.'));
        }
        $date = input_date('date') ?? today();
        $deptId = input_int('department');
        $show = (string)input('show', 'required');
        $where = ['u.id IN (' . in_list($ids) . ')', 'u.is_active = 1'];
        $params = $ids;
        if ($deptId) {
            $where[] = 'u.department_id = ?';
            $params[] = $deptId;
        }
        if ($show === 'required' && daylog_roles()) {
            $where[] = 'r.slug IN (' . in_list(daylog_roles()) . ')';
            $params = array_merge($params, array_values(daylog_roles()));
        }
        $people = rows('SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE ' . implode(' AND ', $where), $params);
        $teamRows = team_day_rows(array_map('intval', array_column($people, 'id')), $date);
        $counts = ['all' => count($teamRows), 'open' => 0, 'closed' => 0, 'auto_closed' => 0, 'none' => 0, 'leave' => 0, 'late' => 0, 'hours' => 0.0, 'ai' => 0, 'blockers' => 0];
        foreach ($teamRows as $r) {
            $s = $r['status'] ?: 'none';
            if ($s === 'missed') {
                $s = 'none';
            }
            $counts[$s] = ($counts[$s] ?? 0) + 1;
            if ($r['started_at'] && date('H:i', strtotime($r['started_at'])) > time_setting('remind_start_time', '11:00')) {
                $counts['late']++;
            }
            $counts['hours'] += (float)$r['hours'];
            $counts['ai'] += (int)$r['ai_minutes'];
            $counts['blockers'] += (int)$r['blockers'];
        }
        $deptIds = array_unique(array_filter(array_map('intval', q('SELECT department_id FROM users WHERE id IN (' . in_list($ids) . ')', $ids)->fetchAll(PDO::FETCH_COLUMN))));
        $departments = $deptIds ? rows('SELECT id, name FROM departments WHERE id IN (' . in_list($deptIds) . ') ORDER BY name', array_values($deptIds)) : [];
        render('team/index', [
            'pageTitle' => t('Team Day'), 'date' => $date, 'rows' => $teamRows, 'counts' => $counts,
            'departments' => $departments, 'deptId' => $deptId, 'show' => $show,
            'workingDay' => is_working_day($date),
        ]);
        break;

    case 'day':
        $day = find_work_day((int)input('id')) ?? abort(404);
        if (!can_view_person_day((int)$day['user_id'])) {
            abort(403);
        }
        $entries = in_array($day['status'], ['missed', 'leave'], true) ? [] : day_entries((int)$day['id']);
        $notes = rows("SELECT kind, channel, recipient, status, error, created_at FROM notification_log WHERE ref_id = ? AND kind LIKE 'day_summary%' ORDER BY id DESC LIMIT 10", [$day['id']]);
        $history = rows("SELECT * FROM audit_log WHERE entity_type = 'workday' AND entity_id = ? ORDER BY id DESC LIMIT 30", [$day['id']]);
        $prev = row('SELECT id FROM work_days WHERE user_id = ? AND work_date < ? ORDER BY work_date DESC LIMIT 1', [$day['user_id'], $day['work_date']]);
        $next = row('SELECT id FROM work_days WHERE user_id = ? AND work_date > ? ORDER BY work_date LIMIT 1', [$day['user_id'], $day['work_date']]);
        render('team/day', [
            'pageTitle' => $day['user_name'] . ' — ' . fmt_date($day['work_date']),
            'day' => $day, 'entries' => $entries, 'totals' => day_totals($entries), 'notes' => $notes, 'history' => $history,
            'canManage' => can_manage_person_day((int)$day['user_id']), 'prev' => $prev, 'next' => $next,
        ]);
        break;

    case 'reopen':
        if (!is_post()) {
            abort(405);
        }
        $day = find_work_day((int)input('id')) ?? abort(404);
        if (!can_manage_person_day((int)$day['user_id'])) {
            abort(403);
        }
        $reason = trim((string)input('reason', ''));
        if ($reason === '') {
            flash('danger', t('Please give a reason for reopening.'));
        } elseif (!in_array($day['status'], ['closed', 'auto_closed'], true)) {
            flash('warning', t('Only a closed day can be reopened.'));
        } else {
            reopen_day($day, $reason);
            flash('success', t(':name can now add to this day and close it again.', ['name' => $day['user_name']]));
        }
        redirect(url('team', ['a' => 'day', 'id' => $day['id']]));

    case 'leave':
        if (!is_post()) {
            abort(405);
        }
        $userId = (int)input('user');
        $date = input_date('date') ?? today();
        if (!can_manage_person_day($userId)) {
            abort(403);
        }
        $existing = get_work_day($userId, $date);
        $name = user_name($userId);
        if ($existing && !in_array($existing['status'], ['missed', 'leave'], true)) {
            flash('warning', t('This person already started work that day.'));
        } elseif ($existing && $existing['status'] === 'leave') {
            q('DELETE FROM work_days WHERE id = ?', [$existing['id']]);
            audit('workday', (int)$existing['id'], "$name — $date", 'update', 'Leave removed');
            flash('success', t('Leave removed.'));
        } else {
            if ($existing) {
                update('work_days', ['status' => 'leave', 'flagged' => 0, 'flag_reason' => null, 'updated_at' => now()], ['id' => $existing['id']]);
                $id = (int)$existing['id'];
            } else {
                $id = insert('work_days', ['user_id' => $userId, 'work_date' => $date, 'status' => 'leave', 'created_at' => now()]);
            }
            audit('workday', $id, "$name — $date", 'update', 'Marked on leave');
            flash('success', t(':name marked on leave for :date.', ['name' => $name, 'date' => fmt_date($date)]));
        }
        redirect(url('team', ['date' => $date]));

    case 'resend':
        if (!is_post()) {
            abort(405);
        }
        $day = find_work_day((int)input('id')) ?? abort(404);
        if (!can_manage_person_day((int)$day['user_id']) && !can('settings.manage')) {
            abort(403);
        }
        $warnings = send_day_summary((int)$day['id']);
        $warnings ? array_map(fn($w) => flash('warning', $w), $warnings) : flash('success', t('Summary sent again.'));
        redirect(url('team', ['a' => 'day', 'id' => $day['id']]));

    default:
        abort(404);
}
