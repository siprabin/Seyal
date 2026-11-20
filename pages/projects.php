<?php
/** Projects: list, create, edit, view (task list), Kanban board, members, history, delete. */
declare(strict_types=1);

/** Display-friendly snapshot of a project, used for the audit trail. */
function describe_project(array $p): array
{
    return [
        'code'        => $p['code'],
        'name'        => $p['name'],
        'description' => $p['description'],
        'client'      => $p['client_id'] ? (string)val('SELECT name FROM clients WHERE id = ?', [$p['client_id']]) : 'Internal',
        'department'  => $p['department_id'] ? (string)val('SELECT name FROM departments WHERE id = ?', [$p['department_id']]) : '',
        'manager'     => user_name($p['manager_id'] ? (int)$p['manager_id'] : null),
        'status'      => $p['status'],
        'priority'    => $p['priority'],
        'start_date'  => $p['start_date'],
        'target_date' => $p['target_date'],
    ];
}

function project_form_data(): array
{
    return [
        'code'          => mb_substr((string)input('code', ''), 0, 30) ?: null,
        'name'          => mb_substr((string)input('name', ''), 0, 200),
        'description'   => (string)input('description', '') ?: null,
        'client_id'     => input_int('client_id') ?: null,
        'client_visible' => input('client_visible') ? 1 : 0,
        'department_id' => input_int('department_id') ?: null,
        'manager_id'    => input_int('manager_id') ?: null,
        'status'        => array_key_exists((string)input('status'), project_statuses()) ? input('status') : 'planning',
        'priority'      => array_key_exists((string)input('priority'), priorities()) ? input('priority') : 'medium',
        'start_date'    => input_date('start_date'),
        'target_date'   => input_date('target_date'),
    ];
}

function validate_project(array $d, ?int $id = null): array
{
    $errors = [];
    if ($d['name'] === '') {
        $errors[] = t('Project name is required.');
    }
    if ($d['code'] && val('SELECT COUNT(*) FROM projects WHERE code = ? AND id <> ?', [$d['code'], $id ?? 0])) {
        $errors[] = t('Another project already uses this code.');
    }
    if ($d['start_date'] && $d['target_date'] && $d['target_date'] < $d['start_date']) {
        $errors[] = t('Target date cannot be before the start date.');
    }
    return $errors;
}

function save_members(int $projectId, array $memberIds, ?int $managerId): void
{
    $memberIds = array_unique(array_filter(array_map('intval', $memberIds)));
    if ($managerId) {
        $memberIds[] = $managerId;
    }
    // Never drop people who still have tasks in the project.
    $assigned = q('SELECT DISTINCT assignee_id FROM tasks WHERE project_id = ? AND assignee_id IS NOT NULL', [$projectId])->fetchAll(PDO::FETCH_COLUMN);
    $memberIds = array_unique(array_merge($memberIds, array_map('intval', $assigned)));
    q('DELETE FROM project_members WHERE project_id = ?' . ($memberIds ? ' AND user_id NOT IN (' . in_list($memberIds) . ')' : ''), array_merge([$projectId], $memberIds));
    foreach ($memberIds as $mid) {
        ensure_project_member($projectId, $mid);
    }
}

