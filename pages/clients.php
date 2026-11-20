<?php
/** Client records. (The read-only client portal login comes in Phase 3.) */
declare(strict_types=1);

require_perm('clients.view');

function client_form_data(): array
{
    $email = (string)input('email', '');
    return [
        'name'           => mb_substr((string)input('name', ''), 0, 160),
        'contact_person' => mb_substr((string)input('contact_person', ''), 0, 120) ?: null,
        'email'          => $email !== '' ? mb_substr(mb_strtolower($email), 0, 190) : null,
        'phone'          => mb_substr((string)input('phone', ''), 0, 40) ?: null,
        'address'        => (string)input('address', '') ?: null,
        'notes'          => (string)input('notes', '') ?: null,
        'is_active'      => input('is_active') ? 1 : 0,
    ];
}

switch ($action) {
    case 'index':
        $show = (string)input('show', 'active');
        $search = (string)input('q', '');
        $where = ['1=1'];
        $params = [];
        if ($show === 'active') {
            $where[] = 'c.is_active = 1';
        } elseif ($show === 'inactive') {
            $where[] = 'c.is_active = 0';
        }
        if ($search !== '') {
            $where[] = '(c.name LIKE ? OR c.contact_person LIKE ? OR c.email LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%");
        }
        $clients = rows(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM projects p WHERE p.client_id = c.id) AS project_count,
                    (SELECT COUNT(*) FROM projects p WHERE p.client_id = c.id AND p.status IN ('planning','active','on_hold')) AS open_count
             FROM clients c WHERE " . implode(' AND ', $where) . ' ORDER BY c.name',
            $params
        );
        render('clients/index', ['pageTitle' => t('Clients'), 'clients' => $clients, 'show' => $show, 'search' => $search]);
        break;

    case 'view':
        $client = row('SELECT * FROM clients WHERE id = ?', [(int)input('id')]) ?? abort(404);
        [$scope, $sp] = project_scope('p');
        $projects = rows(
            "SELECT p.*, m.name AS manager_name FROM projects p LEFT JOIN users m ON m.id = p.manager_id
             WHERE p.client_id = ? AND $scope ORDER BY p.status = 'completed', p.target_date",
            array_merge([$client['id']], $sp)
        );
        render('clients/view', [
            'pageTitle' => $client['name'], 'client' => $client, 'projects' => $projects,
            'portalUsers' => rows("SELECT u.* FROM users u JOIN roles r ON r.id = u.role_id WHERE u.client_id = ? AND r.slug = 'client' ORDER BY u.name", [$client['id']]),
            'progress' => project_progress(array_column($projects, 'id')),
        ]);
        break;

    case 'create':
    case 'edit':
        require_perm('clients.manage');
        $client = $action === 'edit' ? (row('SELECT * FROM clients WHERE id = ?', [(int)input('id')]) ?? abort(404)) : null;
        if (is_post()) {
            $d = client_form_data();
            $errors = [];
            if ($d['name'] === '') {
                $errors[] = t('Client name is required.');
            }
            if ($d['email'] && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = t('Please enter a valid email address.');
            }
            if ($errors) {
                foreach ($errors as $err) {
                    flash('danger', $err);
                }
                remember_old();
                redirect(url('clients', array_filter(['a' => $action, 'id' => $client['id'] ?? null])));
            }
            if ($client) {
                update('clients', $d + ['updated_at' => now()], ['id' => $client['id']]);
                $keys = array_keys($d);
                audit_diff('client', (int)$client['id'], $d['name'], array_intersect_key($client, array_flip($keys)), $d);
                flash('success', t('Client updated.'));
                redirect(url('clients', ['a' => 'view', 'id' => $client['id']]));
            }
            $id = insert('clients', $d + ['created_at' => now()]);
            audit('client', $id, $d['name'], 'create');
            flash('success', t('Client added.'));
            redirect(url('clients', ['a' => 'view', 'id' => $id]));
        }
        render('clients/form', ['pageTitle' => $client ? t('Edit client') : t('New client'), 'client' => $client]);
        break;

    case 'delete':
        require_perm('clients.manage');
        if (!is_post()) {
            abort(405);
        }
        $client = row('SELECT * FROM clients WHERE id = ?', [(int)input('id')]) ?? abort(404);
        if (val('SELECT COUNT(*) FROM projects WHERE client_id = ?', [$client['id']])) {
            flash('warning', t('This client has projects, so it cannot be deleted. Mark it inactive instead.'));
            redirect(url('clients', ['a' => 'view', 'id' => $client['id']]));
        }
        q('DELETE FROM clients WHERE id = ?', [$client['id']]);
        audit('client', (int)$client['id'], $client['name'], 'delete');
        flash('success', t('Client deleted.'));
        redirect(url('clients'));

    case 'portal_user':
        // Create a portal login, or (with uid) activate/deactivate or set a new password.
        require_perm('clients.manage');
        if (!is_post()) {
            abort(405);
        }
        $client = row('SELECT * FROM clients WHERE id = ?', [(int)input('id')]) ?? abort(404);
        $back = url('clients', ['a' => 'view', 'id' => $client['id']]) . '#portal';
        $roleId = (int)val("SELECT id FROM roles WHERE slug = 'client'");
        if ($pu = input_int('uid')) {
            $person = row('SELECT * FROM users WHERE id = ? AND client_id = ? AND role_id = ?', [$pu, $client['id'], $roleId]) ?? abort(404);
            if (input('toggle')) {
                update('users', ['is_active' => $person['is_active'] ? 0 : 1, 'updated_at' => now()], ['id' => $person['id']]);
                audit_diff('user', (int)$person['id'], $person['name'], ['is_active' => $person['is_active']], ['is_active' => $person['is_active'] ? 0 : 1]);
                flash('success', t('Portal access updated.'));
            } else {
                $pw = (string)($_POST['password'] ?? '');
                if ($err = validate_password($pw, $pw)) {
                    flash('danger', $err);
                } else {
                    update('users', ['password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'failed_logins' => 0, 'locked_until' => null, 'updated_at' => now()], ['id' => $person['id']]);
                    audit('user', (int)$person['id'], $person['name'], 'password_reset', 'Portal user, set by ' . user()['name']);
                    flash('success', t('Password changed. Share it with :name.', ['name' => $person['name']]));
                }
            }
            redirect($back);
        }
        $name = mb_substr(trim((string)input('name', '')), 0, 120);
        $email = mb_strtolower(trim((string)input('email', '')));
        $pw = (string)($_POST['password'] ?? '');
        $lang = isset(SUPPORTED_LANGS[input('lang')]) ? input('lang') : setting('default_lang', 'en');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('danger', t('Enter a name and a valid email address.'));
        } elseif (val('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) {
            flash('danger', t('Another user already has this email.'));
        } elseif ($err = validate_password($pw, $pw)) {
            flash('danger', $err);
        } else {
            $newId = insert('users', ['name' => $name, 'email' => $email, 'password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'role_id' => $roleId,
                'client_id' => $client['id'], 'designation' => $client['name'], 'lang' => $lang, 'created_at' => now()]);
            audit('user', $newId, $name, 'create', 'Client portal login for ' . $client['name']);
            flash('success', t('Portal login created. Share the email and password with :name.', ['name' => $name]));
        }
        redirect($back);

    default:
        abort(404);
}
