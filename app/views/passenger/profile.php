<?php
$err = fn($k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
$cls = fn($k) => isset($errors[$k]) ? ' has-err' : '';
$val = fn($k) => e(old($k, (string) $account[$k]));
?>
<?php View::partial('page-head', ['heading' => 'Profile', 'sub' => 'Your account details and your ride QR']) ?>
<?php View::partial('flash') ?>
<div class="row cols-main">
    <form class="card form-stack" method="post" action="<?= url('/passenger/profile') ?>" novalidate>
        <?= csrf_field() ?>
        <h3 style="margin:0">Account details</h3>
        <div class="form-grid">
            <div class="field<?= $cls('full_name') ?>"><label for="full_name">Full name</label><input class="input" id="full_name" name="full_name" value="<?= $val('full_name') ?>"><?= $err('full_name') ?></div>
            <div class="field<?= $cls('username') ?>"><label for="username">Username</label><input class="input" id="username" name="username" value="<?= $val('username') ?>"><?= $err('username') ?></div>
            <div class="field<?= $cls('email') ?>"><label for="email">Email</label><input class="input" id="email" name="email" type="email" value="<?= $val('email') ?>"><?= $err('email') ?></div>
            <div class="field<?= $cls('phone') ?>"><label for="phone">Mobile number</label><input class="input" id="phone" name="phone" type="tel" value="<?= $val('phone') ?>"><?= $err('phone') ?></div>
        </div>
        <h3 style="margin:6px 0 0">Change password</h3>
        <p class="hint" style="margin-top:-8px">Leave these empty to keep your current password.</p>
        <div class="form-grid">
            <div class="field<?= $cls('password') ?>"><label for="new_password">New password</label><input class="input" id="new_password" name="new_password" type="password" autocomplete="new-password"><?= $err('password') ?></div>
            <div class="field<?= $cls('password_confirm') ?>"><label for="new_password_confirm">Confirm new password</label><input class="input" id="new_password_confirm" name="new_password_confirm" type="password" autocomplete="new-password"><?= $err('password_confirm') ?></div>
        </div>
        <div class="field<?= $cls('current_password') ?>" style="max-width:420px"><label for="current_password">Current password</label>
            <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required><span class="hint">Needed to save any change</span><?= $err('current_password') ?></div>
        <div><button class="btn" type="submit">Save changes</button></div>
    </form>
    <div class="card" style="display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px">
        <h3 style="align-self:flex-start;margin:0">Your ride QR</h3>
        <div class="qr-frame<?= $wallet['status'] === 'frozen' ? ' frozen' : '' ?>"><span id="profileQR"></span></div>
        <p style="font-size:15px">Linked to your wallet. It replaces the old 1TAP card number, so there's nothing to type in.</p>
        <p style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center"><span class="pill pill-brand" data-qr-countdown>Loading…</span><?= pill($wallet['status']) ?></p>
        <a class="btn ghost" href="<?= url('/passenger/qr') ?>"><?= $wallet['status'] === 'active' ? 'Lost your phone? Freeze your QR' : 'Unfreeze your QR' ?></a>
        <p class="hint">Member since <?= e(fmt_date($account['created_at'])) ?></p>
    </div>
</div>
<script src="<?= asset('vendor/qrcode.js') ?>"></script>
<script>document.addEventListener('DOMContentLoaded', () => ONETAP.liveQR({ url: '<?= e(url('/passenger/qr/token')) ?>', target: '#profileQR' }));</script>
