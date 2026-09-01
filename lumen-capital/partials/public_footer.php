<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <a href="<?= url('index.php') ?>" class="brand" style="margin-bottom:14px;">
          <span class="brand-mark"><i class="fa-solid fa-chart-line"></i></span>
          <?= e(get_setting('site_name', APP_NAME)) ?>
        </a>
        <p style="max-width:280px;">Institutional-grade digital asset investment strategies, built for individuals who want clarity — not complexity.</p>
        <div style="display:flex;gap:10px;margin-top:16px;">
          <a href="#" class="btn btn-ghost btn-sm" style="padding:8px 10px;"><i class="fa-brands fa-x-twitter"></i></a>
          <a href="#" class="btn btn-ghost btn-sm" style="padding:8px 10px;"><i class="fa-brands fa-telegram"></i></a>
          <a href="#" class="btn btn-ghost btn-sm" style="padding:8px 10px;"><i class="fa-brands fa-linkedin"></i></a>
          <a href="#" class="btn btn-ghost btn-sm" style="padding:8px 10px;"><i class="fa-brands fa-discord"></i></a>
        </div>
      </div>
      <div>
        <h5>Platform</h5>
        <a href="<?= url('plans.php') ?>">Investment Plans</a>
        <a href="<?= url('about.php') ?>">About Us</a>
        <a href="<?= url('faq.php') ?>">FAQ</a>
        <a href="<?= url('contact.php') ?>">Contact</a>
      </div>
      <div>
        <h5>Account</h5>
        <a href="<?= url('register.php') ?>">Create Account</a>
        <a href="<?= url('login.php') ?>">Sign In</a>
        <a href="<?= url('user/deposit.php') ?>">Deposit Funds</a>
        <a href="<?= url('user/withdraw.php') ?>">Withdraw Funds</a>
      </div>
      <div>
        <h5>Legal</h5>
        <a href="<?= url('terms.php') ?>">Terms of Service</a>
        <a href="<?= url('privacy.php') ?>">Privacy Policy</a>
        <a href="<?= url('faq.php') ?>#risk">Risk Disclosure</a>
        <span style="color:var(--text-faint);font-size:13px;">Support:<br><?= e(get_setting('support_email')) ?></span>
      </div>
    </div>
    <div class="footer-disclaimer">
      <strong>Academic demo project.</strong> Lumen Capital is a simulated investment platform built for educational purposes only. No real funds, cryptocurrency, or financial instruments are transacted. Deposit and withdrawal flows are manually confirmed for demonstration and are not connected to any real payment network.
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', APP_NAME)) ?>. All rights reserved.</span>
      <span>Built with PHP &amp; MySQL</span>
    </div>
  </div>
</footer>

<script src="<?= asset('vendor/aos/aos.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script>AOS.init({ once: true, duration: 700, offset: 40 });</script>
<?php if (!empty($extraScript)) echo $extraScript; ?>
</body>
</html>
