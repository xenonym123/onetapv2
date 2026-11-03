<?php
$menus = [
    'passenger' => [['/passenger', 'home', 'Home', false], ['/passenger/qr', 'qr', 'My QR', false], ['/passenger/track', 'map', 'Track a Jeepney', false],
                    ['/passenger/rides', 'history', 'Ride History', false], ['/passenger/reload', 'wallet', 'Reload Wallet', false], ['/passenger/profile', 'user', 'Profile', false]],
    'driver'    => [['/driver', 'gauge', 'Home', false], ['/driver/count', 'users', 'Passenger Count', false], ['/scan', 'scan', 'QR Scanner', false],
                    ['/driver/earnings', 'peso', 'Earnings', false], ['/driver/route', 'route', 'Routes', false]],
    'admin'     => [['/admin', 'chart', 'Analytics', false], ['/admin/users', 'users', 'User Management', true], ['/admin/tracking', 'bus', 'Jeepney Tracking', false],
                    ['/admin/reports', 'receipt', 'Fare Reports', false], ['/admin/logs', 'logs', 'System Logs', false]],
];
$roleLabel = ['passenger' => 'Passenger', 'driver' => 'Driver', 'admin' => 'Admin · Operator'][$me['role']] ?? '';
?>
<aside class="sidebar" id="sidebar">
    <a href="<?= url(Auth::home()) ?>" aria-label="1TAP dashboard"><?php View::partial('logo', ['white' => true]) ?></a>
    <nav aria-label="Dashboard">
        <?php foreach ($menus[$me['role']] ?? [] as [$path, $ic, $label, $prefix]): ?>
            <a href="<?= url($path) ?>" class="<?= is_active($path, $prefix) ? 'on' : '' ?>"><?= icon($ic) ?><span><?= e($label) ?></span></a>
        <?php endforeach; ?>
    </nav>
    <div class="spacer"></div>
    <form method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?><button class="logout" type="submit"><?= icon('logout') ?>Log out</button></form>
    <div class="me">
        <div class="av"><?= e(initials($me['full_name'])) ?></div>
        <div><b><?= e($me['full_name']) ?></b><span><?= e($roleLabel) ?></span></div>
    </div>
</aside>
<div class="scrim" data-close-nav></div>
