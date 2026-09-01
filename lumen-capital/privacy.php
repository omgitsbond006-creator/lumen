<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Privacy Policy';
$pageDescription = 'Privacy Policy for ' . get_setting('site_name', APP_NAME) . '.';

require __DIR__ . '/partials/public_header.php';
?>

<section class="section" style="padding-top:60px;">
  <div class="container-narrow">
    <div class="text-center" style="margin-bottom:40px;" data-aos="fade-up">
      <div class="eyebrow"><span class="dot"></span> Legal</div>
      <h1 style="font-size:34px;">Privacy Policy</h1>
      <p>Last updated <?= date('F Y') ?></p>
    </div>

    <div class="card glass-card" style="margin-bottom:30px;">
      <p style="margin:0;"><strong>This is an academic software demonstration.</strong> Please do not submit real sensitive personal information, government ID numbers, or payment card details anywhere on this site. This policy is written in the style of a real product's privacy policy purely to demonstrate the feature.</p>
    </div>

    <h3>1. Information we collect</h3>
    <p>When you create an account, we store the information you provide directly: full name, email address, and optionally a phone number and country. When you use the platform, we record the actions you take — deposits, investments, withdrawals, and support messages — so your dashboard and transaction history can be displayed back to you accurately.</p>

    <h3>2. How we use it</h3>
    <p>Your information is used to operate your account: authenticating you, displaying your balance and history, processing the deposit/withdrawal requests you submit, sending account-related notices (welcome messages, password resets, email verification), and crediting referral bonuses.</p>

    <h3>3. Cookies &amp; sessions</h3>
    <p>We use a single session cookie to keep you signed in. It is required for the platform to function and is not used for cross-site tracking or advertising. No third-party analytics or advertising scripts run on this platform.</p>

    <h3>4. Data storage</h3>
    <p>Data is stored in a MySQL database associated with this application instance. Passwords are never stored in plain text — only a salted, one-way hash. There is no SMTP integration in this build; outbound "emails" are logged internally rather than actually transmitted (see the admin Outbound Email Log).</p>

    <h3>5. Data sharing</h3>
    <p>We do not sell or share your information with third parties. This is a self-contained demonstration application with no external integrations beyond locally-hosted static assets.</p>

    <h3>6. Your choices</h3>
    <p>You can update your profile information and password at any time from your account settings. You can ask an administrator to remove your account and associated data by contacting <?= e(get_setting('support_email')) ?>.</p>

    <h3>7. Data retention</h3>
    <p>Account and transaction records are retained for as long as the account exists, so that your ledger history remains accurate and complete.</p>

    <h3>8. Changes to this policy</h3>
    <p>We may update this policy from time to time to reflect changes to the platform. The "last updated" date at the top of this page will always reflect the latest revision.</p>

    <h3>9. Contact</h3>
    <p>Questions about this policy can be sent through our <a href="<?= url('contact.php') ?>">contact page</a> or to <?= e(get_setting('support_email')) ?>.</p>
  </div>
</section>

<?php require __DIR__ . '/partials/public_footer.php'; ?>
