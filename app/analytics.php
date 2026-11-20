<?php
/**
 * Phase 3 analytics: project health (forecast + 🟢🟡🔴), task ratings,
 * performance scores, per-person metrics and period helpers.
 *
 * Performance score (0–100) over a period:
 *     Σ (weightage × quality × on-time factor)  /  Σ (weightage × 5)  × 100
 * on-time factor = 1 when finished by the due date, otherwise the "late factor" setting (default 0.7).
 */
declare(strict_types=1);

/* ---------- Settings ---------- */

function late_factor(): float
{
    $f = (float)setting('late_factor', '0.7');
    return max(0.0, min(1.0, $f));
}

function amber_days(): int
{
    return max(0, (int)setting('rag_amber_days', '7'));
}

/* ---------- Periods ---------- */

/** Named periods for filters. Returns [from, to] as Y-m-d. */
function period_range(string $key, ?string $from = null, ?string $to = null): array
{
    $t = today();
    return match ($key) {
        'this_week'  => [date('Y-m-d', strtotime('monday this week')), $t],
        'last_week'  => [date('Y-m-d', strtotime('monday last week')), date('Y-m-d', strtotime('sunday last week'))],
        'last_month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
        'last_3m'    => [date('Y-m-d', strtotime('-3 months', strtotime($t)) + 86400), $t],
        'this_year'  => [date('Y-01-01'), $t],
        'custom'     => [$from ?: date('Y-m-01'), $to ?: $t],
        default      => [date('Y-m-01'), $t], // this_month
    };
}

function period_options(): array
{
    return [
        'this_week' => t('This week'), 'last_week' => t('Last week'),
        'this_month' => t('This month'), 'last_month' => t('Last month'),
        'last_3m' => t('Last 3 months'), 'this_year' => t('This year'), 'custom' => t('Custom'),
    ];
}

/** Read period filter from the request: [key, from, to]. */
function request_period(string $default = 'this_month'): array
{
    $key = (string)input('period', $default);
    if (!isset(period_options()[$key])) {
        $key = $default;
    }
    [$from, $to] = period_range($key, input_date('from'), input_date('to'));
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }
    return [$key, $from, $to];
}

function week_start(string $date): string
{
    return date('Y-m-d', strtotime('monday this week', strtotime($date)));
}

/* ---------- Project health ---------- */

function rag_badge(string $rag, ?string $reason = null): string
{
    $map = [
        'green' => ['🟢', t('On track'), 'success'],
        'amber' => ['🟡', t('At risk'), 'warning'],
        'red'   => ['🔴', t('Off track'), 'danger'],
        'grey'  => ['⚪', t('No data'), 'secondary'],
        'done'  => ['✅', t('Complete'), 'success'],
    ];
    [$dot, $label, $c] = $map[$rag] ?? $map['grey'];
    return '<span class="rag rag-' . e($rag) . '" title="' . e($reason ?? $label) . '"><span aria-hidden="true">' . $dot . '</span> ' . e($label) . '</span>';
}

/**
 * Health for each project: % done, forecast finish date (from the last 4 weeks' pace),
 * and a 🟢🟡🔴 status with a short reason.
 * Returns [id => [pct, total, done, overdue, forecast, rag, reason]].
 */
