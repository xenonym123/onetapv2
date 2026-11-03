<!doctype html>
<html lang="en">
<head><?php View::partial('head', ['title' => $title ?? '']) ?></head>
<body>
<div class="auth">
    <section class="side">
        <a href="<?= url('/') ?>" aria-label="1TAP home"><?php View::partial('logo', ['white' => true]) ?></a>
        <h2>Scan anytime,<br>ride anywhere.</h2>
        <p>Pay your fare with a scan, see where your jeepney is, and know if there's still a seat.</p>
    </section>
    <section class="pane"><?= $content ?></section>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
