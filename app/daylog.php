<?php
/**
 * Daily work: Start Day, work-log entries (with AI usage), Close Day,
 * auto-close / missed days, and who may see whose day.
 */
declare(strict_types=1);

/* ---------- Lists ---------- */

function work_day_statuses(): array
{
    return [
        'open'        => ['label' => t('Working'),           'color' => 'primary'],
        'closed'      => ['label' => t('Closed'),            'color' => 'success'],
        'auto_closed' => ['label' => t('Auto-closed'),       'color' => 'warning'],
        'missed'      => ['label' => t('Not started'),       'color' => 'danger'],
        'leave'       => ['label' => t('On leave'),          'color' => 'secondary'],
    ];
}

function ai_purposes(): array
{
    return [
        'coding'        => t('Coding'),
        'debugging'     => t('Debugging / testing'),
        'writing'       => t('Writing / content'),
        'research'      => t('Research'),
        'design'        => t('Design / images'),
        'data'          => t('Data analysis'),
        'documentation' => t('Documentation'),
        'translation'   => t('Translation'),
        'communication' => t('Email / communication'),
        'other'         => t('Other'),
    ];
}

function quality_labels(): array
{
    return [1 => t('Poor'), 2 => t('Fair'), 3 => t('Good'), 4 => t('Very good'), 5 => t('Excellent')];
}

function active_ai_tools(): array
{
    return rows('SELECT id, name FROM ai_tools WHERE is_active = 1 ORDER BY sort_order, name');
}

/* ---------- Settings helpers ---------- */

/** Roles that are expected to Start/Close their day (reminders, digests, missed-day flags). */
function daylog_roles(): array
{
    return array_filter(explode(',', (string)setting('daylog_roles', 'dept_head,manager,member')));
}

function daylog_required(array $user): bool
{
    $role = $user['role'] ?? (string)val('SELECT slug FROM roles WHERE id = ?', [$user['role_id']]);
    return in_array($role, daylog_roles(), true);
}

/** Can this person use My Day at all? Everyone internal can, even if not required. */
function daylog_enabled_for(array $user): bool
{
    return ($user['role'] ?? '') !== ROLE_CLIENT;
}

function holidays(): array
{
    $out = [];
    foreach (preg_split('/\r?\n/', (string)setting('holidays', '')) as $line) {
        if (preg_match('/^\s*(\d{4}-\d{2}-\d{2})\s*(.*)$/', $line, $m)) {
            $out[$m[1]] = trim($m[2]);
        }
    }
    return $out;
}

function is_working_day(string $date): bool
{
    $days = array_map('intval', explode(',', (string)setting('working_days', '1,2,3,4,5,6')));
    return in_array((int)date('N', strtotime($date)), $days, true) && !isset(holidays()[$date]);
}

function time_setting(string $key, string $default): string
{
    $v = (string)setting($key, $default);
    return preg_match('/^\d{2}:\d{2}$/', $v) ? $v : $default;
}

/* ---------- Reading days ---------- */

function get_work_day(int $userId, string $date): ?array
{
    return row('SELECT * FROM work_days WHERE user_id = ? AND work_date = ?', [$userId, $date]);
}

function find_work_day(int $id): ?array
{
    return row(
        'SELECT wd.*, u.name AS user_name, u.email AS user_email, u.reports_to, u.lang AS user_lang, u.department_id,
                c.name AS closed_by_name
         FROM work_days wd JOIN users u ON u.id = wd.user_id LEFT JOIN users c ON c.id = wd.closed_by
         WHERE wd.id = ?',
        [$id]
    );
}

