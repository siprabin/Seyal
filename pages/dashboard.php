<?php
/** Phase 1 home page. The full management dashboard arrives in Phase 3. */
declare(strict_types=1);

[$scope, $sp] = project_scope('p');
$week = date('Y-m-d', strtotime('+7 days'));

$mine = row(
    "SELECT SUM(t.status <> 'done') AS open_n,
            SUM(t.status <> 'done' AND t.due_date < ?) AS overdue_n,
            SUM(t.status <> 'done' AND t.due_date BETWEEN ? AND ?) AS week_n,
            SUM(t.status = 'done' AND t.completed_at >= ?) AS done_week
     FROM tasks t WHERE t.assignee_id = ?",
    [today(), today(), $week, date('Y-m-d', strtotime('-7 days')), uid()]
);

$myTasks = rows(
    "SELECT t.id, t.title, t.status, t.priority, t.due_date, p.name AS project_name
     FROM tasks t JOIN projects p ON p.id = t.project_id
     WHERE t.assignee_id = ? AND t.status <> 'done'
     ORDER BY t.due_date IS NULL, t.due_date, FIELD(t.priority, 'urgent', 'high', 'medium', 'low') LIMIT 8",
    [uid()]
);

$projects = rows(
    "SELECT p.id, p.name, p.code, p.status, p.target_date, m.name AS manager_name
     FROM projects p LEFT JOIN users m ON m.id = p.manager_id
     WHERE $scope AND p.status IN ('planning', 'active', 'on_hold')
     ORDER BY p.target_date IS NULL, p.target_date LIMIT 8",
    $sp
);
$progress = project_progress(array_column($projects, 'id'));
$activeCount = (int)val("SELECT COUNT(*) FROM projects p WHERE $scope AND p.status IN ('planning', 'active', 'on_hold')", $sp);

// Overdue work across projects this user manages (or oversees).
$teamOverdue = [];
if (!is_role(ROLE_MEMBER)) {
    $teamOverdue = rows(
        "SELECT t.id, t.title, t.due_date, u.name AS assignee_name, p.name AS project_name
         FROM tasks t JOIN projects p ON p.id = t.project_id LEFT JOIN users u ON u.id = t.assignee_id
         WHERE $scope AND t.status <> 'done' AND t.due_date < ? AND (t.assignee_id IS NULL OR t.assignee_id <> ?)
           AND (p.manager_id = ? OR ? = 1)
         ORDER BY t.due_date LIMIT 10",
        array_merge($sp, [today(), uid(), uid(), (can('projects.manage_all') || is_super_admin()) ? 1 : 0])
    );
}

$myDay = get_work_day(uid(), today());
$myDayTotals = $myDay && $myDay['status'] !== 'missed' ? day_totals(day_entries((int)$myDay['id'])) : null;
$teamToday = null;
if ($teamIds = team_member_ids()) {
    $required = array_values(array_intersect($teamIds, array_map('intval', array_column(daylog_required_users(), 'id'))));
    $teamRows = team_day_rows($required, today());
    $teamToday = [
        'total'   => count($teamRows),
        'working' => count(array_filter($teamRows, fn($r) => $r['status'] === 'open')),
        'closed'  => count(array_filter($teamRows, fn($r) => in_array($r['status'], ['closed', 'auto_closed'], true))),
        'none'    => count(array_filter($teamRows, fn($r) => !$r['status'] || $r['status'] === 'missed')),
        'leave'   => count(array_filter($teamRows, fn($r) => $r['status'] === 'leave')),
    ];
}

render('dashboard/index', [
    'myDay' => $myDay, 'myDayTotals' => $myDayTotals, 'teamToday' => $teamToday,
    'pageTitle' => t('Dashboard'),
    'mine' => $mine, 'myTasks' => $myTasks, 'projects' => $projects, 'progress' => $progress,
    'activeCount' => $activeCount, 'teamOverdue' => $teamOverdue,
]);
