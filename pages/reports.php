<?php
/**
 * Reports with Excel / CSV / PDF export. Each report only includes the people
 * (your team + you) and projects you are allowed to see.
 */
declare(strict_types=1);

require_perm('reports.view');

$tabs = ['timesheet' => t('Timesheets'), 'ai' => t('AI usage'), 'projects' => t('Project status'), 'performance' => t('Performance'), 'attendance' => t('Attendance')];
$tab = (string)input('tab', 'timesheet');
if (!isset($tabs[$tab])) {
    $tab = 'timesheet';
}
if ($tab === 'performance' && !performance_scope_ids()) {
    $tab = 'timesheet';
}
[$period, $from, $to] = request_period($tab === 'projects' ? 'this_month' : 'last_month');

$people = array_values(array_unique(array_merge(team_member_ids(), [uid()])));
$personId = input_int('person');
if ($personId && !in_array($personId, $people, true)) {
    $personId = null;
}
$deptId = input_int('department');
$peopleRows = rows('SELECT u.id, u.name, u.department_id, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id = u.department_id
                    WHERE u.id IN (' . in_list($people) . ') ORDER BY u.name', $people);
$selected = array_values(array_filter($peopleRows, fn($p) => (!$personId || (int)$p['id'] === $personId) && (!$deptId || (int)$p['department_id'] === $deptId)));
$selIds = array_map(fn($p) => (int)$p['id'], $selected);

$headers = [];
$data = [];       // export rows (plain values)
$numeric = [];    // indexes of numeric columns (right-aligned)
$extra = [];      // extra display blocks
$title = $tabs[$tab];

switch ($tab) {
    case 'timesheet':
        $projectId = input_int('project');
        [$scope, $sp] = project_scope('p');
        $where = ['wd.work_date BETWEEN ? AND ?'];
        $params = [$from, $to];
        $where[] = $selIds ? 'wl.user_id IN (' . in_list($selIds) . ')' : '1=0';
        $params = array_merge($params, $selIds);
        if ($projectId) {
            $where[] = 't.project_id = ?';
            $params[] = $projectId;
        }
        $list = rows(
            'SELECT wd.work_date, u.name AS person, p.name AS project, t.title AS task, wl.hours, wl.ai_used, wl.ai_minutes, wl.time_saved_minutes, wl.description, wl.blockers
             FROM work_logs wl JOIN work_days wd ON wd.id = wl.work_day_id JOIN users u ON u.id = wl.user_id
             LEFT JOIN tasks t ON t.id = wl.task_id LEFT JOIN projects p ON p.id = t.project_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY wd.work_date, u.name, wl.id',
            $params
        );
        $headers = [t('Date'), t('Name'), t('Project'), t('Task'), t('Hours'), t('AI time') . ' (min)', t('Time saved with AI') . ' (min)', t('Description'), t('Blockers')];
        $numeric = [4, 5, 6];
        $totals = [];
        foreach ($list as $r) {
            $data[] = [fmt_date($r['work_date']), $r['person'], $r['project'] ?? t('Other work'), $r['task'] ?? '', (float)$r['hours'],
                $r['ai_used'] ? (int)$r['ai_minutes'] : 0, $r['ai_used'] ? (int)$r['time_saved_minutes'] : 0, (string)$r['description'], (string)$r['blockers']];
            $totals[$r['person']] = ($totals[$r['person']] ?? 0) + (float)$r['hours'];
        }
        arsort($totals);
        $extra['totals'] = $totals;
        $extra['projects'] = rows("SELECT p.id, p.name FROM projects p WHERE $scope ORDER BY p.name", $sp);
        $extra['projectId'] = $projectId;
        break;

    case 'ai':
        $headers = [t('Name'), t('Entries'), t('Entries with AI'), t('AI share') . ' %', t('AI time') . ' (min)', t('Time saved with AI') . ' (min)', t('Avg AI quality'), t('Tools')];
        $numeric = [1, 2, 3, 4, 5, 6];
        foreach ($selected as $p) {
            $r = row('SELECT COUNT(*) AS n, COALESCE(SUM(wl.ai_used), 0) AS ai_n,
                             COALESCE(SUM(CASE WHEN wl.ai_used = 1 THEN wl.ai_minutes ELSE 0 END), 0) AS ai_min,
                             COALESCE(SUM(CASE WHEN wl.ai_used = 1 THEN wl.time_saved_minutes ELSE 0 END), 0) AS saved,
                             AVG(CASE WHEN wl.ai_used = 1 THEN wl.output_quality END) AS q
                      FROM work_logs wl JOIN work_days wd ON wd.id = wl.work_day_id WHERE wl.user_id = ? AND wd.work_date BETWEEN ? AND ?',
                [$p['id'], $from, $to]);
            if (!(int)$r['n']) {
                continue;
            }
            $tools = q('SELECT a.name FROM work_log_ai_tools x JOIN ai_tools a ON a.id = x.ai_tool_id JOIN work_logs wl ON wl.id = x.work_log_id
                        JOIN work_days wd ON wd.id = wl.work_day_id WHERE wl.user_id = ? AND wd.work_date BETWEEN ? AND ?
                        GROUP BY a.name ORDER BY COUNT(*) DESC', [$p['id'], $from, $to])->fetchAll(PDO::FETCH_COLUMN);
            $data[] = [$p['name'], (int)$r['n'], (int)$r['ai_n'], round($r['ai_n'] * 100 / $r['n'], 1), (int)$r['ai_min'], (int)$r['saved'],
                $r['q'] !== null ? round((float)$r['q'], 2) : '', implode(', ', $tools)];
        }
        if ($selIds) {
            $extra['byTool'] = rows('SELECT a.name, COUNT(*) AS uses, SUM(wl.ai_minutes) AS minutes, SUM(wl.time_saved_minutes) AS saved, AVG(wl.output_quality) AS q
                FROM work_log_ai_tools x JOIN ai_tools a ON a.id = x.ai_tool_id JOIN work_logs wl ON wl.id = x.work_log_id JOIN work_days wd ON wd.id = wl.work_day_id
                WHERE wl.user_id IN (' . in_list($selIds) . ') AND wd.work_date BETWEEN ? AND ? GROUP BY a.name ORDER BY uses DESC', array_merge($selIds, [$from, $to]));
            $extra['byPurpose'] = rows('SELECT wl.ai_purpose, COUNT(*) AS uses, SUM(wl.ai_minutes) AS minutes, SUM(wl.time_saved_minutes) AS saved
                FROM work_logs wl JOIN work_days wd ON wd.id = wl.work_day_id
                WHERE wl.ai_used = 1 AND wl.user_id IN (' . in_list($selIds) . ') AND wd.work_date BETWEEN ? AND ? GROUP BY wl.ai_purpose ORDER BY uses DESC', array_merge($selIds, [$from, $to]));
        }
        break;

    case 'projects':
        $status = (string)input('status', 'open');
        [$scope, $sp] = project_scope('p');
        $w = [$scope];
        if ($status === 'open') {
            $w[] = "p.status IN ('planning','active','on_hold')";
        }
        $projects = rows('SELECT p.*, c.name AS client_name, m.name AS manager_name FROM projects p LEFT JOIN clients c ON c.id = p.client_id
                          LEFT JOIN users m ON m.id = p.manager_id WHERE ' . implode(' AND ', $w) . ' ORDER BY p.name', $sp);
        $health = project_health($projects);
        $hours = [];
        if ($projects) {
            $ids = array_column($projects, 'id');
            foreach (rows('SELECT t.project_id, SUM(wl.hours) AS h FROM work_logs wl JOIN tasks t ON t.id = wl.task_id WHERE t.project_id IN (' . in_list($ids) . ') GROUP BY t.project_id', $ids) as $r) {
                $hours[(int)$r['project_id']] = (float)$r['h'];
            }
            $est = [];
            foreach (rows('SELECT project_id, SUM(estimated_hours) AS e FROM tasks t WHERE project_id IN (' . in_list($ids) . ') AND NOT EXISTS (SELECT 1 FROM tasks c WHERE c.parent_id = t.id) GROUP BY project_id', $ids) as $r) {
                $est[(int)$r['project_id']] = (float)$r['e'];
            }
        }
        $ragLabel = fn($rag) => ['green' => t('On track'), 'amber' => t('At risk'), 'red' => t('Off track'), 'grey' => t('No data'), 'done' => t('Complete')][$rag] ?? $rag;
        $headers = [t('Project'), t('Code'), t('Client'), t('Project manager'), t('Status'), t('Progress') . ' %', t('Health'), t('Reason'), t('Start date'), t('Target date'), t('Forecast'), t('Tasks'), t('Done'), t('Overdue'), t('Est. hrs'), t('Hours logged')];
        $numeric = [5, 11, 12, 13, 14, 15];
        foreach ($projects as $p) {
            $h = $health[(int)$p['id']];
            $data[] = [$p['name'], (string)$p['code'], $p['client_name'] ?? t('Internal'), (string)$p['manager_name'], project_statuses()[$p['status']]['label'] ?? $p['status'],
                $h['pct'], $ragLabel($h['rag']), $h['reason'], fmt_date($p['start_date']), fmt_date($p['target_date']), $h['forecast'] ? fmt_date($h['forecast']) : '',
                $h['total'], $h['done'], $h['overdue'], round($est[(int)$p['id']] ?? 0, 1), round($hours[(int)$p['id']] ?? 0, 1)];
        }
        $extra['status'] = $status;
        $extra['rag'] = array_map(fn($p) => $health[(int)$p['id']]['rag'], $projects);
        break;

    case 'performance':
        $scopeIds = performance_scope_ids();
        $headers = [t('Name'), t('Department'), t('Score'), t('Rated tasks'), t('Tasks done'), t('Avg quality'), t('On time') . ' %', t('Hours'), t('AI time') . ' (min)', t('Time saved with AI') . ' (min)', t('Days worked'), t('Working days'), t('Late start'), t('Weekly review')];
        $numeric = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];
        foreach ($selected as $p) {
            if (!in_array((int)$p['id'], $scopeIds, true)) {
                continue; // never your own score
            }
            $m = person_metrics((int)$p['id'], $from, $to);
            $data[] = [$p['name'], (string)$p['department_name'], $m['score'] ?? '', $m['tasks_rated'], $m['tasks_done'], $m['avg_quality'] ?? '', $m['on_time_pct'] ?? '',
                $m['hours'], $m['ai_minutes'], $m['saved_minutes'], $m['days_worked'], $m['working_days'], $m['late_starts'], $m['review_avg'] ?? ''];
        }
        break;

    case 'attendance':
        $headers = [t('Name'), t('Working days'), t('Days worked'), t('Late start'), t('Not started'), t('On leave'), t('Auto-closed'), t('Avg start time'), t('Avg close time'), t('Hours')];
        $numeric = [1, 2, 3, 4, 5, 6, 9];
        foreach ($selected as $p) {
            $m = person_metrics((int)$p['id'], $from, $to);
            $avg = row("SELECT SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(started_at)))) AS s, SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(closed_at)))) AS c
                        FROM work_days WHERE user_id = ? AND work_date BETWEEN ? AND ? AND status = 'closed'", [$p['id'], $from, $to]);
            $data[] = [$p['name'], $m['working_days'], $m['days_worked'], $m['late_starts'], $m['days_missed'], $m['days_leave'], $m['days_auto_closed'],
                $avg['s'] ? date('h:i A', strtotime(substr($avg['s'], 0, 8))) : '', $avg['c'] ? date('h:i A', strtotime(substr($avg['c'], 0, 8))) : '', $m['hours']];
        }
        break;
}

$subtitle = ($tab === 'projects' ? '' : fmt_date($from) . ' – ' . fmt_date($to)) . ($personId ? ' · ' . user_name($personId) : '');
maybe_export('report-' . $tab, $title, $subtitle, $headers, $data, $numeric);

render('reports/index', [
    'pageTitle' => t('Reports'), 'tabs' => $tabs, 'tab' => $tab, 'period' => $period, 'from' => $from, 'to' => $to,
    'headers' => $headers, 'data' => $data, 'numeric' => $numeric, 'extra' => $extra,
    'peopleRows' => $peopleRows, 'personId' => $personId, 'deptId' => $deptId,
    'departments' => rows('SELECT DISTINCT d.id, d.name FROM departments d JOIN users u ON u.department_id = d.id WHERE u.id IN (' . in_list($people) . ') ORDER BY d.name', $people),
]);
