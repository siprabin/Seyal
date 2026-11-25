-- Team Members no longer see Users and Org Chart by default.
-- A Super Admin can turn it back on under Roles & Permissions.
DELETE rp FROM role_permissions rp JOIN roles r ON r.id = rp.role_id
WHERE rp.perm = 'users.view' AND r.slug = 'member';
