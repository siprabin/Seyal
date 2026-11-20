<?php
/**
 * Roles and permissions.
 * Roles are fixed (they map to the company hierarchy). Which permissions each
 * role has is editable by the Super Admin on the Roles & Permissions page.
 * Super Admin always has every permission.
 */
declare(strict_types=1);

const ROLE_SUPER_ADMIN = 'super_admin';
const ROLE_SENIOR      = 'senior_mgmt';
const ROLE_DEPT_HEAD   = 'dept_head';
const ROLE_MANAGER     = 'manager';
const ROLE_MEMBER      = 'member';
const ROLE_CLIENT      = 'client';

/** permission key => [group, English label] */
function permission_list(): array
{
    return [
        'projects.view_all'        => ['Projects', 'See all projects in the company'],
        'projects.view_department' => ['Projects', 'See all projects of own department'],
        'projects.create'          => ['Projects', 'Create projects'],
        'projects.manage_all'      => ['Projects', 'Edit any visible project and its tasks'],
        'projects.delete'          => ['Projects', 'Delete projects'],
        'clients.view'             => ['Clients', 'View clients'],
        'clients.manage'           => ['Clients', 'Add and edit clients'],
        'users.view'               => ['People', 'View the staff directory and org chart'],
        'users.manage'             => ['People', 'Add and edit users'],
        'departments.manage'       => ['People', 'Add and edit departments'],
        'team.view_all'            => ['Daily work', 'See everyone\'s daily work logs'],
        'team.view_department'     => ['Daily work', 'See daily work logs of own department'],
        'reports.view'             => ['Performance', 'Open reports and exports (for the people and projects they can see)'],
        'appraisals.publish'       => ['Performance', 'Publish appraisals to employees (and reopen finalised ones)'],
        'audit.view'               => ['System', 'View the full audit log'],
        'settings.manage'          => ['System', 'Change system settings'],
    ];
}

/** Defaults loaded at install time (and by "Reset to defaults"). */
function default_role_permissions(): array
{
    return [
        ROLE_SENIOR    => ['team.view_all', 'projects.view_all', 'projects.create', 'projects.manage_all', 'clients.view', 'clients.manage', 'users.view', 'audit.view', 'reports.view'],
        ROLE_DEPT_HEAD => ['team.view_department', 'projects.view_department', 'projects.create', 'projects.manage_all', 'clients.view', 'clients.manage', 'users.view', 'reports.view'],
        ROLE_MANAGER   => ['projects.create', 'clients.view', 'clients.manage', 'users.view', 'reports.view'],
        ROLE_MEMBER    => ['users.view'],
        ROLE_CLIENT    => [],
    ];
}

function seed_role_permissions(PDO $pdo, bool $reset = false): void
{
    $roles = $pdo->query('SELECT id, slug FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
    $defaults = default_role_permissions();
    $ins = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, perm) VALUES (?, ?)');
    foreach ($roles as $id => $slug) {
        if ($reset) {
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$id]);
        }
        foreach ($defaults[$slug] ?? [] as $perm) {
            $ins->execute([$id, $perm]);
        }
    }
}

function role_label(string $slug): string
{
    return t([
        ROLE_SUPER_ADMIN => 'Super Admin',
        ROLE_SENIOR      => 'Senior Management',
        ROLE_DEPT_HEAD   => 'Department Head',
        ROLE_MANAGER     => 'Project Manager / Team Lead',
        ROLE_MEMBER      => 'Team Member',
        ROLE_CLIENT      => 'Client',
    ][$slug] ?? $slug);
}

function all_roles(bool $includeClient = false): array
{
    $sql = 'SELECT * FROM roles' . ($includeClient ? '' : " WHERE slug <> 'client'") . ' ORDER BY level';
    return rows($sql);
}
