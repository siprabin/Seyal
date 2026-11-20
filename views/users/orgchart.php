<?php
$renderTree = function (int $parentId, int $depth) use (&$renderTree, $children) {
    if (empty($children[$parentId]) || $depth > 20) {
        return;
    }
    echo '<ul' . ($depth === 0 ? ' class="org-tree"' : '') . '>';
    foreach ($children[$parentId] as $p) {
        echo '<li><a class="org-node text-decoration-none text-body" href="' . e(url('users', ['a' => 'view', 'id' => $p['id']])) . '">'
            . avatar($p['name'], 'avatar-sm')
            . '<span><strong>' . e($p['name']) . '</strong><br><span class="small text-muted">'
            . e(trim(($p['designation'] ?: role_label($p['role'])) . ($p['department_name'] ? ' · ' . $p['department_name'] : '')))
            . '</span></span></a>';
        $renderTree((int)$p['id'], $depth + 1);
        echo '</li>';
    }
    echo '</ul>';
};
?>
<div class="page-head">
  <h1><?= e(t('Org Chart')) ?></h1>
  <div class="actions"><a href="<?= e(url('users')) ?>" class="btn btn-outline-secondary"><i class="bi bi-people"></i> <?= e(t('Users')) ?></a></div>
</div>
<p class="text-muted small"><?= e(t('Built from each person\'s "Reports to" setting. Edit a user to change where they appear.')) ?></p>
<div class="card"><div class="card-body" style="overflow-x:auto">
  <?php if (empty($children[0])): ?><p class="text-muted mb-0"><?= e(t('No users yet.')) ?></p><?php endif; ?>
  <?php $renderTree(0, 0); ?>
</div></div>
