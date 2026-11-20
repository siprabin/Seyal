<?php
/**
 * Who can see / change which project or task, plus shared domain lists
 * (statuses, priorities) and small query helpers used across pages.
 */
declare(strict_types=1);

/* ---------- Domain lists ---------- */

function task_statuses(): array
{
    return [
        'todo'        => ['label' => t('To Do'),       'color' => 'secondary'],
        'in_progress' => ['label' => t('In Progress'), 'color' => 'primary'],
        'review'      => ['label' => t('In Review'),   'color' => 'info'],
        'blocked'     => ['label' => t('Blocked'),     'color' => 'danger'],
        'done'        => ['label' => t('Done'),        'color' => 'success'],
    ];
}

function priorities(): array
{
    return [
        'low'    => ['label' => t('Low'),    'color' => 'secondary'],
        'medium' => ['label' => t('Medium'), 'color' => 'info'],
        'high'   => ['label' => t('High'),   'color' => 'warning'],
        'urgent' => ['label' => t('Urgent'), 'color' => 'danger'],
    ];
}

function project_statuses(): array
{
    return [
        'planning'  => ['label' => t('Planning'),  'color' => 'secondary'],
        'active'    => ['label' => t('Active'),    'color' => 'primary'],
        'on_hold'   => ['label' => t('On Hold'),   'color' => 'warning'],
        'completed' => ['label' => t('Completed'), 'color' => 'success'],
        'cancelled' => ['label' => t('Cancelled'), 'color' => 'dark'],
    ];
}

function badge(array $list, ?string $key): string
{
    if (!$key || !isset($list[$key])) {
        return '';
    }
    $c = $list[$key]['color'];
    $text = in_array($c, ['warning', 'info', 'light'], true) ? ' text-dark' : '';
    return '<span class="badge bg-' . $c . $text . '">' . e($list[$key]['label']) . '</span>';
}

function weight_stars(int $w): string
{
    $w = max(1, min(5, $w));
    return '<span class="weight" title="' . e(t('Weightage :n of 5', ['n' => $w])) . '">'
        . str_repeat('<i class="bi bi-circle-fill"></i>', $w)
        . str_repeat('<i class="bi bi-circle"></i>', 5 - $w) . '</span>';
}

/* ---------- Project visibility ---------- */

/**
 * SQL condition limiting projects (alias $a) to those the current user may see.
 * Returns [sql, params].
 */
function project_scope(string $a = 'p'): array
{
    $u = user();
    if (!$u) {
        return ['1=0', []];
    }
    if (can('projects.view_all')) {
        return ['1=1', []];
    }
    $id = (int)$u['id'];
    $conds = [
        "$a.manager_id = ?",
        "$a.created_by = ?",
        "EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id = $a.id AND pm.user_id = ?)",
        "EXISTS (SELECT 1 FROM tasks tt WHERE tt.project_id = $a.id AND tt.assignee_id = ?)",
        "EXISTS (SELECT 1 FROM departments dd WHERE dd.id = $a.department_id AND dd.head_user_id = ?)",
    ];
    $params = [$id, $id, $id, $id, $id];
    if (can('projects.view_department') && $u['department_id']) {
        $conds[] = "$a.department_id = ?";
        $params[] = (int)$u['department_id'];
    }
    return ['(' . implode(' OR ', $conds) . ')', $params];
}

function can_view_project(array $project): bool
{
    static $cache = [];
    $pid = (int)$project['id'];
    if (!isset($cache[$pid])) {
        [$scope, $params] = project_scope('p');
        $cache[$pid] = (bool)val("SELECT COUNT(*) FROM projects p WHERE p.id = ? AND $scope", array_merge([$pid], $params));
    }
    return $cache[$pid];
}

/** Full control: edit project, add/edit/delete any task in it. */
function can_manage_project(array $project): bool
{
    if (is_super_admin()) {
        return true;
    }
    if ((int)$project['manager_id'] === uid()) {
        return true;
    }
    $head = $project['department_id'] ? (int)val('SELECT head_user_id FROM departments WHERE id = ?', [$project['department_id']]) : 0;
    if ($head === uid()) {
        return true;
    }
    return can('projects.manage_all') && can_view_project($project);
}

/** Assignees may move their own task through statuses; managers may move any. */
function can_update_task_status(array $task, array $project): bool
{
    return can_manage_project($project) || (int)$task['assignee_id'] === uid();
}

/**
 * Team members of a project may add tasks for themselves when the project allows it
 * (project setting "Team members can add their own tasks"). Such tasks are always assigned to the creator.
 */
function can_add_own_task(array $project): bool
{
    if (empty($project['member_tasks']) || (user()['role'] ?? '') === ROLE_CLIENT) {
        return false;
    }
    return (bool)val('SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ?', [$project['id'], uid()]);
}

