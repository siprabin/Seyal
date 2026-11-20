<?php
/** AI tools master list (ChatGPT, Gemini, Claude preloaded; admin can add more). */
declare(strict_types=1);

require_perm('settings.manage');

if (is_post()) {
    $id = input_int('id');
    $tool = $id ? (row('SELECT * FROM ai_tools WHERE id = ?', [$id]) ?? abort(404)) : null;
    switch ($action) {
        case 'save':
            $name = mb_substr(trim((string)input('name', '')), 0, 80);
            $sort = (int)input('sort_order', 0);
            if ($name === '') {
                flash('danger', t('Enter the tool name.'));
            } elseif (val('SELECT COUNT(*) FROM ai_tools WHERE name = ? AND id <> ?', [$name, $id ?? 0])) {
                flash('danger', t('A tool with this name already exists.'));
            } elseif ($tool) {
                update('ai_tools', ['name' => $name, 'sort_order' => $sort], ['id' => $tool['id']]);
                audit_diff('ai_tool', (int)$tool['id'], $name, ['name' => $tool['name']], ['name' => $name]);
                flash('success', t('Saved.'));
            } else {
                $sort = $sort ?: (int)val('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ai_tools');
                $newId = insert('ai_tools', ['name' => $name, 'sort_order' => $sort, 'created_at' => now()]);
                audit('ai_tool', $newId, $name, 'create');
                flash('success', t('AI tool added.'));
            }
            break;
        case 'toggle':
            if ($tool) {
                update('ai_tools', ['is_active' => $tool['is_active'] ? 0 : 1], ['id' => $tool['id']]);
                audit_diff('ai_tool', (int)$tool['id'], $tool['name'], ['is_active' => $tool['is_active']], ['is_active' => $tool['is_active'] ? 0 : 1]);
            }
            break;
        case 'delete':
            if ($tool) {
                if (val('SELECT COUNT(*) FROM work_log_ai_tools WHERE ai_tool_id = ?', [$tool['id']])) {
                    flash('warning', t('This tool is already used in work logs, so it cannot be deleted. Hide it instead.'));
                } else {
                    q('DELETE FROM ai_tools WHERE id = ?', [$tool['id']]);
                    audit('ai_tool', (int)$tool['id'], $tool['name'], 'delete');
                    flash('success', t('AI tool deleted.'));
                }
            }
            break;
    }
    redirect(url('aitools'));
}

$tools = rows(
    'SELECT a.*, COUNT(x.work_log_id) AS uses,
            (SELECT MAX(wl.created_at) FROM work_logs wl JOIN work_log_ai_tools y ON y.work_log_id = wl.id WHERE y.ai_tool_id = a.id) AS last_used
     FROM ai_tools a LEFT JOIN work_log_ai_tools x ON x.ai_tool_id = a.id
     GROUP BY a.id ORDER BY a.sort_order, a.name'
);
render('aitools/index', ['pageTitle' => t('AI Tools'), 'tools' => $tools]);
