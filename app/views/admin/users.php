<?php View::partial('page-head', ['heading' => 'User management', 'sub' => 'Passengers, drivers and admins',
    'tools' => '<a class="btn ghost" href="' . url('/admin/users/export') . '">' . icon('download') . 'Export CSV</a><a class="btn" href="' . url('/admin/users/new') . '">' . icon('plus') . 'Add user</a>']) ?>
<?php View::partial('flash') ?>
<div class="card">
    <div class="head">
        <div class="filters">
            <?php foreach (['' => 'All', 'passenger' => 'Passengers', 'driver' => 'Drivers', 'admin' => 'Admins'] as $k => $label): ?>
                <a class="<?= $role === $k ? 'on' : '' ?>" href="<?= url('/admin/users' . ($k ? '?role=' . $k : '')) ?>"><?= e($label) ?><?= $k ? ' · ' . (int) ($counts[$k] ?? 0) : '' ?></a>
            <?php endforeach; ?>
        </div>
        <form class="searchbar" method="get" action="<?= url('/admin/users') ?>">
            <?php if (!config('app.pretty_urls')): ?><input type="hidden" name="url" value="admin/users"><?php endif; ?>
            <?php if ($role): ?><input type="hidden" name="role" value="<?= e($role) ?>"><?php endif; ?>
            <label class="sr-only" for="q">Search</label><input class="input" id="q" name="q" placeholder="Search name, username, email" value="<?= e($q) ?>">
            <button class="btn ghost sm" type="submit">Search</button>
        </form>
    </div>
    <?php if (!$users): ?><p class="empty">No accounts match.</p><?php else: ?>
    <div class="table-scroll"><table class="data">
        <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Wallet</th><th>Last sign-in</th><th>Status</th><th class="r">Actions</th></tr></thead>
        <tbody><?php foreach ($users as $u): ?>
            <tr>
                <td class="num muted"><?= str_pad((string) $u['user_id'], 3, '0', STR_PAD_LEFT) ?></td>
                <td><b><?= e($u['full_name']) ?></b><br><span class="muted small"><?= e($u['username']) ?> · <?= e($u['email']) ?></span></td>
                <td><?= e(ucfirst($u['role_name'])) ?></td>
                <td class="num"><?= $u['balance'] !== null ? peso($u['balance']) . ($u['wallet_status'] === 'frozen' ? ' ' . pill('frozen') : '') : '<span class="muted">—</span>' ?></td>
                <td class="small muted"><?= e(time_ago($u['last_login'])) ?></td>
                <td><?= pill($u['status']) ?></td>
                <td class="r" style="white-space:nowrap">
                    <a class="btn sm ghost" href="<?= url('/admin/users/' . $u['user_id']) ?>"><?= icon('edit') ?>Edit</a>
                    <?php if ((int) $u['user_id'] !== (int) $me['id']): ?>
                    <form class="inline-form" method="post" action="<?= url('/admin/users/' . $u['user_id'] . '/status') ?>"
                          <?= $u['status'] === 'active' ? 'data-confirm="Deactivate ' . e($u['full_name']) . '? They won\'t be able to sign in."' : '' ?>>
                        <?= csrf_field() ?><button class="btn sm <?= $u['status'] === 'active' ? 'danger' : 'ghost' ?>" type="submit"><?= $u['status'] === 'active' ? 'Deactivate' : 'Reactivate' ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
    <p class="hint" style="margin-top:14px">Accounts are deactivated instead of deleted, so their rides, fares and logs stay on record.</p>
</div>
