<?php
/**
 * Performance. Managers see live scores for their team; everyone sees their own
 * score only through published appraisals.
 */
declare(strict_types=1);

$scope = performance_scope_ids();

switch ($action) {
    case 'index':
        if (!$scope) {
            redirect(url('performance', ['a' => 'mine']));
        }
        [$period, $from, $to] = request_period('last_3m');
        $deptId = input_int('department');
        $where = ['u.id IN (' . in_list($scope) . ')', 'u.is_active = 1', "r.slug <> 'client'"];
        $params = $scope;
        if ($deptId) {
            $where[] = 'u.department_id = ?';
            $params[] = $deptId;
        }
        $people = rows('SELECT u.id, u.name, u.designation, d.name AS department_name FROM users u JOIN roles r ON r.id = u.role_id
                        LEFT JOIN departments d ON d.id = u.department_id WHERE ' . implode(' AND ', $where) . ' ORDER BY u.name', $params);
        // Previous period of the same length, for the trend arrow.
        $len = (int)round((strtotime($to) - strtotime($from)) / 86400) + 1;
        $pFrom = date('Y-m-d', strtotime($from . " -$len days"));
        $pTo = date('Y-m-d', strtotime($from . ' -1 day'));
        foreach ($people as &$p) {
            $p['m'] = person_metrics((int)$p['id'], $from, $to);
            $p['prev'] = person_metrics((int)$p['id'], $pFrom, $pTo)['score'];
        }
        unset($p);
        $sort = (string)input('sort', 'score');
        usort($people, fn($a, $b) => match ($sort) {
            'name' => strcmp($a['name'], $b['name']),
            'hours' => $b['m']['hours'] <=> $a['m']['hours'],
            default => ($b['m']['score'] ?? -1) <=> ($a['m']['score'] ?? -1),
        });
        $deptIds = array_unique(array_filter(array_map('intval', q('SELECT department_id FROM users WHERE id IN (' . in_list($scope) . ')', $scope)->fetchAll(PDO::FETCH_COLUMN))));
        render('performance/index', [
            'pageTitle' => t('Performance'), 'people' => $people, 'period' => $period, 'from' => $from, 'to' => $to,
            'deptId' => $deptId, 'sort' => $sort,
            'departments' => $deptIds ? rows('SELECT id, name FROM departments WHERE id IN (' . in_list($deptIds) . ') ORDER BY name', array_values($deptIds)) : [],
        ]);
        break;

    case 'person':
        $userId = (int)input('id');
        if ($userId === uid()) {
            redirect(url('performance', ['a' => 'mine']));
        }
        if (!can_view_performance($userId)) {
            abort(403);
        }
        $person = row('SELECT u.*, d.name AS department_name, m.name AS manager_name FROM users u
                       LEFT JOIN departments d ON d.id = u.department_id LEFT JOIN users m ON m.id = u.reports_to WHERE u.id = ?', [$userId]) ?? abort(404);
        [$period, $from, $to] = request_period('last_3m');
        $ratings = rows(
            'SELECT r.*, t.title, p.name AS project_name, u.name AS rater_name FROM task_ratings r
             JOIN tasks t ON t.id = r.task_id JOIN projects p ON p.id = t.project_id LEFT JOIN users u ON u.id = r.rated_by
             WHERE r.user_id = ? AND r.completed_on BETWEEN ? AND ? ORDER BY r.completed_on DESC',
            [$userId, $from, $to]
        );
        $reviews = rows('SELECT w.*, u.name AS reviewer_name FROM weekly_reviews w LEFT JOIN users u ON u.id = w.reviewer_id
                         WHERE w.user_id = ? ORDER BY w.week_start DESC LIMIT 12', [$userId]);
        $appraisals = rows('SELECT * FROM appraisals WHERE user_id = ? ORDER BY period_to DESC', [$userId]);
        render('performance/person', [
            'pageTitle' => $person['name'], 'person' => $person, 'period' => $period, 'from' => $from, 'to' => $to,
            'm' => person_metrics($userId, $from, $to), 'trend' => monthly_trend($userId, 12),
            'weeks' => weekly_work_series([$userId], 12), 'ratings' => $ratings, 'reviews' => $reviews, 'appraisals' => $appraisals,
            'extraScripts' => ['vendor/chartjs/chart.umd.min.js', 'js/charts.js'],
        ]);
        break;

    case 'mine':
        // Own view: work figures and published appraisals only (no live score).
        [$period, $from, $to] = request_period('this_month');
        $published = rows("SELECT a.*, u.name AS reviewer_name FROM appraisals a LEFT JOIN users u ON u.id = a.reviewer_id
                           WHERE a.user_id = ? AND a.status = 'published' ORDER BY a.period_to DESC", [uid()]);
        $sharedReviews = (int)val('SELECT COUNT(*) FROM weekly_reviews WHERE user_id = ? AND shared = 1', [uid()]);
        render('performance/mine', [
            'pageTitle' => t('My performance'), 'period' => $period, 'from' => $from, 'to' => $to,
            'm' => person_metrics(uid(), $from, $to), 'published' => $published, 'sharedReviews' => $sharedReviews,
            'weeks' => weekly_work_series([uid()], 12),
            'extraScripts' => ['vendor/chartjs/chart.umd.min.js', 'js/charts.js'],
        ]);
        break;

    default:
        abort(404);
}
