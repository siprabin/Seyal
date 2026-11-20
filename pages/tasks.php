<?php
/** Tasks and subtasks: create, edit, view, status change, comments, attachments, delete. */
declare(strict_types=1);

/** Display-friendly snapshot of a task, used for the audit trail. */
function describe_task(array $t): array
{
    return [
        'title'           => $t['title'],
        'description'     => $t['description'],
        'assignee'        => user_name($t['assignee_id'] ? (int)$t['assignee_id'] : null),
        'status'          => $t['status'],
        'priority'        => $t['priority'],
        'weightage'       => (string)$t['weightage'],
        'estimated_hours' => $t['estimated_hours'] === null ? null : rtrim(rtrim((string)$t['estimated_hours'], '0'), '.'),
        'start_date'      => $t['start_date'],
        'due_date'        => $t['due_date'],
    ];
}

function task_form_data(): array
{
    $hours = input('estimated_hours', '');
    return [
        'title'           => mb_substr((string)input('title', ''), 0, 255),
        'description'     => (string)input('description', '') ?: null,
        'assignee_id'     => input_int('assignee_id') ?: null,
        'status'          => array_key_exists((string)input('status'), task_statuses()) ? input('status') : 'todo',
        'priority'        => array_key_exists((string)input('priority'), priorities()) ? input('priority') : 'medium',
        'weightage'       => max(1, min(5, (int)input('weightage', 3))),
        'estimated_hours' => ($hours === '' || $hours === null) ? null : round(max(0, min(9999, (float)$hours)), 2),
        'start_date'      => input_date('start_date'),
        'due_date'        => input_date('due_date'),
    ];
}

function validate_task(array $d): array
{
    $errors = [];
    if ($d['title'] === '') {
        $errors[] = t('Task title is required.');
    }
    if ($d['start_date'] && $d['due_date'] && $d['due_date'] < $d['start_date']) {
        $errors[] = t('Due date cannot be before the start date.');
    }
    if ($d['assignee_id'] && !val("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND u.is_active = 1 AND r.slug <> 'client'", [$d['assignee_id']])) {
        $errors[] = t('The selected assignee is not an active user.');
    }
    return $errors;
}

