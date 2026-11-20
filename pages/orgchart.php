<?php
/** Organisation chart built from each user's "Reports to". */
declare(strict_types=1);

require_perm('users.view');

$people = rows(
    "SELECT u.id, u.name, u.designation, u.reports_to, r.slug AS role, d.name AS department_name
     FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN departments d ON d.id = u.department_id
     WHERE u.is_active = 1 AND r.slug <> 'client' ORDER BY r.level, u.name"
);
$ids = array_column($people, 'id');
$children = [];
foreach ($people as $p) {
    // Anyone whose manager is missing/inactive is shown at the top level.
    $parent = ($p['reports_to'] && in_array($p['reports_to'], $ids)) ? (int)$p['reports_to'] : 0;
    $children[$parent][] = $p;
}
render('users/orgchart', ['pageTitle' => t('Org Chart'), 'children' => $children]);
