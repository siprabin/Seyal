<?php
/**
 * Audit trail. Every create / change / delete is written here with who, when
 * and the old and new value, one row per changed field. Due-date changes
 * also carry the reason the user typed.
 */
declare(strict_types=1);

function audit_raw(
    ?int $userId,
    ?string $userName,
    string $entityType,
    ?int $entityId,
    ?string $label,
    string $action,
    ?string $field = null,
    ?string $old = null,
    ?string $new = null,
    ?string $note = null
): void {
    insert('audit_log', [
        'user_id'      => $userId,
        'user_name'    => $userName ? mb_substr($userName, 0, 120) : null,
        'entity_type'  => $entityType,
        'entity_id'    => $entityId,
        'entity_label' => $label !== null ? mb_substr($label, 0, 255) : null,
        'action'       => $action,
        'field'        => $field,
        'old_value'    => $old,
        'new_value'    => $new,
        'note'         => $note !== null ? mb_substr($note, 0, 500) : null,
        'ip'           => mb_substr($_SERVER['REMOTE_ADDR'] ?? 'cli', 0, 45),
        'created_at'   => now(),
    ]);
}

function audit(string $entityType, ?int $entityId, ?string $label, string $action, ?string $note = null): void
{
    $u = user();
    audit_raw($u ? (int)$u['id'] : null, $u['name'] ?? null, $entityType, $entityId, $label, $action, null, null, null, $note);
}

/**
 * Log each field that differs between $before and $after.
 * Both arrays should hold display-friendly values keyed by field name.
 * $notes can give a per-field reason, e.g. ['due_date' => 'Client delayed input'].
 */
function audit_diff(string $entityType, int $entityId, string $label, array $before, array $after, array $notes = []): int
{
    $u = user();
    $count = 0;
    foreach ($after as $field => $new) {
        $old = $before[$field] ?? null;
        if ((string)$old === (string)$new) {
            continue;
        }
        audit_raw(
            $u ? (int)$u['id'] : null,
            $u['name'] ?? null,
            $entityType,
            $entityId,
            $label,
            'update',
            $field,
            $old === null ? null : (string)$old,
            $new === null ? null : (string)$new,
            $notes[$field] ?? null
        );
        $count++;
    }
    return $count;
}

/** Field names shown in the audit screens. */
function audit_field_label(?string $field): string
{
    $map = [
        'title' => 'Title', 'name' => 'Name', 'description' => 'Description', 'status' => 'Status',
        'priority' => 'Priority', 'weightage' => 'Weightage', 'estimated_hours' => 'Estimated hours',
        'start_date' => 'Start date', 'due_date' => 'Due date', 'target_date' => 'Target date',
        'assignee' => 'Assignee', 'manager' => 'Project manager', 'client' => 'Client',
        'department' => 'Department', 'role' => 'Role', 'reports_to' => 'Reports to', 'email' => 'Email',
        'is_active' => 'Active', 'code' => 'Code', 'parent' => 'Parent task', 'designation' => 'Designation',
        'phone' => 'Phone', 'head' => 'Department head', 'contact_person' => 'Contact person',
        'address' => 'Address', 'notes' => 'Notes', 'password' => 'Password', 'lang' => 'Language',
        'permissions' => 'Permissions', 'setting' => 'Setting', 'rating' => 'Rating',
        'member_tasks' => 'Team members can add their own tasks',
    ];
    return $field ? t($map[$field] ?? $field) : '';
}

function audit_action_label(string $action): string
{
    return t([
        'create' => 'Created', 'update' => 'Changed', 'delete' => 'Deleted', 'login' => 'Logged in',
        'logout' => 'Logged out', 'locked' => 'Locked', 'password_reset' => 'Password reset',
        'comment' => 'Commented', 'upload' => 'Uploaded file', 'delete_file' => 'Deleted file',
        'demo_load' => 'Loaded demo data', 'demo_wipe' => 'Wiped demo data',
        'start_day' => 'Started the day', 'close_day' => 'Closed the day', 'auto_close' => 'Auto-closed the day',
        'reopen_day' => 'Reopened the day', 'rate' => 'Rated', 'review' => 'Reviewed', 'finalise' => 'Finalised',
        'publish' => 'Published', 'acknowledge' => 'Acknowledged', 'backup' => 'Created backup', 'reopen' => 'Reopened',
    ][$action] ?? $action);
}

function audit_entity_label(string $type): string
{
    return t([
        'project' => 'Project', 'task' => 'Task', 'user' => 'User', 'client' => 'Client',
        'department' => 'Department', 'settings' => 'Settings', 'role' => 'Role', 'system' => 'System',
        'workday' => 'Work day', 'ai_tool' => 'AI tool', 'appraisal' => 'Appraisal', 'review' => 'Weekly review',
    ][$type] ?? $type);
}