/** Entries of a day, with task/project names and the AI tools used. */
function day_entries(int $dayId): array
{
    $entries = rows(
        'SELECT wl.*, t.title AS task_title, t.status AS task_status, p.id AS project_id, p.name AS project_name
         FROM work_logs wl
         LEFT JOIN tasks t ON t.id = wl.task_id
         LEFT JOIN projects p ON p.id = t.project_id
         WHERE wl.work_day_id = ? ORDER BY wl.created_at, wl.id',
        [$dayId]
    );
    if ($entries) {
        $ids = array_column($entries, 'id');
        $tools = [];
        foreach (rows('SELECT x.work_log_id, a.id, a.name FROM work_log_ai_tools x JOIN ai_tools a ON a.id = x.ai_tool_id
                       WHERE x.work_log_id IN (' . in_list($ids) . ') ORDER BY a.sort_order, a.name', $ids) as $r) {
            $tools[(int)$r['work_log_id']][(int)$r['id']] = $r['name'];
        }
        foreach ($entries as &$e) {
            $e['tools'] = $tools[(int)$e['id']] ?? [];
        }
        unset($e);
    }
    return $entries;
}

function day_totals(array $entries): array
{
    $t = ['hours' => 0.0, 'ai_minutes' => 0, 'saved_minutes' => 0, 'count' => count($entries), 'ai_entries' => 0, 'tools' => [], 'blockers' => []];
    foreach ($entries as $e) {
        $t['hours'] += (float)$e['hours'];
        if ($e['ai_used']) {
            $t['ai_entries']++;
            $t['ai_minutes'] += (int)$e['ai_minutes'];
            $t['saved_minutes'] += (int)$e['time_saved_minutes'];
            foreach ($e['tools'] as $name) {
                $t['tools'][$name] = true;
            }
        }
        if (trim((string)$e['blockers']) !== '') {
            $t['blockers'][] = ['task' => $e['task_title'] ?: t('Other work'), 'text' => $e['blockers']];
        }
    }
    $t['tools'] = array_keys($t['tools']);
    return $t;
}

/** Hours always with two decimals (0.30, 1.50, 8.00), matching how they are entered. */
function fmt_hours(float $h): string
{
    return number_format($h, 2, '.', '');
}

function fmt_minutes(int $m): string
{
    if ($m < 60) {
        return t(':m min', ['m' => $m]);
    }
    return t(':h h :m min', ['h' => intdiv($m, 60), 'm' => $m % 60]);
}

function fmt_time(?string $dt): string
{
    return $dt ? date('h:i A', strtotime($dt)) : '—';
}

function is_late_start(array $day): bool
{
    return $day['started_at'] && date('H:i', strtotime($day['started_at'])) > time_setting('remind_start_time', '11:00');
}

/* ---------- Location at Start Day / Close Day ---------- */

/** Settings → Daily work → Location: 'off', 'ask' (record when the person allows) or 'required'. */
function location_mode(): string
{
    $m = (string)setting('location_mode', 'off');
    return in_array($m, ['off', 'ask', 'required'], true) ? $m : 'off';
}

/**
 * Location sent by the browser with the Start Day / Close Day form, as work_days columns
 * ($prefix 'start' or 'close'). Empty when location capture is off.
 */
function location_from_request(string $prefix): array
{
    if (location_mode() === 'off') {
        return [];
    }
    $lat = filter_var(input('loc_lat'), FILTER_VALIDATE_FLOAT);
    $lng = filter_var(input('loc_lng'), FILTER_VALIDATE_FLOAT);
    $ok = $lat !== false && $lng !== false && abs($lat) <= 90 && abs($lng) <= 180 && !($lat == 0 && $lng == 0);
    $status = (string)input('loc_status', '');
    if (!in_array($status, ['denied', 'unavailable', 'timeout', 'unsupported'], true)) {
        $status = 'unavailable';
    }
    $acc = filter_var(input('loc_acc'), FILTER_VALIDATE_FLOAT);
    return [
        $prefix . '_lat'        => $ok ? round($lat, 6) : null,
        $prefix . '_lng'        => $ok ? round($lng, 6) : null,
        $prefix . '_accuracy'   => $ok && $acc !== false ? (int)min(max(round($acc), 0), 1000000) : null,
        $prefix . '_loc_status' => $ok ? 'ok' : $status,
    ];
}

/** Attribute for Start Day / Close Day forms: app.js asks the browser for the location before submitting. */
function location_form_attr(): string
{
    $mode = location_mode();
    return $mode === 'off' ? '' : ' data-location="' . $mode . '"';
}

/** True when the location is required but the browser did not send one. */
function location_missing(array $loc, string $prefix): bool
{
    return location_mode() === 'required' && ($loc[$prefix . '_loc_status'] ?? '') !== 'ok';
}

/**
 * Small map-pin link for a recorded location ($prefix 'start' or 'close'), or a muted icon when the
 * person did not share it. Empty when nothing was recorded (capture off, closed by a manager or automatically).
 */
function location_pin(array $day, string $prefix, bool $withText = false): string
{
    $status = $day[$prefix . '_loc_status'] ?? null;
    if (!$status) {
        return '';
    }
    if ($status !== 'ok') {
        $why = $status === 'denied' ? t('Location access was blocked') : t('Location not available');
        return ' <span class="text-muted" title="' . e($why) . '"><i class="bi bi-geo"></i>' . ($withText ? ' <small>' . e($why) . '</small>' : '') . '</span>';
    }
    $lat = (float)$day[$prefix . '_lat'];
    $lng = (float)$day[$prefix . '_lng'];
    $acc = $day[$prefix . '_accuracy'] !== null ? (int)$day[$prefix . '_accuracy'] : null;
    $coords = number_format($lat, 5, '.', '') . ', ' . number_format($lng, 5, '.', '');
    $tip = $coords . ($acc !== null ? ' · ' . t('Accuracy: ±:n m', ['n' => $acc]) : '');
    // Laptops locate by Wi-Fi/IP and can be far off; flag anything less precise than 1 km.
    $rough = $acc !== null && $acc > 1000;
    return ' <a href="' . e('https://www.google.com/maps?q=' . rawurlencode($coords)) . '" target="_blank" rel="noopener" class="text-decoration-none' . ($rough ? ' text-warning-emphasis' : '') . '" title="' . e($tip) . '">'
        . '<i class="bi bi-geo-alt-fill"></i>'
        . ($withText ? ' <span class="ltr small">' . e($coords) . '</span>' . ($acc !== null ? ' <small class="text-muted ltr">±' . $acc . ' m</small>' : '') : '')
        . '</a>';
}

/* ---------- Changing days ---------- */

function start_day(int $userId, array $location = []): array
{
    $today = today();
    $day = get_work_day($userId, $today);
    if ($day && in_array($day['status'], ['open', 'closed', 'auto_closed'], true)) {
        return $day;
    }
    if ($day) {
        // A "missed"/"leave" placeholder for today: turn it into a real day.
        update('work_days', ['status' => 'open', 'started_at' => now(), 'flagged' => 0, 'flag_reason' => null, 'updated_at' => now()] + $location, ['id' => $day['id']]);
    } else {
        insert('work_days', ['user_id' => $userId, 'work_date' => $today, 'status' => 'open', 'started_at' => now(), 'created_at' => now()] + $location);
    }
    return get_work_day($userId, $today);
}

/**
 * Close a day and send the summary (email to manager + Teams).
 * Returns a list of warnings about notifications that failed (the day is closed regardless).
 */
function close_day(array $day, ?int $closedBy, string $status = 'closed', ?string $note = null, ?string $flagReason = null, array $location = []): array
{
    // A day closed by a manager or automatically has no close location; clear one left from before a reopen.
    if (!$location && location_mode() !== 'off') {
        $location = ['close_lat' => null, 'close_lng' => null, 'close_accuracy' => null, 'close_loc_status' => null];
    }
    update('work_days', $location + [
        'status'      => $status,
        'closed_at'   => now(),
        'closed_by'   => $closedBy,
        'note'        => $note ?? $day['note'],
        'flagged'     => $flagReason ? 1 : (int)$day['flagged'],
        'flag_reason' => $flagReason ?? $day['flag_reason'],
        'updated_at'  => now(),
    ], ['id' => $day['id']]);
    $name = (string)val('SELECT name FROM users WHERE id = ?', [$day['user_id']]);
    $label = $name . ' — ' . $day['work_date'];
    if ($closedBy) {
        $u = user();
        audit_raw($closedBy, $u['name'] ?? $name, 'workday', (int)$day['id'], $label, 'close_day', null, null, null, $flagReason);
    } else {
        audit_raw(null, null, 'workday', (int)$day['id'], $label, 'auto_close', null, null, null, $flagReason);
    }
    return send_day_summary((int)$day['id']);
}

function reopen_day(array $day, string $reason): void
{
    update('work_days', [
        'status' => 'open', 'closed_at' => null, 'closed_by' => null,
        'reopened_count' => (int)$day['reopened_count'] + 1, 'updated_at' => now(),
    ], ['id' => $day['id']]);
    $name = (string)val('SELECT name FROM users WHERE id = ?', [$day['user_id']]);
    audit('workday', (int)$day['id'], $name . ' — ' . $day['work_date'], 'reopen_day', $reason);
}

/** Close any day still open from an earlier date (the person forgot). Optionally for one user only. */
function auto_close_stale_days(?int $userId = null, int $limit = 50): int
{
    // A day a manager reopened today stays open until the end of today.
    $params = [today(), today() . ' 00:00:00'];
    $sql = "SELECT * FROM work_days WHERE status = 'open' AND work_date < ? AND (updated_at IS NULL OR updated_at < ?)";
    if ($userId) {
        $sql .= ' AND user_id = ?';
        $params[] = $userId;
    }
    $n = 0;
    foreach (rows($sql . ' ORDER BY work_date LIMIT ' . (int)$limit, $params) as $day) {
        close_day($day, null, 'auto_closed', null, 'Day was not closed by the person');
        $n++;
    }
    return $n;
}

/** Users expected to log their day (active, internal, role in daylog_roles). */
function daylog_required_users(): array
{
    $roles = daylog_roles();
    if (!$roles) {
        return [];
    }
    return rows(
        'SELECT u.id, u.name, u.email, u.lang, u.reports_to, u.department_id, u.created_at
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.is_active = 1 AND r.slug IN (' . in_list($roles) . ') ORDER BY u.name',
        array_values($roles)
    );
}

/** Record "Not started" for required people who never started on a working day. */
function mark_missed_days(string $date): int
{
    if (!is_working_day($date)) {
        return 0;
    }
    $n = 0;
    foreach (daylog_required_users() as $u) {
        if (substr($u['created_at'], 0, 10) > $date) {
            continue;
        }
        $n += q(
            'INSERT IGNORE INTO work_days (user_id, work_date, status, flagged, flag_reason, created_at) VALUES (?, ?, ?, 1, ?, ?)',
            [$u['id'], $date, 'missed', 'Day was not started', now()]
        )->rowCount();
    }
    return $n;
}

/* ---------- Who can see whose day ---------- */

/** IDs of people whose daily work the given viewer may see (excluding the viewer). */
function team_member_ids(?array $viewer = null): array
{
    $viewer ??= user();
    if (!$viewer) {
        return [];
    }
    static $cache = [];
    $vid = (int)$viewer['id'];
    if (isset($cache[$vid])) {
        return $cache[$vid];
    }
    $all = rows("SELECT u.id, u.reports_to, u.department_id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug <> 'client'");
    if ($viewer['role'] === ROLE_SUPER_ADMIN || can('team.view_all')) {
        $ids = array_map(fn($u) => (int)$u['id'], $all);
    } else {
        // Everyone below the viewer in the "Reports to" tree.
        $children = [];
        foreach ($all as $u) {
            $children[(int)$u['reports_to']][] = (int)$u['id'];
        }
        $ids = [];
        $queue = $children[$vid] ?? [];
        while ($queue && count($ids) < 5000) {
            $id = array_shift($queue);
            if (isset($ids[$id])) {
                continue;
            }
            $ids[$id] = $id;
            array_push($queue, ...($children[$id] ?? []));
        }
        $ids = array_values($ids);
        // Department heads (by permission or by being named head) see their department.
        $depts = array_map('intval', q('SELECT id FROM departments WHERE head_user_id = ?', [$vid])->fetchAll(PDO::FETCH_COLUMN));
        if (can('team.view_department') && $viewer['department_id']) {
            $depts[] = (int)$viewer['department_id'];
        }
        foreach ($all as $u) {
            if ($u['department_id'] && in_array((int)$u['department_id'], $depts, true)) {
                $ids[] = (int)$u['id'];
            }
        }
    }
    return $cache[$vid] = array_values(array_diff(array_unique($ids), [$vid]));
}

function can_view_person_day(int $userId): bool
{
    return $userId === uid() || in_array($userId, team_member_ids(), true);
}

/** Reopen / mark leave: managers of the person (not the person themselves). */
function can_manage_person_day(int $userId): bool
{
    return $userId !== uid() && in_array($userId, team_member_ids(), true);
}

/** Who receives a person's Close Day summary: their "Reports to", else their department head. */
function summary_recipient(array $person): ?array
{
    if ($person['reports_to']) {
        $m = row('SELECT id, name, email, lang FROM users WHERE id = ? AND is_active = 1', [$person['reports_to']]);
        if ($m) {
            return $m;
        }
    }
    if ($person['department_id']) {
        return row(
            'SELECT u.id, u.name, u.email, u.lang FROM departments d JOIN users u ON u.id = d.head_user_id
             WHERE d.id = ? AND u.is_active = 1 AND u.id <> ?',
            [$person['department_id'], $person['user_id'] ?? $person['id']]
        );
    }
    return null;
}

/** Task options for the work-log form: my open tasks, then other open tasks in my projects. */
function worklog_task_options(int $userId): array
{
    [$scope, $sp] = project_scope('p');
    $base = "SELECT t.id, t.title, t.status, t.assignee_id, p.name AS project_name
             FROM tasks t JOIN projects p ON p.id = t.project_id
             WHERE $scope AND p.status NOT IN ('completed','cancelled')
               AND (t.status <> 'done' OR t.completed_at >= ?)";
    $mine = rows("$base AND t.assignee_id = ? ORDER BY p.name, t.title", array_merge($sp, [today(), $userId]));
    $other = rows(
        "$base AND (t.assignee_id IS NULL OR t.assignee_id <> ?)
           AND EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id = p.id AND pm.user_id = ?)
         ORDER BY p.name, t.title LIMIT 300",
        array_merge($sp, [today(), $userId, $userId])
    );
    return [$mine, $other];
}
