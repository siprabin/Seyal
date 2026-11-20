<?php
/**
 * Kanban board. Expects $tasks (with sub_count, comment_count, file_count, parent_title,
 * assignee_name) and $canMove (callable task => bool). Optional $showProject.
 */
$showProject = $showProject ?? false;
$columns = array_fill_keys(array_keys(task_statuses()), []);
foreach ($tasks as $t) {
    $columns[$t['status']][] = $t;
}
?>
<p class="small text-muted"><i class="bi bi-arrows-move"></i> <?= e(t('Drag a card to another column to change its status. You can move tasks assigned to you; managers can move any task.')) ?></p>
<div class="kanban" id="kanban" data-status-url="<?= e(url('tasks', ['a' => 'status'])) ?>">
  <?php foreach (task_statuses() as $k => $s): ?>
    <div class="kanban-col">
      <div class="kanban-col-head"><?= badge(task_statuses(), $k) ?><span class="count"><?= count($columns[$k]) ?></span></div>
      <div class="kanban-list" data-status="<?= e($k) ?>">
        <?php foreach ($columns[$k] as $t):
          $movable = $canMove($t);
          $overdue = $t['status'] !== 'done' && $t['due_date'] && $t['due_date'] < today(); ?>
          <div class="kanban-card border-status-<?= e($t['status']) ?><?= $movable ? '' : ' locked' ?>" data-id="<?= (int)$t['id'] ?>">
            <?php if ($showProject): ?><div class="parent text-truncate"><i class="bi bi-kanban"></i> <?= e($t['project_name']) ?></div><?php endif; ?>
            <?php if ($t['parent_title']): ?><div class="parent text-truncate">↳ <?= e($t['parent_title']) ?></div><?php endif; ?>
            <a href="<?= e(url('tasks', ['a' => 'view', 'id' => $t['id']])) ?>" class="fw-semibold d-block"><?= e($t['title']) ?></a>
            <div class="meta">
              <?php if (!$showProject): ?><?= avatar($t['assignee_name'], 'avatar-sm') ?><?php endif; ?>
              <?= badge(priorities(), $t['priority']) ?>
              <?php if ($t['due_date']): ?><span class="<?= $overdue ? 'text-overdue' : '' ?>"><i class="bi bi-calendar"></i> <?= e(fmt_date($t['due_date'])) ?></span><?php endif; ?>
              <span class="ms-auto"><?= weight_stars((int)$t['weightage']) ?></span>
            </div>
            <?php if ($t['sub_count'] || $t['comment_count'] || $t['file_count']): ?>
              <div class="meta">
                <?php if ($t['sub_count']): ?><span><i class="bi bi-diagram-2"></i> <?= (int)$t['sub_done'] ?>/<?= (int)$t['sub_count'] ?></span><?php endif; ?>
                <?php if ($t['comment_count']): ?><span><i class="bi bi-chat"></i> <?= (int)$t['comment_count'] ?></span><?php endif; ?>
                <?php if ($t['file_count']): ?><span><i class="bi bi-paperclip"></i> <?= (int)$t['file_count'] ?></span><?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
