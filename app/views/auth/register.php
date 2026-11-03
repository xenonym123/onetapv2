<?php $err = fn($k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : ''; $cls = fn($k) => isset($errors[$k]) ? ' has-err' : ''; ?>
<form class="form" method="post" action="<?= url('/register') ?>" novalidate>
    <?= csrf_field() ?>
    <h1>Create an account</h1>
    <p class="lead">For passengers. Your wallet and ride QR are made for you.</p>
    <?php View::partial('flash') ?>
    <div class="form-grid">
        <div class="field full<?= $cls('full_name') ?>"><label for="full_name">Full name</label>
            <input class="input" id="full_name" name="full_name" autocomplete="name" required value="<?= e(old('full_name')) ?>"><?= $err('full_name') ?></div>
        <div class="field<?= $cls('email') ?>"><label for="email">Email</label>
            <input class="input" id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>"><?= $err('email') ?></div>
        <div class="field<?= $cls('phone') ?>"><label for="phone">Mobile number</label>
            <input class="input" id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="09171234567" required value="<?= e(old('phone')) ?>"><?= $err('phone') ?></div>
        <div class="field full<?= $cls('username') ?>"><label for="username">Username</label>
            <input class="input" id="username" name="username" autocomplete="username" required value="<?= e(old('username')) ?>"><?= $err('username') ?></div>
        <div class="field<?= $cls('password') ?>"><label for="password">Password</label>
            <input class="input" id="password" name="password" type="password" autocomplete="new-password" required><span class="hint">At least 8 characters</span><?= $err('password') ?></div>
        <div class="field<?= $cls('password_confirm') ?>"><label for="password_confirm">Confirm password</label>
            <input class="input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required><?= $err('password_confirm') ?></div>
    </div>
    <button class="btn lg block" type="submit">Create account</button>
    <p class="center muted">Already have one? <a class="linkbtn" href="<?= url('/login') ?>">Sign in</a></p>
</form>