function upload_allowed_ext(): array
{
    $list = setting('upload_extensions', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,png,jpg,jpeg,gif,webp,zip,rar,7z,mp4,mp3');
    return array_filter(array_map(fn($x) => strtolower(trim($x, " .\t")), explode(',', $list)));
}

/** Tasks that may be chosen as assignee list: project members first, then everyone else. */
function assignee_options(int $projectId): array
{
    $members = array_map('intval', q('SELECT user_id FROM project_members WHERE project_id = ?', [$projectId])->fetchAll(PDO::FETCH_COLUMN));
    $all = active_users();
    $inProject = array_values(array_filter($all, fn($u) => in_array((int)$u['id'], $members, true)));
    $others = array_values(array_filter($all, fn($u) => !in_array((int)$u['id'], $members, true)));
    return [$inProject, $others];
}

switch ($action) {
    case 'create':
    case 'edit':
        $task = null;
        $parent = null;
        if ($action === 'edit') {
            [$task, $project] = find_task_or_404((int)input('id'));
            if (!can_edit_task($task, $project)) {
                abort(403);
            }
            $parent = $task['parent_id'] ? row('SELECT * FROM tasks WHERE id = ?', [$task['parent_id']]) : null;
        } elseif ($parentId = input_int('parent')) {
            [$parent, $project] = find_task_or_404($parentId);
            if (!can_add_subtask($parent, $project)) {
                abort(403);
            }
        } else {
            $project = find_project_or_404((int)input('project'));
            if (!can_create_task($project)) {
                abort(403);
            }
        }
        $canAssign = can_assign_tasks($project);

        if (is_post()) {
            $d = task_form_data();
            // People who don't manage the project can't give work to others or set weightage (it feeds performance scores):
            // new tasks are always theirs with the default weightage; on edit, assignee and weightage stay as they were.
            if (!$canAssign) {
                $d['assignee_id'] = $task ? ($task['assignee_id'] ? (int)$task['assignee_id'] : null) : uid();
                $d['weightage'] = $task ? (int)$task['weightage'] : 3;
            }
            $errors = validate_task($d);
            $reason = (string)input('due_reason', '');
            if ($task && $task['due_date'] && $d['due_date'] !== $task['due_date'] && $reason === '') {
                $errors[] = t('Please give a reason for changing the due date.');
            }
            $back = $task
                ? url('tasks', ['a' => 'edit', 'id' => $task['id']])
                : url('tasks', array_filter(['a' => 'create', 'project' => $project['id'], 'parent' => $parent['id'] ?? null]));
            if ($errors) {
                foreach ($errors as $err) {
                    flash('danger', $err);
                }
                remember_old();
                redirect($back);
            }
            if ($task) {
                $before = describe_task($task);
                $d['completed_at'] = $d['status'] === 'done' ? ($task['completed_at'] ?: now()) : null;
                if ($task['status'] === 'done' && $d['status'] !== 'done') {
                    clear_rating_on_reopen((int)$task['id']);
                }
                update('tasks', $d + ['updated_at' => now()], ['id' => $task['id']]);
                audit_diff('task', (int)$task['id'], $d['title'], $before, describe_task(array_merge($task, $d)), $reason !== '' ? ['due_date' => $reason] : []);
                ensure_project_member((int)$project['id'], $d['assignee_id']);
                flash('success', t('Task updated.'));
                redirect(url('tasks', ['a' => 'view', 'id' => $task['id']]));
            }
            $sort = (int)val('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM tasks WHERE project_id = ?', [$project['id']]);
            $id = insert('tasks', $d + [
                'project_id'   => $project['id'],
                'parent_id'    => $parent['id'] ?? null,
                'sort_order'   => $sort,
                'completed_at' => $d['status'] === 'done' ? now() : null,
                'created_by'   => uid(),
                'created_at'   => now(),
            ]);
            audit('task', $id, $d['title'], 'create', $parent ? 'Subtask of: ' . $parent['title'] : null);
            ensure_project_member((int)$project['id'], $d['assignee_id']);
            flash('success', $parent ? t('Subtask added.') : t('Task added.'));
            if (input('save_and_new')) {
                redirect(url('tasks', array_filter(['a' => 'create', 'project' => $project['id'], 'parent' => $parent['id'] ?? null])));
            }
            redirect($parent ? url('tasks', ['a' => 'view', 'id' => $parent['id']]) : url('projects', ['a' => 'view', 'id' => $project['id']]));
        }

        [$inProject, $others] = assignee_options((int)$project['id']);
        render('tasks/form', [
            'pageTitle' => $task ? t('Edit task') : ($parent ? t('New subtask') : t('New task')),
            'task' => $task, 'parent' => $parent, 'project' => $project,
            'inProject' => $inProject, 'others' => $others,
            'canAssign' => $canAssign,
            'fixedAssignee' => $task ? user_name($task['assignee_id'] ? (int)$task['assignee_id'] : null) : (string)user()['name'],
            // Values typed in a quick-add row before "More options" (new tasks only).
            'prefill' => $task ? [] : array_filter([
                'title'       => mb_substr((string)input('title', ''), 0, 255),
                'assignee_id' => input_int('assignee_id') ?: null,
                'due_date'    => input_date('due_date'),
            ], fn($x) => $x !== null && $x !== ''),
        ]);
        break;

    case 'view':
        [$task, $project] = find_task_or_404((int)input('id'));
        $info = row(
            'SELECT a.name AS assignee_name, a.designation AS assignee_designation, c.name AS creator_name
             FROM tasks t LEFT JOIN users a ON a.id = t.assignee_id LEFT JOIN users c ON c.id = t.created_by WHERE t.id = ?',
            [$task['id']]
        );
        // Breadcrumb trail of parent tasks.
        $trail = [];
        $pid = $task['parent_id'];
        while ($pid && count($trail) < 10) {
            $pt = row('SELECT id, title, parent_id FROM tasks WHERE id = ?', [$pid]);
            if (!$pt) {
                break;
            }
            array_unshift($trail, $pt);
            $pid = $pt['parent_id'];
        }
        $subtasks = rows(
            'SELECT t.*, u.name AS assignee_name FROM tasks t LEFT JOIN users u ON u.id = t.assignee_id
             WHERE t.parent_id = ? ORDER BY t.sort_order, t.id',
            [$task['id']]
        );
        $comments = rows(
            'SELECT c.*, u.name AS user_name FROM task_comments c LEFT JOIN users u ON u.id = c.user_id
             WHERE c.task_id = ? ORDER BY c.created_at, c.id',
            [$task['id']]
        );
        $files = rows(
            'SELECT a.*, u.name AS user_name FROM attachments a LEFT JOIN users u ON u.id = a.user_id
             WHERE a.task_id = ? ORDER BY a.created_at DESC',
            [$task['id']]
        );
        $history = rows("SELECT * FROM audit_log WHERE entity_type = 'task' AND entity_id = ? ORDER BY id DESC LIMIT 100", [$task['id']]);
        [$inProject, $others] = assignee_options((int)$project['id']);
        render('tasks/view', [
            'pageTitle' => $task['title'],
            'task' => $task, 'project' => $project, 'info' => $info, 'trail' => $trail,
            'subtasks' => $subtasks, 'comments' => $comments, 'files' => $files, 'history' => $history,
            'canManage' => can_manage_project($project),
            'canEdit' => can_edit_task($task, $project),
            'canStatus' => can_update_task_status($task, $project),
            'canSub' => can_add_subtask($task, $project),
            'inProject' => $inProject, 'others' => $others,
            'maxMb' => (int)setting('upload_max_mb', 10),
            'rating' => row('SELECT r.*, u.name AS rater_name FROM task_ratings r LEFT JOIN users u ON u.id = r.rated_by WHERE r.task_id = ?', [$task['id']]),
            'canRate' => can_rate_task($task),
            // Scores stay hidden from the person rated until their appraisal is published.
            'canSeeRating' => can_rate_task($task) || ($task['assignee_id'] && can_view_performance((int)$task['assignee_id'])),
            'worklog' => rows(
                'SELECT wl.hours, wl.ai_used, wl.ai_minutes, wl.description, wd.work_date, wd.id AS day_id, u.name AS user_name, u.id AS user_id
                 FROM work_logs wl JOIN work_days wd ON wd.id = wl.work_day_id JOIN users u ON u.id = wl.user_id
                 WHERE wl.task_id = ? ORDER BY wd.work_date DESC, wl.id DESC LIMIT 100',
                [$task['id']]
            ),
        ]);
        break;

    case 'status':
        if (!is_post()) {
            abort(405);
        }
        [$task, $project] = find_task_or_404((int)input('id'));
        $status = (string)input('status', '');
        if (!isset(task_statuses()[$status])) {
            abort(422, t('Unknown status.'));
        }
        if (!can_update_task_status($task, $project)) {
            abort(403, t('Only the assignee or a project manager can change this task.'));
        }
        change_task_status($task, $status);
        if (is_ajax()) {
            json_out(['ok' => true, 'status' => $status]);
        }
        flash('success', t('Status changed to :status.', ['status' => task_statuses()[$status]['label']]));
        back(url('tasks', ['a' => 'view', 'id' => $task['id']]));

    case 'comment':
        if (!is_post()) {
            abort(405);
        }
        [$task, $project] = find_task_or_404((int)input('id'));
        $body = trim((string)input('body', ''));
        if ($body === '') {
            flash('warning', t('Comment cannot be empty.'));
        } else {
            insert('task_comments', ['task_id' => $task['id'], 'user_id' => uid(), 'body' => mb_substr($body, 0, 10000), 'created_at' => now()]);
            audit('task', (int)$task['id'], $task['title'], 'comment', mb_strimwidth($body, 0, 200, '…'));
        }
        redirect(url('tasks', ['a' => 'view', 'id' => $task['id']]) . '#comments');

    case 'delete_comment':
        if (!is_post()) {
            abort(405);
        }
        $c = row('SELECT * FROM task_comments WHERE id = ?', [(int)input('cid')]);
        if (!$c) {
            abort(404);
        }
        [$task, $project] = find_task_or_404((int)$c['task_id']);
        if ((int)$c['user_id'] !== uid() && !can_manage_project($project)) {
            abort(403);
        }
        q('DELETE FROM task_comments WHERE id = ?', [$c['id']]);
        audit('task', (int)$task['id'], $task['title'], 'update', 'Comment deleted: ' . mb_strimwidth($c['body'], 0, 200, '…'));
        redirect(url('tasks', ['a' => 'view', 'id' => $task['id']]) . '#comments');

    case 'upload':
        if (!is_post()) {
            abort(405);
        }
        [$task, $project] = find_task_or_404((int)input('id'));
        $back = url('tasks', ['a' => 'view', 'id' => $task['id']]) . '#files';
        $f = $_FILES['file'] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            flash('warning', t('Choose a file first.'));
            redirect($back);
        }
        $maxBytes = (int)setting('upload_max_mb', 10) * 1024 * 1024;
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > $maxBytes) {
            flash('danger', t('File is too large. The limit is :n MB.', ['n' => (int)setting('upload_max_mb', 10)]));
            redirect($back);
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            flash('danger', t('Upload failed. Please try again.'));
            redirect($back);
        }
        $original = basename(str_replace('\\', '/', (string)$f['name']));
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, upload_allowed_ext(), true)) {
            flash('danger', t('This file type is not allowed. Allowed: :list', ['list' => implode(', ', upload_allowed_ext())]));
            redirect($back);
        }
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], APP_ROOT . '/uploads/' . $stored)) {
            flash('danger', t('Could not save the file. Check that the uploads folder is writable.'));
            redirect($back);
        }
        $mime = function_exists('mime_content_type') ? (mime_content_type(APP_ROOT . '/uploads/' . $stored) ?: null) : null;
        insert('attachments', [
            'task_id' => $task['id'], 'user_id' => uid(), 'original_name' => mb_substr($original, 0, 255),
            'stored_name' => $stored, 'mime' => $mime, 'size_bytes' => (int)$f['size'], 'created_at' => now(),
        ]);
        audit('task', (int)$task['id'], $task['title'], 'upload', $original);
        flash('success', t('File uploaded.'));
        redirect($back);

    case 'delete':
        if (!is_post()) {
            abort(405);
        }
        [$task, $project] = find_task_or_404((int)input('id'));
        if (!can_edit_task($task, $project)) {
            abort(403);
        }
        // Collect files of this task and all its descendants before the cascade.
        $ids = [(int)$task['id']];
        for ($i = 0; $i < count($ids) && $i < 1000; $i++) {
            foreach (q('SELECT id FROM tasks WHERE parent_id = ?', [$ids[$i]])->fetchAll(PDO::FETCH_COLUMN) as $cid) {
                $ids[] = (int)$cid;
            }
        }
        foreach (rows('SELECT stored_name FROM attachments WHERE task_id IN (' . in_list($ids) . ')', $ids) as $f) {
            @unlink(APP_ROOT . '/uploads/' . $f['stored_name']);
        }
        q('DELETE FROM tasks WHERE id = ?', [$task['id']]);
        audit('task', (int)$task['id'], $task['title'], 'delete', 'Project: ' . $project['name'] . (count($ids) > 1 ? ' (with ' . (count($ids) - 1) . ' subtasks)' : ''));
        flash('success', t('Task deleted.'));
        redirect($task['parent_id'] ? url('tasks', ['a' => 'view', 'id' => $task['parent_id']]) : url('projects', ['a' => 'view', 'id' => $project['id']]));

    default:
        abort(404);
}
