<?php
/** One audit row. Expects $h (audit_log row). Optional $showEntity (default true). */
$showEntity = $showEntity ?? true;
$fmtVal = function ($field, $v) {
    if ($v === null || $v === '') {
        return '—';
    }
    if ($field === 'status') {
        return task_statuses()[$v]['label'] ?? project_statuses()[$v]['label'] ?? $v;
    }
    if ($field === 'priority') {
        return priorities()[$v]['label'] ?? $v;
    }
    if (in_array($field, ['due_date', 'start_date', 'target_date'], true)) {
        return fmt_date($v);
    }
    if ($field === 'is_active') {
        return $v ? t('Yes') : t('No');
    }
    if ($field === 'description' || $field === 'notes') {
        return mb_strimwidth($v, 0, 80, '…');
    }
    return $v;
};
$isDue = in_array($h['field'], ['due_date', 'target_date'], true);
$entityUrl = null;
if ($h['entity_id'] && $h['action'] !== 'delete') {
    $entityUrl = match ($h['entity_type']) {
        'task' => url('tasks', ['a' => 'view', 'id' => $h['entity_id']]),
        'project' => url('projects', ['a' => 'view', 'id' => $h['entity_id']]),
        'client' => can('clients.view') ? url('clients', ['a' => 'view', 'id' => $h['entity_id']]) : null,
        'user' => can('users.view') ? url('users', ['a' => 'view', 'id' => $h['entity_id']]) : null,
        'workday' => url('team', ['a' => 'day', 'id' => $h['entity_id']]),
        default => null,
    };
}
?>
<div class="history-item<?= $isDue ? ' due-change px-2 rounded' : '' ?>">
  <span class="text-muted" title="<?= e(fmt_datetime($h['created_at'])) ?>"><?= e(fmt_datetime($h['created_at'])) ?></span> ·
  <strong><?= e($h['user_name'] ?: t('System')) ?></strong>
  <?= e(mb_strtolower(audit_action_label($h['action']))) ?>
  <?php if ($showEntity): ?>
    <?= e(mb_strtolower(audit_entity_label($h['entity_type']))) ?>
    <?php if ($entityUrl): ?><a href="<?= e($entityUrl) ?>"><?= e($h['entity_label']) ?></a><?php else: ?><em><?= e($h['entity_label']) ?></em><?php endif; ?>
  <?php endif; ?>
  <?php if ($h['field']): ?>
    — <?= e(audit_field_label($h['field'])) ?>:
    <span class="diff-old"><?= e($fmtVal($h['field'], $h['old_value'])) ?></span>
    <i class="bi bi-arrow-right small"></i>
    <span class="diff-new"><?= e($fmtVal($h['field'], $h['new_value'])) ?></span>
  <?php endif; ?>
  <?php if ($h['note']): ?>
    <div class="small text-body-secondary ms-3"><i class="bi bi-chat-left-quote"></i> <?= e($h['note']) ?></div>
  <?php endif; ?>
</div>
