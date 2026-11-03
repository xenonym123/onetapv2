<!doctype html>
<html lang="en">
<head><?php View::partial('head', ['title' => $title ?? '']) ?></head>
<body class="pub">
<?php View::partial('topnav', ['page' => $page ?? '']) ?>
<?php if (!empty($_SESSION['flash'])): ?><div class="wrap" style="padding-top:16px"><?php View::partial('flash') ?></div><?php endif; ?>
<main><?= $content ?></main>
<?php View::partial('footer') ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
