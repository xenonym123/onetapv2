<div class="page-head">
    <div style="display:flex;gap:14px;align-items:center;min-width:0">
        <button class="burger" type="button" aria-label="Open menu" data-open-nav><?= icon('menu') ?></button>
        <div style="min-width:0"><h1><?= e($heading) ?></h1><?php if (!empty($sub)): ?><p><?= e($sub) ?></p><?php endif; ?></div>
    </div>
    <?php if (!empty($tools)): ?><div class="tools"><?= $tools ?></div><?php endif; ?>
</div>
