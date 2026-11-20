<?php
/**
 * Read-only client portal. A client login sees only its own company's projects
 * (where "Show in client portal" is on): progress, status, dates and milestones.
 * Never hours, ratings, comments, files, AI usage or staff details.
 */
declare(strict_types=1);

$u = user();
if ($u['role'] !== ROLE_CLIENT) {
    redirect(url('dashboard')); // staff use the normal app
}
$clientId = (int)$u['client_id'];
$client = $clientId ? row('SELECT id, name FROM clients WHERE id = ? AND is_active = 1', [$clientId]) : null;
if (!$client) {
    abort(403, t('Your portal access is not linked to an active client. Please contact us.'));
}

$visible = "p.client_id = ? AND p.client_visible = 1 AND p.status <> 'cancelled'";

switch ($action) {
    case 'index':
        $projects = rows("SELECT p.id, p.code, p.name, p.status, p.start_date, p.target_date FROM projects p WHERE $visible
                          ORDER BY p.status = 'completed', p.target_date IS NULL, p.target_date", [$clientId]);
        render('portal/index', [
            'pageTitle' => t('My projects'), 'client' => $client, 'projects' => $projects,
            'progress' => project_progress(array_column($projects, 'id')),
        ]);
        break;

    case 'project':
        $project = row("SELECT p.id, p.code, p.name, p.description, p.status, p.start_date, p.target_date FROM projects p WHERE p.id = ? AND $visible",
            [(int)input('id'), $clientId]) ?? abort(404);
        // Milestones = top-level tasks, with progress from their subtasks.
        $milestones = rows(
            "SELECT t.id, t.title, t.status, t.due_date, t.completed_at,
                    (SELECT COUNT(*) FROM tasks s WHERE s.parent_id = t.id) AS subs,
                    (SELECT COUNT(*) FROM tasks s WHERE s.parent_id = t.id AND s.status = 'done') AS subs_done
             FROM tasks t WHERE t.project_id = ? AND t.parent_id IS NULL ORDER BY t.sort_order, t.due_date IS NULL, t.due_date, t.id",
            [$project['id']]
        );
        $recent = rows("SELECT title, completed_at FROM tasks WHERE project_id = ? AND status = 'done' AND completed_at IS NOT NULL ORDER BY completed_at DESC LIMIT 8", [$project['id']]);
        render('portal/project', [
            'pageTitle' => $project['name'], 'client' => $client, 'project' => $project, 'milestones' => $milestones, 'recent' => $recent,
            'progress' => project_progress([(int)$project['id']])[(int)$project['id']],
        ]);
        break;

    default:
        abort(404);
}
