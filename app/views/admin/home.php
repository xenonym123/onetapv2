<?php
$delta = function ($now, $before) {
    if (!$before) return 'No data for yesterday';
    $p = round(($now - $before) / $before * 100);
    return ($p >= 0 ? '+' : '') . $p . '% vs yesterday';
};
?>
<?php View::partial('page-head', ['heading' => 'Analytics', 'sub' => 'System performance, fares and fleet at a glance',
    'tools' => '<span class="pill pill-brand">' . icon('clock') . 'Today · ' . date('g:i A') . '</span>']) ?>
<?php View::partial('flash') ?>
<div class="row cols-4">
    <div class="card stat-card"><div class="label">Units on the road<?= icon('bus') ?></div><div class="value"><?= $onRoad ?> <small>of <?= count($fleet) ?></small></div>
        <div class="sub"><?= count(array_filter($fleet, fn($u) => $u['state'] === 'breakdown')) ?> breakdown · <?= count(array_filter($fleet, fn($u) => $u['state'] === 'offline')) ?> offline</div></div>
    <div class="card stat-card"><div class="label">Paid rides today<?= icon('users') ?></div><div class="value"><?= (int) $today['paid'] ?></div>
        <div class="sub"><?= e($delta((int) $today['paid'], (int) $yday['paid'] - (int) $today['paid'])) ?></div></div>
    <div class="card stat-card"><div class="label">Fares today<?= icon('peso') ?></div><div class="value"><?= peso($today['revenue'], 0) ?></div>
        <div class="sub"><?= $today['paid'] ? 'Average ' . peso($today['revenue'] / $today['paid']) : 'No fares yet' ?></div></div>
    <div class="card stat-card"><div class="label">Scans blocked at full<?= icon('alert') ?></div><div class="value amber"><?= (int) $today['blocked_full'] ?></div>
        <div class="sub">Riders turned away today</div></div>
</div>

<div class="row cols-wide">
    <div class="card">
        <div class="head"><h3>Live passenger count</h3><a class="btn sm ghost" href="<?= url('/admin/tracking') ?>"><?= icon('map') ?>Map</a></div>
        <div class="bars">
            <?php foreach ($fleet as $u): ?>
                <?php if ($u['state'] === 'on_trip'): $pct = round($u['onboard'] / max(1, $u['capacity']) * 100); ?>
                    <div class="bar-row"><b><?= e($u['unit_code']) ?></b><div class="bar" title="<?= e($u['route_name']) ?>"><i class="<?= $pct >= 100 ? 'full' : '' ?>" style="width:<?= min(100, $pct) ?>%"></i></div><span class="num"><?= (int) $u['onboard'] ?>/<?= (int) $u['capacity'] ?></span></div>
                <?php else: ?>
                    <div class="bar-row"><b><?= e($u['unit_code']) ?></b><span class="muted small"><?= strip_tags(pill($u['state'])) ?></span><span></span></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <p class="hint" style="margin-top:16px"><?= $riding ?> people riding right now across <?= $onRoad ?> units.</p>
    </div>
    <div class="card">
        <h3>Today's totals</h3>
        <?php $max = max(1, (float) $today['revenue'], (float) $reloads); ?>
        <div class="bars">
            <div><div class="list-row" style="border:0;padding-bottom:6px"><span>Fares collected (to operators)</span><b class="num"><?= peso($today['revenue']) ?></b></div><div class="bar"><i style="width:<?= round($today['revenue'] / $max * 100) ?>%"></i></div></div>
            <div><div class="list-row" style="border:0;padding-bottom:6px"><span>Wallet reloads (<?= (int) $reloadsN ?>)</span><b class="num"><?= peso($reloads) ?></b></div><div class="bar"><i style="width:<?= round($reloads / $max * 100) ?>%;background:var(--green)"></i></div></div>
            <div><div class="list-row" style="border:0;padding-bottom:6px"><span>Scans blocked (all reasons)</span><b class="num"><?= (int) $today['blocked'] ?></b></div></div>
        </div>
        <h3 style="margin-top:22px">Accounts</h3>
        <div class="list-row"><span>Passengers</span><b class="num"><?= (int) ($users['passenger'] ?? 0) ?></b></div>
        <div class="list-row"><span>Drivers</span><b class="num"><?= (int) ($users['driver'] ?? 0) ?></b></div>
        <div class="list-row"><span>Admins</span><b class="num"><?= (int) ($users['admin'] ?? 0) ?></b></div>
    </div>
</div>

<div class="card">
    <div class="head"><h3>Recent activity</h3><a class="btn sm ghost" href="<?= url('/admin/logs') ?>">All logs</a></div>
    <?php View::partial('log-list', ['logs' => $activity]) ?>
</div>
