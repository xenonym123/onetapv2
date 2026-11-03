<!doctype html>
<html lang="en">
<head>
<?php View::partial('head', ['title' => $title ?? '']) ?>
<?php if (!empty($leaflet)): ?><link rel="stylesheet" href="<?= asset('vendor/leaflet/leaflet.css') ?>"><?php endif; ?>
</head>
<body>
<div class="app">
    <?php View::partial('sidebar', ['me' => $me]) ?>
    <main class="main">
        <?= $content ?>
    </main>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (!empty($leaflet)): ?><script src="<?= asset('vendor/leaflet/leaflet.js') ?>"></script><?php endif; ?>
<?php foreach ($scripts ?? [] as $s): ?><script src="<?= asset($s) ?>"></script><?php endforeach; ?>
<?php if (($me['role'] ?? '') === 'driver' && !empty($trip)): ?><script>ONETAP.gpsBeacon('<?= e(url('/driver/gps')) ?>');</script><?php endif; ?>
</body>
</html>
