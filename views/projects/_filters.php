<?php
/**
 * Task filters for a project's List and Board views: search plus Status / Assignee / Priority / Due,
 * each allowing several choices. Expects $project, $filters, $assigneeChoices, $tab ('view' or 'board').
 * Sets $filtered for the page.
 */
$filtered = $filters['q'] !== '' || $filters['status'] || $filters['assignee'] || $filters['priority'] || $filters['due'];
?>
<form method="get" action="<?= e(url('projects')) ?>" class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <input type="hidden" name="p" value="projects"><input type="hidden" name="a" value="<?= e($tab) ?>"><input type="hidden" name="id" value="<?= (int)$project['id'] ?>">
  <div class="input-group input-group-sm" style="max-width:240px">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input type="search" class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(t('Search tasks')) ?>" aria-label="<?= e(t('Search tasks')) ?>">
  </div>
  <?php
  foreach ([
      ['status', t('Status'), array_map(fn($s) => $s['label'], task_statuses())],
      ['assignee', t('Assignee'), $assigneeChoices],
      ['priority', t('Priority'), array_map(fn($s) => $s['label'], priorities())],
      ['due', t('Due'), due_filter_choices()],
  ] as [$msName, $msLabel, $msOptions]):
      $msSelected = $filters[$msName]; $msSearch = null;
      require APP_ROOT . '/views/partials/multi_select.php';
  endforeach; ?>
  <?php if ($filtered): ?>
    <a class="btn btn-sm btn-link" href="<?= e(url('projects', ['a' => $tab, 'id' => $project['id']])) ?>"><i class="bi bi-x-circle"></i> <?= e(t('Clear filters')) ?></a>
  <?php endif; ?>
</form>
