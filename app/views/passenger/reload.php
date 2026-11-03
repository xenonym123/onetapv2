<?php View::partial('page-head', ['heading' => 'Reload wallet', 'sub' => 'Balance: ' . peso($wallet['balance'])]) ?>
<?php View::partial('flash') ?>
<div class="row cols-2">
    <div class="card">
        <h3>Add money</h3>
        <form method="post" action="<?= url('/passenger/reload') ?>" class="form-stack" id="reloadForm">
            <?= csrf_field() ?>
            <div class="amounts" role="radiogroup" aria-label="Amount">
                <?php foreach ([50, 100, 200, 500] as $i => $a): ?>
                    <label><input type="radio" name="preset" value="<?= $a ?>" <?= $i === 1 ? 'checked' : '' ?>><span>₱<?= $a ?></span></label>
                <?php endforeach; ?>
            </div>
            <div class="field"><label for="amount">Amount (₱)</label>
                <input class="input num" id="amount" name="amount" type="number" inputmode="decimal" min="<?= (int) config('reload.min') ?>" max="<?= (int) config('reload.max') ?>" step="1" value="100" required>
                <span class="hint">Between <?= peso(config('reload.min'), 0) ?> and <?= peso(config('reload.max'), 0) ?></span></div>
            <div class="field"><label>Pay with</label>
                <div class="methods">
                    <?php $first = true; foreach ($methods as $k => $m): ?>
                        <label><input type="radio" name="method" value="<?= e($k) ?>" <?= $first ? 'checked' : '' ?>><?= e($m) ?></label>
                    <?php $first = false; endforeach; ?>
                </div></div>
            <button class="btn lg block" type="submit">Reload wallet</button>
            <p class="hint">For the demo, the payment is approved right away. A live version would hand off to GCash or Maya here.</p>
        </form>
    </div>
    <div class="card">
        <h3>Reload history</h3>
        <?php if (!$history): ?><p class="empty">No reloads yet.</p><?php endif; ?>
        <?php foreach ($history as $h): ?>
            <div class="list-row">
                <div class="who"><b><?= e($methods[$h['method']] ?? $h['method']) ?></b><span><?= e(fmt_date($h['reloaded_at'], 'M j, Y · g:i A')) ?></span></div>
                <span class="amt-plus num">+<?= peso($h['amount']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
document.querySelectorAll('#reloadForm input[name=preset]').forEach((r) => r.addEventListener('change', () => { document.getElementById('amount').value = r.value; }));
document.getElementById('amount').addEventListener('input', () => document.querySelectorAll('#reloadForm input[name=preset]').forEach((r) => { r.checked = false; }));
</script>
