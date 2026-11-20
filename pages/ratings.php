<?php
/** Task ratings by the direct manager: queue of finished tasks to rate, and recent ratings. */
declare(strict_types=1);

if (is_post() && $action === 'save') {
    [$task, $project] = find_task_or_404((int)input('task'));
    $err = save_task_rating($task, (int)input('quality'), (string)input('comment', ''));
    if (is_ajax()) {
        $err ? json_out(['ok' => false, 'error' => $err], 422) : json_out(['ok' => true]);
    }
    $err ? flash('danger', $err) : flash('success', t('Rating saved.'));
    back(url('ratings'));
}

$tab = input('tab') === 'rated' ? 'rated' : 'todo';
$todo = tasks_to_rate(uid());
$rated = [];
if ($tab === 'rated') {
    $rated = rows(
        'SELECT r.*, t.title, t.due_date, t.completed_at, u.name AS assignee_name, p.name AS project_name
         FROM task_ratings r JOIN tasks t ON t.id = r.task_id JOIN users u ON u.id = r.user_id JOIN projects p ON p.id = t.project_id
         WHERE r.rated_by = ? ORDER BY r.updated_at DESC, r.created_at DESC LIMIT 100',
        [uid()]
    );
}
$hasReports = (bool)val('SELECT COUNT(*) FROM users WHERE reports_to = ? AND is_active = 1', [uid()]) || is_super_admin();
if (!$hasReports) {
    abort(403, t('Nobody reports to you, so there is nothing to rate.'));
}
render('ratings/index', ['pageTitle' => t('Task ratings'), 'tab' => $tab, 'todo' => $todo, 'rated' => $rated]);
