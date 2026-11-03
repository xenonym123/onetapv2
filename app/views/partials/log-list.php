<?php
$meta = [
    'login' => ['login', '', 'Sign in'], 'logout' => ['logout', '', 'Sign out'], 'login_failed' => ['alert', 'red', 'Failed sign-in'],
    'register' => ['user', 'green', 'New passenger'], 'reload' => ['wallet', 'green', 'Wallet reload'],
    'fare_paid' => ['check', 'green', 'Fare paid'], 'fare_blocked' => ['alert', 'amber', 'Scan blocked'],
    'trip_start' => ['play', '', 'Trip started'], 'trip_end' => ['stop', '', 'Trip ended'],
    'unit_status' => ['bus', 'amber', 'Unit status'], 'unit_added' => ['bus', 'green', 'Unit added'],
    'route_added' => ['route', 'green', 'Route added'], 'fare_changed' => ['peso', 'amber', 'Fare changed'],
    'user_added' => ['user', 'green', 'Account added'], 'user_updated' => ['edit', '', 'Account updated'], 'user_status' => ['user', 'amber', 'Account status'],
    'profile_updated' => ['edit', '', 'Profile updated'], 'password_changed' => ['lock', '', 'Password changed'],
    'qr_frozen' => ['snow', 'red', 'QR frozen'], 'qr_unfrozen' => ['qr', 'green', 'QR unfrozen'],
];
?>
<?php if (!$logs): ?><p class="empty">Nothing logged yet.</p><?php endif; ?>
<?php foreach ($logs as $l): [$ic, $tone, $label] = $meta[$l['action']] ?? ['logs', '', ucfirst(str_replace('_', ' ', $l['action']))]; ?>
    <div class="log">
        <div class="ico <?= $tone ?>"><?= icon($ic) ?></div>
        <div class="body">
            <div class="top"><b><?= e($label) ?></b><span title="<?= e($l['logged_at']) ?>"><?= e(fmt_date($l['logged_at'], 'M j · g:i A')) ?></span></div>
            <p><?= e($l['full_name'] ?? 'System') ?><?= $l['username'] ? ' (' . e($l['username']) . ')' : '' ?> · <?= e($l['details']) ?></p>
        </div>
    </div>
<?php endforeach; ?>
