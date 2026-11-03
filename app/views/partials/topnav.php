<header class="topnav" id="topnav">
    <div class="wrap">
        <a href="<?= url('/') ?>" aria-label="1TAP home"><?php View::partial('logo') ?></a>
        <nav aria-label="Main">
            <a href="<?= url('/') ?>" class="<?= ($page ?? '') === 'home' ? 'on' : '' ?>">Home</a>
            <a href="<?= url('/about') ?>" class="<?= ($page ?? '') === 'about' ? 'on' : '' ?>">About Us</a>
            <a href="<?= url('/pay-with-qr') ?>" class="<?= ($page ?? '') === 'qr' ? 'on' : '' ?>">Pay with QR</a>
        </nav>
        <div class="actions">
            <?php if ($u = Auth::user()): ?>
                <a class="btn" href="<?= url(Auth::home()) ?>">My dashboard</a>
            <?php else: ?>
                <a class="btn ghost" href="<?= url('/login') ?>">Login</a>
                <a class="btn" href="<?= url('/register') ?>">Create account</a>
            <?php endif; ?>
            <button class="navtoggle" type="button" aria-label="Menu" aria-expanded="false" data-toggle-nav><?= icon('menu') ?></button>
        </div>
    </div>
</header>