switch ($action) {
    case 'index':
        $status = (string)input('status', 'open');
        $clientId = input_int('client');
        $deptId = input_int('department');
        $search = (string)input('q', '');
        [$scope, $params] = project_scope('p');
        $where = [$scope];
        if ($status === 'open') {
            $where[] = "p.status IN ('planning','active','on_hold')";
        } elseif (isset(project_statuses()[$status])) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($clientId === -1) {
            $where[] = 'p.client_id IS NULL';
        } elseif ($clientId) {
            $where[] = 'p.client_id = ?';
            $params[] = $clientId;
        }
        if ($deptId) {
            $where[] = 'p.department_id = ?';
            $params[] = $deptId;
        }
        if ($search !== '') {
            $where[] = '(p.name LIKE ? OR p.code LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $projects = rows(
            'SELECT p.*, c.name AS client_name, d.name AS department_name, m.name AS manager_name
             FROM projects p
             LEFT JOIN clients c ON c.id = p.client_id
             LEFT JOIN departments d ON d.id = p.department_id
             LEFT JOIN users m ON m.id = p.manager_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY FIELD(p.status, \'active\', \'planning\', \'on_hold\', \'completed\', \'cancelled\'), p.target_date IS NULL, p.target_date, p.name',
            $params
        );
        $progress = project_progress(array_column($projects, 'id'));
        render('projects/index', [
            'pageTitle' => t('Projects'),
            'projects' => $projects, 'progress' => $progress,
            'status' => $status, 'clientId' => $clientId, 'deptId' => $deptId, 'search' => $search,
            'clients' => all_clients(false), 'departments' => all_departments(),
        ]);
        break;

    case 'create':
    case 'edit':
        $project = null;
        if ($action === 'edit') {
            $project = find_project_or_404((int)input('id'));
            if (!can_manage_project($project)) {
                abort(403);
            }
        } elseif (!can('projects.create')) {
            abort(403);
        }

        if (is_post()) {
            $d = project_form_data();
            $errors = validate_project($d, $project ? (int)$project['id'] : null);
            $reason = (string)input('target_reason', '');
            if ($project && $project['target_date'] && $d['target_date'] !== $project['target_date'] && $reason === '') {
                $errors[] = t('Please give a reason for changing the target date.');
            }
            if ($errors) {
                foreach ($errors as $err) {
                    flash('danger', $err);
                }
                remember_old();
                redirect($project ? url('projects', ['a' => 'edit', 'id' => $project['id']]) : url('projects', ['a' => 'create']));
            }
            $members = (array)($_POST['members'] ?? []);
            if ($project) {
                $before = describe_project($project);
                update('projects', $d + ['updated_at' => now()], ['id' => $project['id']]);
                $after = describe_project(array_merge($project, $d));
                audit_diff('project', (int)$project['id'], $d['name'], $before, $after, $reason !== '' ? ['target_date' => $reason] : []);
                save_members((int)$project['id'], $members, $d['manager_id']);
                flash('success', t('Project updated.'));
                redirect(url('projects', ['a' => 'view', 'id' => $project['id']]));
            }
            $id = insert('projects', $d + ['created_by' => uid(), 'created_at' => now()]);
            audit('project', $id, $d['name'], 'create');
            save_members($id, $members, $d['manager_id'] ?: uid());
            flash('success', t('Project created. Now add its tasks.'));
            redirect(url('projects', ['a' => 'view', 'id' => $id]));
        }

        $memberIds = $project
            ? array_map('intval', q('SELECT user_id FROM project_members WHERE project_id = ?', [$project['id']])->fetchAll(PDO::FETCH_COLUMN))
            : [];
        render('projects/form', [
            'pageTitle' => $project ? t('Edit project') : t('New project'),
            'project' => $project,
            'memberIds' => array_map('intval', (array)old('members', $memberIds)),
            'users' => active_users(), 'clients' => all_clients(), 'departments' => all_departments(),
        ]);
        break;

    case 'view':
    case 'board':
        $project = find_project_or_404((int)input('id'));
        $canManage = can_manage_project($project);
        $filters = [
            'status'   => (string)input('status', ''),
            'assignee' => input_int('assignee'),
            'priority' => (string)input('priority', ''),
            'due'      => (string)input('due', ''),
        ];
        $where = ['t.project_id = ?'];
        $params = [$project['id']];
        if ($filters['status'] === 'open') {
            $where[] = "t.status <> 'done'";
        } elseif (isset(task_statuses()[$filters['status']])) {
            $where[] = 't.status = ?';
            $params[] = $filters['status'];
        }
        if ($filters['assignee'] === -1) {
            $where[] = 't.assignee_id IS NULL';
        } elseif ($filters['assignee']) {
            $where[] = 't.assignee_id = ?';
            $params[] = $filters['assignee'];
        }
        if (isset(priorities()[$filters['priority']])) {
            $where[] = 't.priority = ?';
            $params[] = $filters['priority'];
        }
        if ($filters['due'] === 'overdue') {
            $where[] = "t.status <> 'done' AND t.due_date < ?";
            $params[] = today();
        } elseif ($filters['due'] === 'week') {
            $where[] = "t.status <> 'done' AND t.due_date BETWEEN ? AND ?";
            $params[] = today();
            $params[] = date('Y-m-d', strtotime('+7 days'));
        }
        $tasks = rows(
            'SELECT t.*, u.name AS assignee_name, pt.title AS parent_title,
                    (SELECT COUNT(*) FROM tasks s WHERE s.parent_id = t.id) AS sub_count,
                    (SELECT COUNT(*) FROM tasks s WHERE s.parent_id = t.id AND s.status = \'done\') AS sub_done,
                    (SELECT COUNT(*) FROM task_comments tc WHERE tc.task_id = t.id) AS comment_count,
                    (SELECT COUNT(*) FROM attachments a WHERE a.task_id = t.id) AS file_count
             FROM tasks t
             LEFT JOIN users u ON u.id = t.assignee_id
             LEFT JOIN tasks pt ON pt.id = t.parent_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY t.sort_order, t.due_date IS NULL, t.due_date, t.id',
            $params
        );
        $info = row(
            'SELECT c.name AS client_name, d.name AS department_name, m.name AS manager_name
             FROM projects p LEFT JOIN clients c ON c.id = p.client_id
             LEFT JOIN departments d ON d.id = p.department_id LEFT JOIN users m ON m.id = p.manager_id
             WHERE p.id = ?',
            [$project['id']]
        );
        $members = rows(
            'SELECT u.id, u.name, u.designation FROM project_members pm JOIN users u ON u.id = pm.user_id
             WHERE pm.project_id = ? ORDER BY u.name',
            [$project['id']]
        );
        render($action === 'board' ? 'projects/board' : 'projects/view', [
            'pageTitle' => $project['name'],
            'project' => $project, 'info' => $info, 'tasks' => $tasks, 'members' => $members,
            'progress' => project_progress([(int)$project['id']])[(int)$project['id']],
            'canManage' => $canManage, 'filters' => $filters, 'tab' => $action,
            'extraScripts' => $action === 'board' ? ['vendor/sortable/Sortable.min.js', 'js/kanban.js'] : [],
        ]);
        break;

    case 'history':
        $project = find_project_or_404((int)input('id'));
        $history = rows(
            "SELECT * FROM audit_log
             WHERE (entity_type = 'project' AND entity_id = ?)
                OR (entity_type = 'task' AND entity_id IN (SELECT id FROM tasks WHERE project_id = ?))
             ORDER BY id DESC LIMIT 300",
            [$project['id'], $project['id']]
        );
        $info = row(
            'SELECT c.name AS client_name, d.name AS department_name, m.name AS manager_name
             FROM projects p LEFT JOIN clients c ON c.id = p.client_id
             LEFT JOIN departments d ON d.id = p.department_id LEFT JOIN users m ON m.id = p.manager_id WHERE p.id = ?',
            [$project['id']]
        );
        render('projects/history', [
            'pageTitle' => $project['name'], 'project' => $project, 'info' => $info, 'history' => $history,
            'progress' => project_progress([(int)$project['id']])[(int)$project['id']],
            'canManage' => can_manage_project($project), 'tab' => 'history',
        ]);
        break;

    case 'delete':
        if (!is_post()) {
            abort(405);
        }
        $project = find_project_or_404((int)input('id'));
        if (!can('projects.delete')) {
            abort(403);
        }
        // Remove stored files for this project's tasks before the rows cascade away.
        foreach (rows('SELECT a.stored_name FROM attachments a JOIN tasks t ON t.id = a.task_id WHERE t.project_id = ?', [$project['id']]) as $f) {
            @unlink(APP_ROOT . '/uploads/' . $f['stored_name']);
        }
        $taskCount = (int)val('SELECT COUNT(*) FROM tasks WHERE project_id = ?', [$project['id']]);
        q('DELETE FROM projects WHERE id = ?', [$project['id']]);
        audit('project', (int)$project['id'], $project['name'], 'delete', "Deleted with $taskCount tasks");
        flash('success', t('Project deleted.'));
        redirect(url('projects'));

    default:
        abort(404);
}