function project_health(array $projects): array
{
    $out = [];
    $ids = array_map(fn($p) => (int)$p['id'], $projects);
    if (!$ids) {
        return $out;
    }
    $progress = project_progress($ids);
    $since = date('Y-m-d', strtotime('-28 days'));
    $pace = [];
    foreach (rows(
        'SELECT t.project_id, SUM(t.weightage) AS w_total,
                SUM(CASE WHEN t.status = \'done\' THEN t.weightage ELSE 0 END) AS w_done,
                SUM(CASE WHEN t.status = \'done\' AND t.completed_at >= ? THEN t.weightage ELSE 0 END) AS w_recent,
                MAX(t.completed_at) AS last_done
         FROM tasks t
         WHERE t.project_id IN (' . in_list($ids) . ')
           AND NOT EXISTS (SELECT 1 FROM tasks c WHERE c.parent_id = t.id)
         GROUP BY t.project_id',
        array_merge([$since], $ids)
    ) as $r) {
        $pace[(int)$r['project_id']] = $r;
    }
    $today = today();
    foreach ($projects as $p) {
        $id = (int)$p['id'];
        $pr = $progress[$id];
        $pc = $pace[$id] ?? ['w_total' => 0, 'w_done' => 0, 'w_recent' => 0, 'last_done' => null];
        $remaining = (float)$pc['w_total'] - (float)$pc['w_done'];
        $forecast = null;
        $target = $p['target_date'] ?: null;

        if ((float)$pc['w_total'] <= 0) {
            [$rag, $reason] = ['grey', t('No tasks yet')];
        } elseif ($remaining <= 0) {
            $forecast = $pc['last_done'] ? substr($pc['last_done'], 0, 10) : $today;
            [$rag, $reason] = ['done', t('All tasks done')];
        } else {
            $perDay = (float)$pc['w_recent'] / 28;
            if ($perDay > 0) {
                $forecast = date('Y-m-d', strtotime('+' . (int)ceil($remaining / $perDay) . ' days'));
            }
            if (in_array($p['status'], ['on_hold', 'cancelled'], true)) {
                [$rag, $reason] = ['grey', project_statuses()[$p['status']]['label']];
            } elseif ($target && $target < $today) {
                [$rag, $reason] = ['red', t('Target date has passed')];
            } elseif ($forecast === null) {
                $close = $target && $target <= date('Y-m-d', strtotime('+14 days'));
                [$rag, $reason] = [$close ? 'red' : 'amber', t('No progress in the last 4 weeks')];
            } elseif (!$target) {
                [$rag, $reason] = [$pr['overdue'] ? 'amber' : 'green', $pr['overdue'] ? t(':n overdue', ['n' => $pr['overdue']]) : t('No target date set')];
            } else {
                $slip = (int)round((strtotime($forecast) - strtotime($target)) / 86400);
                if ($slip <= 0) {
                    [$rag, $reason] = ['green', t('Forecast is on or before the target date')];
                } elseif ($slip <= amber_days()) {
                    [$rag, $reason] = ['amber', t('Forecast :n days after target', ['n' => $slip])];
                } else {
                    [$rag, $reason] = ['red', t('Forecast :n days after target', ['n' => $slip])];
                }
                if ($rag === 'green' && $pr['overdue']) {
                    [$rag, $reason] = ['amber', t(':n overdue', ['n' => $pr['overdue']])];
                }
            }
        }
        $out[$id] = $pr + ['forecast' => $forecast, 'rag' => $rag, 'reason' => $reason];
    }
    return $out;
}

/* ---------- Task ratings ---------- */

/** The direct manager rates; if the assignee has no manager, the Super Admin can. */
function can_rate_task(array $task): bool
{
    if (!$task['assignee_id'] || $task['status'] !== 'done' || (int)$task['assignee_id'] === uid()) {
        return false;
    }
    $mgr = (int)val('SELECT reports_to FROM users WHERE id = ?', [$task['assignee_id']]);
    return $mgr === uid() || (!$mgr && is_super_admin());
}

/** Ratings are locked once an appraisal covering that date is finalised or published. */
function rating_locked(int $userId, string $date): bool
{
    return (bool)val(
        "SELECT COUNT(*) FROM appraisals WHERE user_id = ? AND status IN ('finalised','published') AND ? BETWEEN period_from AND period_to",
        [$userId, $date]
    );
}

function save_task_rating(array $task, int $quality, ?string $comment): ?string
{
    if (!can_rate_task($task)) {
        return t('Only the assignee\'s direct manager can rate this task.');
    }
    $quality = max(1, min(5, $quality));
    $completed = substr((string)($task['completed_at'] ?: now()), 0, 10);
    if (rating_locked((int)$task['assignee_id'], $completed)) {
        return t('This rating is part of a finalised appraisal and can no longer be changed.');
    }
    $daysLate = $task['due_date'] ? (int)round((strtotime($completed) - strtotime($task['due_date'])) / 86400) : 0;
    $data = [
        'user_id' => (int)$task['assignee_id'], 'rated_by' => uid(), 'quality' => $quality,
        'weightage' => (int)$task['weightage'], 'on_time' => $daysLate <= 0 ? 1 : 0,
        'days_late' => max(0, $daysLate), 'completed_on' => $completed,
        'comment' => $comment !== null && trim($comment) !== '' ? mb_substr(trim($comment), 0, 2000) : null,
    ];
    $existing = row('SELECT * FROM task_ratings WHERE task_id = ?', [$task['id']]);
    if ($existing) {
        update('task_ratings', $data + ['updated_at' => now()], ['id' => $existing['id']]);
        audit_diff('task', (int)$task['id'], $task['title'], ['rating' => $existing['quality'] . '/5'], ['rating' => $quality . '/5']);
    } else {
        insert('task_ratings', $data + ['task_id' => $task['id'], 'created_at' => now()]);
        audit('task', (int)$task['id'], $task['title'], 'rate', $quality . '/5');
    }
    return null;
}

/** When a done task is reopened its rating no longer applies (unless locked by an appraisal). */
function clear_rating_on_reopen(int $taskId): void
{
    $r = row('SELECT * FROM task_ratings WHERE task_id = ?', [$taskId]);
    if ($r && !rating_locked((int)$r['user_id'], $r['completed_on'])) {
        q('DELETE FROM task_ratings WHERE id = ?', [$r['id']]);
    }
}

/** Done tasks of my direct reports that still need a rating. */
function tasks_to_rate(int $managerId, int $limit = 200): array
{
    $superExtra = is_super_admin() ? ' OR u.reports_to IS NULL' : '';
    return rows(
        "SELECT t.*, u.name AS assignee_name, p.name AS project_name
         FROM tasks t JOIN users u ON u.id = t.assignee_id JOIN projects p ON p.id = t.project_id
         LEFT JOIN task_ratings r ON r.task_id = t.id
         WHERE t.status = 'done' AND r.id IS NULL AND u.is_active = 1 AND u.id <> ?
           AND (u.reports_to = ? $superExtra)
           AND t.completed_at >= ?
         ORDER BY t.completed_at DESC LIMIT " . (int)$limit,
        [$managerId, $managerId, date('Y-m-d', strtotime('-120 days'))]
    );
}

function rating_stars(int $q): string
{
    return '<span class="stars" title="' . $q . '/5">' . str_repeat('★', $q) . '<span class="text-body-tertiary">' . str_repeat('★', 5 - $q) . '</span></span>';
}

/* ---------- Scores & metrics ---------- */

/** Score 0–100 from rating rows (each with weightage, quality, on_time). */
function score_from_ratings(array $ratings): ?float
{
    $num = 0.0;
    $den = 0.0;
    $lf = late_factor();
    foreach ($ratings as $r) {
        $w = (int)$r['weightage'];
        $num += $w * (int)$r['quality'] * ((int)$r['on_time'] ? 1.0 : $lf);
        $den += $w * 5;
    }
    return $den > 0 ? round($num * 100 / $den, 1) : null;
}

function score_badge(?float $score): string
{
    if ($score === null) {
        return '<span class="text-muted">—</span>';
    }
    $c = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
    return '<span class="badge bg-' . $c . ($c === 'warning' ? ' text-dark' : '') . ' score-badge">' . number_format($score, 1) . '</span>';
}

function working_days_between(string $from, string $to): int
{
    $n = 0;
    $end = min($to, today());
    for ($d = $from; $d <= $end; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
        if (is_working_day($d)) {
            $n++;
        }
    }
    return $n;
}

/** Everything we know about one person for a period. Used by performance, reviews, appraisals, reports. */
function person_metrics(int $userId, string $from, string $to): array
{
    $ratings = rows('SELECT weightage, quality, on_time FROM task_ratings WHERE user_id = ? AND completed_on BETWEEN ? AND ?', [$userId, $from, $to]);
    $tasks = row(
        "SELECT COUNT(*) AS done_n,
                SUM(due_date IS NOT NULL) AS with_due,
                SUM(due_date IS NOT NULL AND DATE(completed_at) <= due_date) AS on_time_n
         FROM tasks WHERE assignee_id = ? AND status = 'done' AND completed_at BETWEEN ? AND ?",
        [$userId, $from . ' 00:00:00', $to . ' 23:59:59']
    );
    $work = row(
        'SELECT COALESCE(SUM(wl.hours), 0) AS hours,
                COALESCE(SUM(CASE WHEN wl.ai_used = 1 THEN wl.ai_minutes ELSE 0 END), 0) AS ai_minutes,
                COALESCE(SUM(CASE WHEN wl.ai_used = 1 THEN wl.time_saved_minutes ELSE 0 END), 0) AS saved_minutes,
                COUNT(wl.id) AS entries, COALESCE(SUM(wl.ai_used), 0) AS ai_entries,
                AVG(CASE WHEN wl.ai_used = 1 THEN wl.output_quality END) AS ai_quality
         FROM work_logs wl JOIN work_days wd ON wd.id = wl.work_day_id
         WHERE wl.user_id = ? AND wd.work_date BETWEEN ? AND ?',
        [$userId, $from, $to]
    );
    $att = row(
        "SELECT SUM(status IN ('open','closed','auto_closed')) AS worked,
                SUM(status = 'missed') AS missed, SUM(status = 'leave') AS leave_days,
                SUM(status = 'auto_closed') AS auto_closed,
                SUM(started_at IS NOT NULL AND TIME(started_at) > ?) AS late
         FROM work_days WHERE user_id = ? AND work_date BETWEEN ? AND ?",
        [time_setting('remind_start_time', '11:00') . ':00', $userId, $from, $to]
    );
    $reviews = row('SELECT COUNT(*) AS n, AVG(rating) AS avg_rating FROM weekly_reviews WHERE user_id = ? AND week_start BETWEEN ? AND ?', [$userId, week_start($from), $to]);
    $rated = count($ratings);
    return [
        'from' => $from, 'to' => $to,
        'tasks_done' => (int)$tasks['done_n'],
        'tasks_rated' => $rated,
        'avg_quality' => $rated ? round(array_sum(array_column($ratings, 'quality')) / $rated, 2) : null,
        'on_time_pct' => (int)$tasks['with_due'] ? round($tasks['on_time_n'] * 100 / $tasks['with_due'], 1) : null,
        'score' => score_from_ratings($ratings),
        'hours' => round((float)$work['hours'], 2),
        'entries' => (int)$work['entries'],
        'ai_minutes' => (int)$work['ai_minutes'],
        'saved_minutes' => (int)$work['saved_minutes'],
        'ai_share_pct' => (int)$work['entries'] ? round($work['ai_entries'] * 100 / $work['entries'], 1) : null,
        'ai_quality' => $work['ai_quality'] !== null ? round((float)$work['ai_quality'], 2) : null,
        // Count working days only from when the person started (account created or first day logged).
        'working_days' => working_days_between(max($from, person_start_date($userId)), $to),
        'days_worked' => (int)$att['worked'],
        'days_missed' => (int)$att['missed'],
        'days_leave' => (int)$att['leave_days'],
        'days_auto_closed' => (int)$att['auto_closed'],
        'late_starts' => (int)$att['late'],
        'reviews' => (int)$reviews['n'],
        'review_avg' => $reviews['avg_rating'] !== null ? round((float)$reviews['avg_rating'], 2) : null,
    ];
}

/** Monthly score and on-time % for the last $months months: [['label','score','on_time','tasks']]. */
function monthly_trend(int $userId, int $months = 12): array
{
    $from = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));
    $byMonth = [];
    foreach (rows('SELECT DATE_FORMAT(completed_on, \'%Y-%m\') AS ym, weightage, quality, on_time FROM task_ratings WHERE user_id = ? AND completed_on >= ?', [$userId, $from]) as $r) {
        $byMonth[$r['ym']][] = $r;
    }
    $out = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-$i months", strtotime(date('Y-m-01'))));
        $list = $byMonth[$ym] ?? [];
        $out[] = [
            'label' => date('M y', strtotime($ym . '-01')),
            'score' => score_from_ratings($list),
            'on_time' => $list ? round(array_sum(array_column($list, 'on_time')) * 100 / count($list), 1) : null,
            'tasks' => count($list),
        ];
    }
    return $out;
}

