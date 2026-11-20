-- Department Heads and Project Managers / Team Leads can add and edit clients (and their portal logins) by default.
-- Admins can still turn this off in Roles & Permissions.
INSERT IGNORE INTO role_permissions (role_id, perm) SELECT id, 'clients.manage' FROM roles WHERE slug IN ('dept_head', 'manager');
