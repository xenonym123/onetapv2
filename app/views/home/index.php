<section class="hero">
    <div class="wrap">
        <div class="copy">
            <div class="eyebrow">E-jeepneys in Bacolod City</div>
            <h1>Scan anytime,<br>ride anywhere.</h1>
            <p>Pay your fare with the QR code on your phone. See where your jeepney is, and know there's a seat before it gets to you.</p>
            <div class="cta">
                <a class="btn lg" href="<?= url('/register') ?>">Create a free account</a>
                <a class="btn lg ghost" href="#how">How it works</a>
            </div>
        </div>
        <div class="media">
            <div class="phone" aria-hidden="true">
                <div class="screen">
                    <div class="notch"></div>
                    <?php View::partial('logo') ?>
                    <span class="muted small">Wallet balance</span>
                    <b style="font-size:30px;letter-spacing:-.5px">₱250.00</b>
                    <div class="qrbox"><span id="demoQR"></span></div>
                    <b>Show this to the scanner</b>
                    <span class="pill pill-brand">Refreshes every 30 s</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="how">
    <div class="wrap">
        <div class="eyebrow">How it works</div>
        <h2>Three steps, no coins</h2>
        <div class="grid-3">
            <div class="step"><div class="n">1</div><h3>Sign up</h3><p>Make a passenger account. Your wallet and your ride QR come with it.</p></div>
            <div class="step"><div class="n">2</div><h3>Load your wallet</h3><p>Reload through GCash, Maya, a partner store, or a 1TAP kiosk.</p></div>
            <div class="step"><div class="n">3</div><h3>Show your QR</h3><p>Hold it up to the scanner when you board. The fare comes out of your wallet.</p></div>
        </div>
    </div>
</section>

<section class="section tint">
    <div class="wrap">
        <div class="eyebrow">1TAP by the numbers</div>
        <h2>Built for the daily commute</h2>
        <div class="grid-4">
            <div class="stat"><b>12+</b><span>E-jeepney routes</span></div>
            <div class="stat"><b>5,000</b><span>Daily passengers</span></div>
            <div class="stat"><b>150</b><span>Stops covered</span></div>
            <div class="stat"><b>25</b><span>Seat limit per unit, enforced at the scanner</span></div>
        </div>
    </div>
</section>

<script src="<?= asset('vendor/qrcode.js') ?>"></script>
<script>document.addEventListener('DOMContentLoaded', () => ONETAP.drawQR(document.getElementById('demoQR'), '1TAP-DEMO-QR'));</script>
