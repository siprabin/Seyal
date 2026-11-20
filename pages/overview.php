<?php
/**
 * Management dashboard: project health (🟢🟡🔴, target vs forecast), today's attendance,
 * overdue tasks and weekly AI usage — limited to the projects and people the viewer can see.
 */
declare(strict_types=1);

$teamIds = team_member_ids();
if (!$teamIds && !can('projects.view_all') && !can('reports.view')) {
    redirect(url('dashboard'));
}

[$scope, $sp] = project_scope('p');
$projects = rows(
    "SELECT p.*, c.name AS client_name, m.name AS manager_name
     FROM projects p LEFT JOIN clients c ON c.id = p.client_id LEFT JOIN users m ON m.id = p.manager_id
     WHERE $scope AND p.status IN ('planning','active','on_hold')
     ORDER BY p.target_date IS NULL, p.target_date, p.name",
    $sp
);
$health = project_health($projects);
// Worst first: red, amber, green, grey, done.
$order = ['red' => 0, 'amber' => 1, 'green' => 2, 'grey' => 3, 'done' => 4];
usort($projects, fn($a, $b) => [$order[$health[(int)$a['id']]['rag']], $a['target_date'] ?? '9999'] <=> [$order[$health[(int)$b['id']]['rag']], $b['target_date'] ?? '9999']);
$ragCounts = array_count_values(array_column($health, 'rag'));

$overdue = rows(
    "SELECT t.id, t.title, t.due_date, t.priority, u.name AS assignee_name, p.name AS project_name, p.id AS project_id
     FROM tasks t JOIN projects p ON p.id = t.project_id LEFT JOIN users u ON u.id = t.assignee_id
     WHERE $scope AND t.status <> 'done' AND t.due_date < ? AND p.status IN ('planning','active','on_hold')
     ORDER BY t.due_date LIMIT 15",
    array_merge($sp, [today()])
);
$overdueTotal = (int)val(
    "SELECT COUNT(*) FROM tasks t JOIN projects p ON p.id = t.project_id
     WHERE $scope AND t.status <> 'done' AND t.due_date < ? AND p.status IN ('planning','active','on_hold')",
    array_merge($sp, [today()])
);

// Attendance today: people expected to log their day, within my team (plus me).
$people = array_values(array_unique(array_merge($teamIds, [uid()])));
$required = array_values(array_intersect($people, array_map('intval', array_column(daylog_required_users(), 'id'))));
$attendance = team_day_rows($required, today());

// AI usage: last 8 weeks for the same people; tools and top users this week.
$weeks = weekly_work_series($people, 8);
$wkStart = week_start(today());
$tools = $people ? rows(
    'SELECT a.name, COUNT(*) AS uses, SUM(wl.ai_minutes) AS minutes
     FROM work_log_ai_tools x JOIN ai_tools a ON a.id = x.ai_tool_id
     JOIN work_logs wl ON wl.id = x.work_log_id JOIN work_days wd ON wd.id = wl.work_day_id
     WHERE wl.user_id IN (' . in_list($people) . ') AND wd.work_date >= ?
     GROUP BY a.id, a.name ORDER BY uses DESC',
    array_merge($people, [$wkStart])
) : [];
$topAi = $people ? rows(
    'SELECT u.id, u.name, SUM(wl.ai_minutes) AS ai_minutes, SUM(wl.time_saved_minutes) AS saved, COUNT(*) AS entries
     FROM work_logs wl JOIN work_days wd ON wd.id = wl.work_day_id JOIN users u ON u.id = wl.user_id
     WHERE wl.ai_used = 1 AND wl.user_id IN (' . in_list($people) . ') AND wd.work_date >= ?
     GROUP BY u.id, u.name ORDER BY saved DESC LIMIT 5',
    array_merge($people, [$wkStart])
) : [];
$thisWeek = end($weeks);

render('overview/index', [
    'pageTitle' => t('Management dashboard'),
    'projects' => $projects, 'health' => $health, 'ragCounts' => $ragCounts,
    'overdue' => $overdue, 'overdueTotal' => $overdueTotal,
    'attendance' => $attendance, 'weeks' => $weeks, 'tools' => $tools, 'topAi' => $topAi, 'thisWeek' => $thisWeek,
    'extraScripts' => ['vendor/chartjs/chart.umd.min.js', 'js/charts.js'],
]);
