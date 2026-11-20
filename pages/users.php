<?php
/** Staff directory and user management. */
declare(strict_types=1);

require_perm('users.view');

function describe_user(array $u): array
{
    return [
        'name'        => $u['name'],
        'email'       => $u['email'],
        'role'        => (string)val('SELECT name FROM roles WHERE id = ?', [$u['role_id']]),
        'department'  => $u['department_id'] ? (string)val('SELECT name FROM departments WHERE id = ?', [$u['department_id']]) : '',
        'reports_to'  => user_name($u['reports_to'] ? (int)$u['reports_to'] : null),
        'designation' => $u['designation'],
        'phone'       => $u['phone'],
        'lang'        => $u['lang'],
        'is_active'   => (string)(int)$u['is_active'],
    ];
}

/** True if making $userId report to $managerId would create a loop. */
function creates_reporting_loop(int $userId, int $managerId): bool
{
    $seen = 0;
    while ($managerId && $seen++ < 100) {
        if ($managerId === $userId) {
            return true;
        }
        $managerId = (int)val('SELECT reports_to FROM users WHERE id = ?', [$managerId]);
    }
    return false;
}

switch ($action) {
    case 'index':
        $f = [
            'q'          => (string)input('q', ''),
            'department' => input_int('department'),
            'role'       => input_int('role'),
            'show'       => (string)input('show', 'active'),
        ];
        $where = ["r.slug <> 'client'"];
        $params = [];
        if ($f['show'] === 'active') {
            $where[] = 'u.is_active = 1';
        } elseif ($f['show'] === 'inactive') {
            $where[] = 'u.is_active = 0';
        }
        if ($f['department']) {
            $where[] = 'u.department_id = ?';
            $params[] = $f['department'];
        }
        if ($f['role']) {
            $where[] = 'u.role_id = ?';
            $params[] = $f['role'];
        }
        if ($f['q'] !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.designation LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        $users = rows(
            'SELECT u.*, r.slug AS role, d.name AS department_name, m.name AS manager_name
             FROM users u JOIN roles r ON r.id = u.role_id
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN users m ON m.id = u.reports_to
             WHERE ' . implode(' AND ', $where) . ' ORDER BY r.level, u.name',
            $params
        );
        render('users/index', ['pageTitle' => t('Users'), 'users' => $users, 'f' => $f, 'departments' => all_departments(), 'roles' => all_roles()]);
        break;

    case 'view':
        $person = row(
            'SELECT u.*, r.slug AS role, d.name AS department_name, m.name AS manager_name
             FROM users u JOIN roles r ON r.id = u.role_id
             LEFT JOIN departments d ON d.id = u.department_id LEFT JOIN users m ON m.id = u.reports_to
             WHERE u.id = ?',
            [(int)input('id')]
        ) ?? abort(404);
        $reports = rows('SELECT id, name, designation FROM users WHERE reports_to = ? AND is_active = 1 ORDER BY name', [$person['id']]);
        [$scope, $sp] = project_scope('p');
        $tasks = rows(
            "SELECT t.id, t.title, t.status, t.due_date, p.name AS project_name
             FROM tasks t JOIN projects p ON p.id = t.project_id
             WHERE t.assignee_id = ? AND t.status <> 'done' AND $scope
             ORDER BY t.due_date IS NULL, t.due_date LIMIT 20",
            array_merge([$person['id']], $sp)
        );
        render('users/view', ['pageTitle' => $person['name'], 'person' => $person, 'reports' => $reports, 'tasks' => $tasks]);
        break;

    case 'create':
    case 'edit':
        require_perm('users.manage');
        $person = $action === 'edit' ? (row('SELECT u.*, r.slug AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [(int)input('id')]) ?? abort(404)) : null;
        if ($person && $person['role'] === ROLE_SUPER_ADMIN && !is_super_admin()) {
            abort(403, t('Only a Super Admin can edit another Super Admin.'));
        }
        $isSelf = $person && (int)$person['id'] === uid();

        if (is_post()) {
            $roleId = (int)input('role_id');
            $roleSlug = (string)val('SELECT slug FROM roles WHERE id = ?', [$roleId]);
            $d = [
                'name'          => mb_substr((string)input('name', ''), 0, 120),
                'email'         => mb_strtolower(mb_substr((string)input('email', ''), 0, 190)),
                'role_id'       => $roleId,
                'department_id' => input_int('department_id') ?: null,
                'reports_to'    => input_int('reports_to') ?: null,
                'designation'   => mb_substr((string)input('designation', ''), 0, 120) ?: null,
                'phone'         => mb_substr((string)input('phone', ''), 0, 40) ?: null,
                'lang'          => isset(SUPPORTED_LANGS[input('lang')]) ? input('lang') : 'en',
                'is_active'     => input('is_active') ? 1 : 0,
            ];
            if ($isSelf) {
                // You cannot demote or deactivate yourself.
                $d['role_id'] = (int)$person['role_id'];
                $d['is_active'] = 1;
                $roleSlug = $person['role'];
            }
            $pw = (string)($_POST['password'] ?? '');
            $errors = [];
            if ($d['name'] === '') {
                $errors[] = t('Name is required.');
            }
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = t('Please enter a valid email address.');
            } elseif (val('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$d['email'], $person['id'] ?? 0])) {
                $errors[] = t('Another user already has this email.');
            }
            if (!$roleSlug || $roleSlug === ROLE_CLIENT) {
                $errors[] = t('Please choose a role.');
            } elseif ($roleSlug === ROLE_SUPER_ADMIN && !is_super_admin()) {
                $errors[] = t('Only a Super Admin can create another Super Admin.');
            }
            if ($person && $d['reports_to'] && creates_reporting_loop((int)$person['id'], $d['reports_to'])) {
                $errors[] = t('That "Reports to" choice would create a loop in the hierarchy.');
            }
            if (!$person || $pw !== '') {
                if ($err = validate_password($pw, (string)($_POST['password_confirm'] ?? ''))) {
                    $errors[] = $err;
                }
            }
            if ($errors) {
                foreach ($errors as $err) {
                    flash('danger', $err);
                }
                remember_old();
                redirect(url('users', array_filter(['a' => $action, 'id' => $person['id'] ?? null])));
            }
            if ($pw !== '') {
                $d['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
            }
            if ($person) {
                $before = describe_user($person);
                update('users', $d + ['updated_at' => now(), 'failed_logins' => 0, 'locked_until' => null], ['id' => $person['id']]);
                audit_diff('user', (int)$person['id'], $d['name'], $before, describe_user(array_merge($person, $d)));
                if ($pw !== '') {
                    audit('user', (int)$person['id'], $d['name'], 'password_reset', 'Set by ' . user()['name']);
                }
                flash('success', t('User updated.'));
                redirect(url('users', ['a' => 'view', 'id' => $person['id']]));
            }
            $id = insert('users', $d + ['created_at' => now()]);
            audit('user', $id, $d['name'], 'create');
            flash('success', t('User added. Share their email and password with them.'));
            redirect(url('users', ['a' => 'view', 'id' => $id]));
        }

        $roles = array_values(array_filter(all_roles(), fn($r) => $r['slug'] !== ROLE_SUPER_ADMIN || is_super_admin()));
        render('users/form', [
            'pageTitle' => $person ? t('Edit user') : t('New user'),
            'person' => $person, 'isSelf' => $isSelf, 'roles' => $roles,
            'departments' => all_departments(),
            'managers' => array_values(array_filter(active_users(), fn($u) => !$person || (int)$u['id'] !== (int)$person['id'])),
        ]);
        break;

    default:
        abort(404);
}
