<footer class="footer">
    <div class="wrap">
        <div>
            <?php View::partial('logo', ['white' => true]) ?>
            <p style="margin-top:12px;max-width:22em">QR fare payment and passenger monitoring for Bacolod's e-jeepneys.</p>
        </div>
        <div>
            <h4>Quick links</h4>
            <ul><li><a href="<?= url('/') ?>">Home</a></li><li><a href="<?= url('/about') ?>">About Us</a></li><li><a href="<?= url('/pay-with-qr') ?>">Pay with QR</a></li></ul>
        </div>
        <div>
            <h4>Get started</h4>
            <ul><li><a href="<?= url('/register') ?>">Create an account</a></li><li><a href="<?= url('/login') ?>">Login</a></li><li><a href="<?= url('/pay-with-qr') ?>#reload">Where to reload</a></li></ul>
        </div>
        <div class="copy">© <?= date('Y') ?> 1TAP Transportation Management System</div>
    </div>
</footer>
