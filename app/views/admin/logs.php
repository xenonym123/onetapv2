<?php View::partial('page-head', ['heading' => 'System logs', 'sub' => number_format($total) . ' entries',
    'tools' => '<a class="btn ghost" href="' . url('/admin/logs' . ($group ? '?type=' . $group : '')) . '">' . icon('refresh') . 'Refresh</a>']) ?>
<div class="filters">
    <a class="<?= $group === '' ? 'on' : '' ?>" href="<?= url('/admin/logs') ?>">All activity</a>
    <?php foreach ($groups as $k => [$label]): ?><a class="<?= $group === $k ? 'on' : '' ?>" href="<?= url('/admin/logs?type=' . $k) ?>"><?= e($label) ?></a><?php endforeach; ?>
</div>
<div class="card">
    <?php View::partial('log-list', ['logs' => $logs]) ?>
    <?php if ($pages > 1): $q = $group ? '&type=' . $group : ''; ?>
        <div class="pager">
            <?php if ($page > 1): ?><a class="btn sm ghost" href="<?= url('/admin/logs?page=' . ($page - 1) . $q) ?>">Newer</a><?php endif; ?>
            <span class="muted small">Page <?= $page ?> of <?= $pages ?></span>
            <?php if ($page < $pages): ?><a class="btn sm ghost" href="<?= url('/admin/logs?page=' . ($page + 1) . $q) ?>">Older</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
