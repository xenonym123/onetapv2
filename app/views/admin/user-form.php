<?php
$editing = $account !== null;
$err = fn($k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
$cls = fn($k) => isset($errors[$k]) ? ' has-err' : '';
$val = fn($k) => e(old($k, $editing ? (string) $account[$k] : ''));
$roleNow = old('role', $editing ? $account['role_name'] : 'driver');
$self = $editing && (int) $account['user_id'] === (int) $me['id'];
?>
<?php View::partial('page-head', ['heading' => $editing ? 'Edit account' : 'Add user', 'sub' => $editing ? $account['full_name'] . ' · ' . $account['username'] : 'Create a driver, admin or passenger account',
    'tools' => '<a class="btn ghost" href="' . url('/admin/users') . '">Back to users</a>']) ?>
<?php View::partial('flash') ?>
<div class="row cols-main">
    <form class="card form-stack" method="post" action="<?= url($editing ? '/admin/users/' . $account['user_id'] : '/admin/users/new') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field full<?= $cls('full_name') ?>"><label for="full_name">Full name</label><input class="input" id="full_name" name="full_name" value="<?= $val('full_name') ?>"><?= $err('full_name') ?></div>
            <div class="field<?= $cls('email') ?>"><label for="email">Email</label><input class="input" id="email" name="email" type="email" value="<?= $val('email') ?>"><?= $err('email') ?></div>
            <div class="field<?= $cls('phone') ?>"><label for="phone">Mobile number</label><input class="input" id="phone" name="phone" type="tel" placeholder="09171234567" value="<?= $val('phone') ?>"><?= $err('phone') ?></div>
            <div class="field<?= $cls('username') ?>"><label for="username">Username</label><input class="input" id="username" name="username" value="<?= $val('username') ?>"><?= $err('username') ?></div>
            <div class="field"><label for="role">Role</label>
                <select class="input" id="role" name="role" <?= $self ? 'disabled' : '' ?>>
                    <?php foreach (['driver' => 'Driver', 'admin' => 'Admin (operator side)', 'passenger' => 'Passenger'] as $k => $l): ?><option value="<?= $k ?>" <?= $roleNow === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                </select><?php if ($self): ?><span class="hint">You can't change your own role.</span><?php endif; ?></div>
            <div class="field full<?= $cls('password') ?>"><label for="password"><?= $editing ? 'New password' : 'Password' ?></label>
                <input class="input" id="password" name="password" type="text" autocomplete="new-password" placeholder="<?= $editing ? 'Leave empty to keep the current one' : 'At least 8 characters' ?>">
                <span class="hint"><?= $editing ? 'Type one here to reset it, then tell the user.' : 'Give this to the person so they can sign in.' ?></span><?= $err('password') ?></div>
        </div>
        <div><button class="btn" type="submit"><?= $editing ? 'Save changes' : 'Create account' ?></button></div>
    </form>
    <div class="card">
        <?php if ($editing): ?>
            <h3>Account</h3>
            <div class="list-row"><span>Status</span><?= pill($account['status']) ?></div>
            <div class="list-row"><span>Created</span><span><?= e(fmt_date($account['created_at'])) ?></span></div>
            <?php if (!empty($wallet)): ?>
                <div class="list-row"><span>Wallet balance</span><b class="num"><?= peso($wallet['balance']) ?></b></div>
                <div class="list-row"><span>Ride QR</span><?= pill($wallet['status']) ?></div>
            <?php endif; ?>
        <?php else: ?>
            <h3>Who can sign up on their own?</h3>
            <p class="muted">Only passengers. Drivers and admins are added here, so nobody can make themselves a driver or an admin from the public site.</p>
            <p class="muted" style="margin-top:10px">Admins also see the operator side: fares per unit, capacity, and the fleet.</p>
        <?php endif; ?>
    </div>
</div>
