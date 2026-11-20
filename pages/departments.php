<?php
/** Departments. */
declare(strict_types=1);

require_perm('departments.manage');

switch ($action) {
    case 'index':
        $departments = rows(
            'SELECT d.*, h.name AS head_name,
                    (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id AND u.is_active = 1) AS user_count,
                    (SELECT COUNT(*) FROM projects p WHERE p.department_id = d.id) AS project_count
             FROM departments d LEFT JOIN users h ON h.id = d.head_user_id ORDER BY d.name'
        );
        render('departments/index', ['pageTitle' => t('Departments'), 'departments' => $departments]);
        break;

    case 'create':
    case 'edit':
        $dept = $action === 'edit' ? (row('SELECT * FROM departments WHERE id = ?', [(int)input('id')]) ?? abort(404)) : null;
        if (is_post()) {
            $d = [
                'name'         => mb_substr((string)input('name', ''), 0, 120),
                'description'  => (string)input('description', '') ?: null,
                'head_user_id' => input_int('head_user_id') ?: null,
            ];
            if ($d['name'] === '') {
                flash('danger', t('Department name is required.'));
                remember_old();
                redirect(url('departments', array_filter(['a' => $action, 'id' => $dept['id'] ?? null])));
            }
            if ($dept) {
                update('departments', $d + ['updated_at' => now()], ['id' => $dept['id']]);
                audit_diff('department', (int)$dept['id'], $d['name'],
                    ['name' => $dept['name'], 'description' => $dept['description'], 'head' => user_name($dept['head_user_id'] ? (int)$dept['head_user_id'] : null)],
                    ['name' => $d['name'], 'description' => $d['description'], 'head' => user_name($d['head_user_id'])]);
                flash('success', t('Department updated.'));
            } else {
                $id = insert('departments', $d + ['created_at' => now()]);
                audit('department', $id, $d['name'], 'create');
                flash('success', t('Department added.'));
            }
            redirect(url('departments'));
        }
        render('departments/form', ['pageTitle' => $dept ? t('Edit department') : t('New department'), 'dept' => $dept, 'users' => active_users()]);
        break;

    case 'delete':
        if (!is_post()) {
            abort(405);
        }
        $dept = row('SELECT * FROM departments WHERE id = ?', [(int)input('id')]) ?? abort(404);
        q('DELETE FROM departments WHERE id = ?', [$dept['id']]);
        audit('department', (int)$dept['id'], $dept['name'], 'delete');
        flash('success', t('Department deleted. Its people and projects now have no department.'));
        redirect(url('departments'));

    default:
        abort(404);
}
