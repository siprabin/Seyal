<?php
/** Download / delete task attachments. Files live outside direct web access (uploads/.htaccess). */
declare(strict_types=1);

$file = row('SELECT * FROM attachments WHERE id = ?', [(int)input('id')]);
if (!$file) {
    abort(404);
}
[$task, $project] = find_task_or_404((int)$file['task_id']);
$path = APP_ROOT . '/uploads/' . basename($file['stored_name']);

if ($action === 'delete') {
    if (!is_post()) {
        abort(405);
    }
    if ((int)$file['user_id'] !== uid() && !can_manage_project($project)) {
        abort(403);
    }
    @unlink($path);
    q('DELETE FROM attachments WHERE id = ?', [$file['id']]);
    audit('task', (int)$task['id'], $task['title'], 'delete_file', $file['original_name']);
    flash('success', t('File deleted.'));
    redirect(url('tasks', ['a' => 'view', 'id' => $task['id']]) . '#files');
}

if (!is_file($path)) {
    abort(404, t('The file is missing on the server.'));
}
// Only show common image types and PDFs inline; everything else downloads.
$inlineTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'application/pdf'];
$mime = $file['mime'] ?: 'application/octet-stream';
$inline = in_array($mime, $inlineTypes, true);
$name = str_replace(['"', "\r", "\n"], '', $file['original_name']);

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode($file['original_name']));
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;
