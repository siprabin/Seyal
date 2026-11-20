<?php
/** Full audit log with filters (who changed what, especially due dates). */
declare(strict_types=1);

require_perm('audit.view');

$f = [
    'type'   => (string)input('type', ''),
    'user'   => input_int('user'),
    'due'    => (bool)input('due'),
    'from'   => input_date('from'),
    'to'     => input_date('to'),
    'q'      => (string)input('q', ''),
];
$where = ['1=1'];
$params = [];
if ($f['type'] !== '') {
    $where[] = 'entity_type = ?';
    $params[] = $f['type'];
}
if ($f['user']) {
    $where[] = 'user_id = ?';
    $params[] = $f['user'];
}
if ($f['due']) {
    $where[] = "field IN ('due_date', 'target_date')";
}
if ($f['from']) {
    $where[] = 'created_at >= ?';
    $params[] = $f['from'] . ' 00:00:00';
}
if ($f['to']) {
    $where[] = 'created_at <= ?';
    $params[] = $f['to'] . ' 23:59:59';
}
if ($f['q'] !== '') {
    $where[] = '(entity_label LIKE ? OR note LIKE ? OR old_value LIKE ? OR new_value LIKE ?)';
    array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
}
$sqlWhere = implode(' AND ', $where);
$pg = paginate((int)val("SELECT COUNT(*) FROM audit_log WHERE $sqlWhere", $params), 50);
$entries = rows("SELECT * FROM audit_log WHERE $sqlWhere ORDER BY id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params);
$types = q('SELECT DISTINCT entity_type FROM audit_log ORDER BY entity_type')->fetchAll(PDO::FETCH_COLUMN);
$users = rows('SELECT id, name FROM users ORDER BY name');

render('audit/index', ['pageTitle' => t('Audit Log'), 'entries' => $entries, 'f' => $f, 'pg' => $pg, 'types' => $types, 'users' => $users]);
