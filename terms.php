<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Terms of Service';
$pageDescription = 'Terms of Service for ' . get_setting('site_name', APP_NAME) . '.';

require __DIR__ . '/partials/public_header.php';
?>

<section class="section" style="padding-top:60px;">
  <div class="container-narrow">
    <div class="text-center" style="margin-bottom:40px;" data-aos="fade-up">
      <div class="eyebrow"><span class="dot"></span> Legal</div>
      <h1 style="font-size:34px;">Terms of Service</h1>
      <p>Last updated <?= date('F Y') ?></p>
    </div>

    <div class="card glass-card" style="margin-bottom:30px;">
      <p style="margin:0;"><strong>This is an academic software demonstration.</strong> <?= e(get_setting('site_name', APP_NAME)) ?> does not hold, transmit, or invest real money, cryptocurrency, or any financial instrument. Nothing on this platform is a real offer of securities or an investment product. These Terms are written in the style of a real product's terms of service purely to demonstrate the feature, not to create binding legal obligations.</p>
    </div>

    <h3>1. Acceptance of these Terms</h3>
    <p>By creating an account or otherwise using this platform, you agree to be bound by these Terms of Service and our <a href="<?= url('privacy.php') ?>">Privacy Policy</a>. If you do not agree, please do not use the platform.</p>

    <h3>2. Eligibility</h3>
    <p>This demo platform is intended for evaluation and educational purposes. You should not submit real personal, financial, or payment information anywhere on this site.</p>

    <h3>3. Accounts</h3>
    <p>You are responsible for maintaining the confidentiality of your password and for all activity under your account. Notify us immediately of any unauthorized use. We may suspend or terminate accounts that violate these Terms or that we reasonably believe pose a risk to the platform.</p>

    <h3>4. Investment plans &amp; simulated returns</h3>
    <p>Plans, daily ROI percentages, and maturity terms displayed on this platform are illustrative figures used to demonstrate a plan-based investment engine. They do not represent real market performance, and past or displayed figures are not indicative of any real future return. See our <a href="<?= url('faq.php') ?>#risk">Risk Disclosure</a> for more detail.</p>

    <h3>5. Deposits &amp; withdrawals</h3>
    <p>Deposit and withdrawal requests submitted through this platform are simulated and are reviewed manually by a platform administrator for demonstration purposes. No real transfer of funds, cryptocurrency, or value occurs as a result of any deposit or withdrawal action taken on this site.</p>

    <h3>6. Prohibited use</h3>
    <p>You agree not to use the platform to store real financial credentials, attempt unauthorized access to other accounts, interfere with the normal operation of the platform, or misrepresent this demo as a real, licensed financial service to any third party.</p>

    <h3>7. Referral program</h3>
    <p>Referral bonuses described on this platform are calculated and credited within the simulated ledger only and carry no real-world monetary value.</p>

    <h3>8. Termination</h3>
    <p>We may suspend or terminate access to any account at any time, with or without notice, including for demonstration or maintenance purposes.</p>

    <h3>9. Changes to these Terms</h3>
    <p>We may update these Terms from time to time. Continued use of the platform after a change constitutes acceptance of the revised Terms.</p>

    <h3>10. Contact</h3>
    <p>Questions about these Terms can be sent through our <a href="<?= url('contact.php') ?>">contact page</a> or to <?= e(get_setting('support_email')) ?>.</p>
  </div>
</section>

<?php require __DIR__ . '/partials/public_footer.php'; ?>