/** Who sees "Add task" on a project: its managers, and team members when the project allows own tasks. */
function can_create_task(array $project): bool
{
    return can_manage_project($project) || can_add_own_task($project);
}

/** Only people who manage the project may choose or change who a task is assigned to (and its weightage). */
function can_assign_tasks(array $project): bool
{
    return can_manage_project($project);
}

/**
 * Managers edit any task. Others may edit subtasks they created, and their own self-assigned tasks
 * while the project allows team members to add their own tasks.
 */
function can_edit_task(array $task, array $project): bool
{
    if (can_manage_project($project)) {
        return true;
    }
    if ((int)$task['created_by'] !== uid()) {
        return false;
    }
    return $task['parent_id']
        || (!empty($project['member_tasks']) && (int)$task['assignee_id'] === uid());
}

/** Assignees can break their own task into subtasks. */
function can_add_subtask(array $parent, array $project): bool
{
    return can_manage_project($project) || (int)$parent['assignee_id'] === uid();
}

/** Apply a status change with completed_at bookkeeping and audit. */
function change_task_status(array $task, string $status): void
{
    if ($task['status'] === $status) {
        return;
    }
    update('tasks', [
        'status'       => $status,
        'completed_at' => $status === 'done' ? now() : null,
        'updated_at'   => now(),
    ], ['id' => $task['id']]);
    audit_diff('task', (int)$task['id'], $task['title'], ['status' => $task['status']], ['status' => $status]);
    if ($task['status'] === 'done') {
        clear_rating_on_reopen((int)$task['id']);
    }
}

function find_project_or_404(int $id): array
{
    $p = row('SELECT * FROM projects WHERE id = ?', [$id]);
    if (!$p) {
        abort(404);
    }
    if (!can_view_project($p)) {
        abort(403);
    }
    return $p;
}

/** Returns [task, project] after checking visibility. */
function find_task_or_404(int $id): array
{
    $t = row('SELECT * FROM tasks WHERE id = ?', [$id]);
    if (!$t) {
        abort(404);
    }
    return [$t, find_project_or_404((int)$t['project_id'])];
}

function ensure_project_member(int $projectId, ?int $userId): void
{
    if ($userId) {
        q('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)', [$projectId, $userId]);
    }
}

/* ---------- Progress ---------- */

/**
 * Progress per project, weighted by task weightage. Only "leaf" tasks count
 * (a parent task's subtasks count instead of the parent itself).
 * Returns [project_id => [pct, total, done, overdue]].
 */
function project_progress(array $projectIds): array
{
    $out = [];
    if (!$projectIds) {
        return $out;
    }
    $sql = 'SELECT t.project_id,
                   SUM(t.weightage) AS w,
                   SUM(CASE WHEN t.status = \'done\' THEN t.weightage ELSE 0 END) AS wd,
                   COUNT(*) AS n,
                   SUM(t.status = \'done\') AS nd,
                   SUM(t.status <> \'done\' AND t.due_date < ?) AS overdue
            FROM tasks t
            WHERE t.project_id IN (' . in_list($projectIds) . ')
              AND NOT EXISTS (SELECT 1 FROM tasks c WHERE c.parent_id = t.id)
            GROUP BY t.project_id';
    foreach (rows($sql, array_merge([today()], array_values($projectIds))) as $r) {
        $out[(int)$r['project_id']] = [
            'pct'     => $r['w'] > 0 ? (int)round($r['wd'] * 100 / $r['w']) : 0,
            'total'   => (int)$r['n'],
            'done'    => (int)$r['nd'],
            'overdue' => (int)$r['overdue'],
        ];
    }
    foreach ($projectIds as $id) {
        $out[(int)$id] ??= ['pct' => 0, 'total' => 0, 'done' => 0, 'overdue' => 0];
    }
    return $out;
}

function progress_bar(int $pct, string $extra = ''): string
{
    $c = $pct >= 100 ? 'bg-success' : '';
    return '<div class="progress ' . $extra . '" role="progressbar" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100" title="' . $pct . '%">'
        . '<div class="progress-bar ' . $c . '" style="width:' . $pct . '%"></div></div>';
}

/* ---------- Lookups for dropdowns ---------- */

function active_users(): array
{
    return rows("SELECT u.id, u.name, u.designation, d.name AS department_name
                 FROM users u JOIN roles r ON r.id = u.role_id
                 LEFT JOIN departments d ON d.id = u.department_id
                 WHERE u.is_active = 1 AND r.slug <> 'client' ORDER BY u.name");
}

function all_departments(): array
{
    return rows('SELECT id, name FROM departments ORDER BY name');
}

function all_clients(bool $activeOnly = true): array
{
    return rows('SELECT id, name FROM clients' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY name');
}

function user_name(?int $id): string
{
    if (!$id) {
        return '';
    }
    static $cache = [];
    return $cache[$id] ??= (string)val('SELECT name FROM users WHERE id = ?', [$id]);
}