/** Hours and AI minutes per week for the last $weeks weeks, for a set of users. */
function weekly_work_series(array $userIds, int $weeks = 8): array
{
    $out = [];
    $start = date('Y-m-d', strtotime('monday this week -' . ($weeks - 1) . ' weeks'));
    for ($i = 0; $i < $weeks; $i++) {
        $ws = date('Y-m-d', strtotime($start . " +$i weeks"));
        $out[$ws] = ['label' => fmt_date($ws), 'hours' => 0.0, 'ai_minutes' => 0, 'saved_minutes' => 0];
    }
    if (!$userIds) {
        return array_values($out);
    }
    foreach (rows(
        'SELECT wd.work_date, wl.hours, wl.ai_used, wl.ai_minutes, wl.time_saved_minutes
         FROM work_logs wl JOIN work_days wd ON wd.id = wl.work_day_id
         WHERE wl.user_id IN (' . in_list($userIds) . ') AND wd.work_date >= ?',
        array_merge(array_values($userIds), [$start])
    ) as $r) {
        $ws = week_start($r['work_date']);
        if (!isset($out[$ws])) {
            continue;
        }
        $out[$ws]['hours'] += (float)$r['hours'];
        if ($r['ai_used']) {
            $out[$ws]['ai_minutes'] += (int)$r['ai_minutes'];
            $out[$ws]['saved_minutes'] += (int)$r['time_saved_minutes'];
        }
    }
    return array_values($out);
}

