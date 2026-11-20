<?php
/**
 * Weekly reviews: each manager reviews their direct reports once a week.
 * The 1–5 rating stays private to managers; comments can be shared with the employee.
 */
declare(strict_types=1);

function can_review(int $userId): bool
{
    $mgr = (int)val('SELECT reports_to FROM users WHERE id = ?', [$userId]);
    return $userId !== uid() && ($mgr === uid() || is_super_admin());
}

function review_week(): string
{
    $d = input_date('week') ?? date('Y-m-d', strtotime('monday last week'));
    // No reviews for weeks that haven't started yet.
    return min(week_start($d), week_start(today()));
}

switch ($action) {
    case 'index':
        $week = review_week();
        $weekEnd = date('Y-m-d', strtotime($week . ' +6 days'));
        $reports = rows("SELECT u.id, u.name, u.designation FROM users u JOIN roles r ON r.id = u.role_id
                         WHERE u.reports_to = ? AND u.is_active = 1 AND r.slug <> 'client' ORDER BY u.name", [uid()]);
        if (!$reports && !is_super_admin()) {
            redirect(url('reviews', ['a' => 'mine']));
        }
        $existing = [];
        if ($reports) {
            $ids = array_column($reports, 'id');
            foreach (rows('SELECT * FROM weekly_reviews WHERE week_start = ? AND user_id IN (' . in_list($ids) . ')', array_merge([$week], $ids)) as $r) {
                $existing[(int)$r['user_id']] = $r;
            }
        }
        foreach ($reports as &$p) {
            $p['m'] = person_metrics((int)$p['id'], $week, $weekEnd);
        }
        unset($p);
        render('reviews/index', ['pageTitle' => t('Weekly reviews'), 'week' => $week, 'weekEnd' => $weekEnd, 'reports' => $reports, 'existing' => $existing]);
        break;

    case 'edit':
        $userId = (int)input('user');
        $person = row('SELECT id, name, designation FROM users WHERE id = ?', [$userId]) ?? abort(404);
        if (!can_review($userId)) {
            abort(403, t('Only the person\'s direct manager can write their weekly review.'));
        }
        $week = review_week();
        $weekEnd = date('Y-m-d', strtotime($week . ' +6 days'));
        $review = row('SELECT * FROM weekly_reviews WHERE user_id = ? AND week_start = ?', [$userId, $week]);
        if (is_post()) {
            $rating = (int)input('rating');
            if ($rating < 1 || $rating > 5) {
                flash('danger', t('Please give an overall rating for the week (1–5).'));
                remember_old();
                redirect(url('reviews', ['a' => 'edit', 'user' => $userId, 'week' => $week]));
            }
            $d = [
                'rating' => $rating,
                'went_well' => trim((string)input('went_well', '')) ?: null,
                'to_improve' => trim((string)input('to_improve', '')) ?: null,
                'next_focus' => trim((string)input('next_focus', '')) ?: null,
                'shared' => input('shared') ? 1 : 0,
                'reviewer_id' => uid(),
            ];
            if ($review) {
                update('weekly_reviews', $d + ['updated_at' => now()], ['id' => $review['id']]);
            } else {
                insert('weekly_reviews', $d + ['user_id' => $userId, 'week_start' => $week, 'created_at' => now()]);
            }
            audit('review', $userId, $person['name'] . ' — ' . $week, 'review');
            flash('success', t('Weekly review saved.'));
            redirect(url('reviews', ['week' => $week]));
        }
        $tasksDone = rows(
            "SELECT t.id, t.title, t.weightage, t.due_date, t.completed_at, p.name AS project_name, r.quality
             FROM tasks t JOIN projects p ON p.id = t.project_id LEFT JOIN task_ratings r ON r.task_id = t.id
             WHERE t.assignee_id = ? AND t.status = 'done' AND t.completed_at BETWEEN ? AND ? ORDER BY t.completed_at",
            [$userId, $week . ' 00:00:00', $weekEnd . ' 23:59:59']
        );
        $blockers = rows(
            "SELECT wd.work_date, wl.blockers FROM work_logs wl JOIN work_days wd ON wd.id = wl.work_day_id
             WHERE wl.user_id = ? AND wd.work_date BETWEEN ? AND ? AND wl.blockers IS NOT NULL AND wl.blockers <> '' ORDER BY wd.work_date",
            [$userId, $week, $weekEnd]
        );
        render('reviews/form', [
            'pageTitle' => t('Weekly review'), 'person' => $person, 'week' => $week, 'weekEnd' => $weekEnd,
            'review' => $review, 'm' => person_metrics($userId, $week, $weekEnd), 'tasksDone' => $tasksDone, 'blockers' => $blockers,
        ]);
        break;

    case 'mine':
        $reviews = rows(
            'SELECT w.week_start, w.went_well, w.to_improve, w.next_focus, u.name AS reviewer_name
             FROM weekly_reviews w LEFT JOIN users u ON u.id = w.reviewer_id
             WHERE w.user_id = ? AND w.shared = 1 ORDER BY w.week_start DESC LIMIT 52',
            [uid()]
        );
        render('reviews/mine', ['pageTitle' => t('My weekly feedback'), 'reviews' => $reviews]);
        break;

    default:
        abort(404);
}
