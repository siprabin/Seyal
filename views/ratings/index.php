<div class="page-head"><h1><?= e(t('Task ratings')) ?></h1></div>
<p class="text-muted small"><?= e(t('Rate the quality of finished tasks done by people who report to you (1 = poor, 5 = excellent). Whether a task was on time is worked out automatically from its due date.')) ?></p>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link<?= $tab === 'todo' ? ' active' : '' ?>" href="<?= e(url('ratings')) ?>"><?= e(t('To rate')) ?>
    <?php if ($todo): ?><span class="badge bg-danger ms-1"><?= count($todo) ?></span><?php endif; ?></a></li>
  <li class="nav-item"><a class="nav-link<?= $tab === 'rated' ? ' active' : '' ?>" href="<?= e(url('ratings', ['tab' => 'rated'])) ?>"><?= e(t('Rated by me')) ?></a></li>
</ul>

<?php if ($tab === 'todo'): ?>
  <?php if (!$todo): ?>
    <div class="card"><div class="card-body text-center text-muted py-5"><i class="bi bi-emoji-smile fs-1 d-block mb-2"></i><?= e(t('All finished tasks are rated.')) ?></div></div>
  <?php else: ?>
  <div class="list-group">
    <?php foreach ($todo as $tk):
      $completed = substr((string)$tk['completed_at'], 0, 10);
      $late = $tk['due_date'] && $completed > $tk['due_date']; ?>
      <div class="list-group-item">
        <div class="row g-2 align-items-center">
          <div class="col-lg-6 min-w-0">
            <a href="<?= e(url('tasks', ['a' => 'view', 'id' => $tk['id']])) ?>" class="fw-semibold text-decoration-none"><?= e($tk['title']) ?></a>
            <div class="small text-muted"><?= avatar($tk['assignee_name'], 'avatar-sm') ?> <?= e($tk['assignee_name']) ?> · <?= e($tk['project_name']) ?></div>
            <div class="small mt-1">
              <?= weight_stars((int)$tk['weightage']) ?>
              <span class="ms-2"><?= e(t('Completed')) ?>: <?= e(fmt_date($completed)) ?></span>
              <?php if ($tk['due_date']): ?><span class="ms-2"><?= e(t('Due')) ?>: <?= e(fmt_date($tk['due_date'])) ?></span><?php endif; ?>
              <?= $late ? '<span class="badge bg-danger-subtle text-danger-emphasis ms-2">' . e(t('Late')) . '</span>' : '<span class="badge bg-success-subtle text-success-emphasis ms-2">' . e(t('On time')) . '</span>' ?>
            </div>
          </div>
          <div class="col-lg-6"><?php $rateTask = $tk; $current = null; require APP_ROOT . '/views/partials/rate_form.php'; ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php else: ?>
  <div class="card table-card">
    <table class="table table-sm table-hover mb-0">
      <thead class="table-light"><tr><th class="ps-3"><?= e(t('Task')) ?></th><th><?= e(t('Assignee')) ?></th><th><?= e(t('Quality')) ?></th><th><?= e(t('On time')) ?></th><th class="d-none d-md-table-cell"><?= e(t('Completed')) ?></th></tr></thead>
      <tbody>
      <?php foreach ($rated as $r): ?>
        <tr>
          <td class="ps-3"><a href="<?= e(url('tasks', ['a' => 'view', 'id' => $r['task_id']])) ?>" class="text-decoration-none"><?= e($r['title']) ?></a><div class="small text-muted"><?= e($r['project_name']) ?></div>
            <?php if ($r['comment']): ?><div class="small fst-italic"><?= e($r['comment']) ?></div><?php endif; ?></td>
          <td class="small"><?= e($r['assignee_name']) ?></td>
          <td><?= rating_stars((int)$r['quality']) ?></td>
          <td><?= $r['on_time'] ? '<span class="text-success">' . e(t('Yes')) . '</span>' : '<span class="text-danger">' . e(days_late_label((int)$r['days_late'])) . '</span>' ?></td>
          <td class="d-none d-md-table-cell small"><?= e(fmt_date($r['completed_on'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rated): ?><tr><td colspan="5" class="text-center text-muted py-4"><?= e(t('Nothing here.')) ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