/** People whose performance the current user may see (team below them; everyone for Super Admin / view-all). */
function performance_scope_ids(): array
{
    return team_member_ids();
}

function can_view_performance(int $userId): bool
{
    return in_array($userId, performance_scope_ids(), true);
}

/* ---------- Appraisals ---------- */

function appraisal_statuses(): array
{
    return [
        'draft'     => ['label' => t('Draft'),     'color' => 'secondary'],
        'finalised' => ['label' => t('Finalised'), 'color' => 'info'],
        'published' => ['label' => t('Published'), 'color' => 'success'],
    ];
}

function appraisal_status_badge(string $status): string
{
    return badge(appraisal_statuses(), $status);
}

/** Managers above the person (or Super Admin) can view; the person only once published. */
function can_view_appraisal(array $a): bool
{
    if ((int)$a['user_id'] === uid()) {
        return $a['status'] === 'published';
    }
    return can_view_performance((int)$a['user_id']);
}

function can_edit_appraisal(array $a): bool
{
    return $a['status'] === 'draft' && (int)$a['user_id'] !== uid() && can_view_performance((int)$a['user_id']);
}

function days_late_label(int $n): string
{
    return $n === 1 ? t('1 day late') : t(':n days late', ['n' => $n]);
}

function person_start_date(int $userId): string
{
    $created = substr((string)val('SELECT created_at FROM users WHERE id = ?', [$userId]), 0, 10);
    $first = (string)val('SELECT MIN(work_date) FROM work_days WHERE user_id = ?', [$userId]);
    return ($first && $first < $created) ? $first : ($created ?: today());
}