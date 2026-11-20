<?php
/** My Tasks: everything assigned to the signed-in user across projects (list or board). */
declare(strict_types=1);

$view = input('view') === 'board' ? 'board' : 'list';
$tab = (string)input('tab', 'open');
[$scope, $scopeParams] = project_scope('p');
$where = ['t.assignee_id = ?', $scope];
$params = array_merge([uid()], $scopeParams);

if ($view === 'list') {
    switch ($tab) {
        case 'overdue':
            $where[] = "t.status <> 'done' AND t.due_date < ?";
            $params[] = today();
            break;
        case 'week':
            $where[] = "t.status <> 'done' AND t.due_date BETWEEN ? AND ?";
            $params[] = today();
            $params[] = date('Y-m-d', strtotime('+7 days'));
            break;
        case 'done':
            $where[] = "t.status = 'done' AND t.completed_at >= ?";
            $params[] = date('Y-m-d', strtotime('-30 days'));
            break;
        default:
            $tab = 'open';
            $where[] = "t.status <> 'done'";
    }
} else {
    // Board: open work plus anything finished in the last 14 days.
    $where[] = "(t.status <> 'done' OR t.completed_at >= ?)";
    $params[] = date('Y-m-d', strtotime('-14 days'));
}

$tasks = rows(
    'SELECT t.*, p.name AS project_name, pt.title AS parent_title, NULL AS assignee_name,
            (SELECT COUNT(*) FROM tasks s WHERE s.parent_id = t.id) AS sub_count,
            (SELECT COUNT(*) FROM tasks s WHERE s.parent_id = t.id AND s.status = \'done\') AS sub_done,
            (SELECT COUNT(*) FROM task_comments tc WHERE tc.task_id = t.id) AS comment_count,
            (SELECT COUNT(*) FROM attachments a WHERE a.task_id = t.id) AS file_count
     FROM tasks t JOIN projects p ON p.id = t.project_id
     LEFT JOIN tasks pt ON pt.id = t.parent_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY t.due_date IS NULL, t.due_date, FIELD(t.priority, \'urgent\', \'high\', \'medium\', \'low\'), t.id',
    $params
);

$counts = row(
    "SELECT SUM(t.status <> 'done') AS open_n,
            SUM(t.status <> 'done' AND t.due_date < ?) AS overdue_n,
            SUM(t.status <> 'done' AND t.due_date BETWEEN ? AND ?) AS week_n
     FROM tasks t JOIN projects p ON p.id = t.project_id WHERE t.assignee_id = ? AND $scope",
    array_merge([today(), today(), date('Y-m-d', strtotime('+7 days')), uid()], $scopeParams)
);

render('mytasks/index', [
    'pageTitle' => t('My Tasks'),
    'tasks' => $tasks, 'tab' => $tab, 'view' => $view, 'counts' => $counts,
    'extraScripts' => $view === 'board' ? ['vendor/sortable/Sortable.min.js', 'js/kanban.js'] : [],
]);
