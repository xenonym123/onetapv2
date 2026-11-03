<form class="form" method="post" action="<?= url('/login') ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="as" value="<?= e($tab) ?>">
    <div>
        <h1>Sign in</h1>
    </div>
    <p class="lead">Are you riding or driving today?</p>
    <div class="tabs2" role="tablist">
        <a href="<?= url('/login?as=passenger') ?>" class="<?= $tab === 'passenger' ? 'on' : '' ?>" role="tab" aria-selected="<?= $tab === 'passenger' ? 'true' : 'false' ?>">Passenger</a>
        <a href="<?= url('/login?as=driver') ?>" class="<?= $tab === 'driver' ? 'on' : '' ?>" role="tab" aria-selected="<?= $tab === 'driver' ? 'true' : 'false' ?>">Driver</a>
    </div>
    <?php View::partial('flash') ?>
    <div class="field">
        <label for="username">Username</label>
        <input class="input" id="username" name="username" autocomplete="username" required value="<?= e(old('username')) ?>" autofocus>
    </div>
    <div class="field">
        <label for="password">Password</label>
        <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
    </div>
    <button class="btn lg block" type="submit">Sign in</button>
    <?php if ($tab === 'passenger'): ?>
        <p class="center muted">New passenger? <a class="linkbtn" href="<?= url('/register') ?>">Create an account</a></p>
    <?php else: ?>
        <p class="center muted small">Driver accounts are made by a 1TAP admin.</p>
    <?php endif; ?>
    <p class="center muted small">Forgot your password? Ask a 1TAP admin to reset it.</p>
    <div class="staff-note">Operator or admin? Sign in with your assigned account. 1TAP opens your dashboard either way.</div>
    <?php if (config('app.debug')): ?>
        <div class="demo">Demo accounts (password <code>1tap2026</code>): passenger <code>juandc</code>, driver <code>ramon</code>. The admin uses your existing <code>admin</code> account.</div>
    <?php endif; ?>
</form>
