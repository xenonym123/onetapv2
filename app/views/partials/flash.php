<?php foreach (['success' => 'check', 'error' => 'alert'] as $type => $ic): ?>
    <?php if ($msg = flash($type)): ?>
        <div class="flash <?= $type ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>"><?= icon($ic) ?><span><?= e($msg) ?></span></div>
    <?php endif; ?>
<?php endforeach; ?>
