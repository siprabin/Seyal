<?php
/** Roles & Permissions matrix (Super Admin only). */
declare(strict_types=1);

if (!is_super_admin()) {
    abort(403);
}

$roles = array_values(array_filter(all_roles(), fn($r) => $r['slug'] !== ROLE_SUPER_ADMIN));
$perms = permission_list();

if (is_post()) {
    if (input('reset')) {
        seed_role_permissions(db(), true);
        audit('role', null, 'All roles', 'update', 'Permissions reset to defaults');
        flash('success', t('Permissions reset to the defaults.'));
        redirect(url('roles'));
    }
    $sent = (array)($_POST['perm'] ?? []);
    db()->beginTransaction();
    foreach ($roles as $r) {
        $before = q('SELECT perm FROM role_permissions WHERE role_id = ?', [$r['id']])->fetchAll(PDO::FETCH_COLUMN);
        $after = array_values(array_intersect(array_keys($perms), array_keys((array)($sent[$r['id']] ?? []))));
        q('DELETE FROM role_permissions WHERE role_id = ?', [$r['id']]);
        foreach ($after as $p) {
            q('INSERT INTO role_permissions (role_id, perm) VALUES (?, ?)', [$r['id'], $p]);
        }
        sort($before);
        sort($after);
        if ($before !== $after) {
            $u = user();
            audit_raw((int)$u['id'], $u['name'], 'role', (int)$r['id'], $r['name'], 'update', 'permissions', implode(', ', $before), implode(', ', $after));
        }
    }
    db()->commit();
    flash('success', t('Permissions saved.'));
    redirect(url('roles'));
}

$current = [];
foreach (rows('SELECT role_id, perm FROM role_permissions') as $rp) {
    $current[(int)$rp['role_id']][$rp['perm']] = true;
}
render('roles/index', ['pageTitle' => t('Roles & Permissions'), 'roles' => $roles, 'perms' => $perms, 'current' => $current]);
