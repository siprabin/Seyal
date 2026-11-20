<?php
require __DIR__ . '/_header.php';
$canMove = fn($t) => $canManage || (int)$t['assignee_id'] === uid();
require APP_ROOT . '/views/partials/kanban.php';
