<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();

if ($viewer['email_verified_at']) {
    flash('info', 'Your email is already verified.');
    redirect('user/dashboard.php');
}

$verifyLink = send_verification_email($viewer['id'], $viewer['email'], $viewer['full_name']);

$pageTitle = 'Verify Email';
$activePage = 'dashboard';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="card" style="max-width:520px;">
  <div class="stat-icon violet" style="margin-bottom:16px;"><i class="fa-solid fa-envelope-circle-check"></i></div>
  <h3 style="margin-bottom:6px;">Verification email sent</h3>
  <p style="margin-bottom:20px;">We've sent a new verification link to <b><?= e($viewer['email']) ?></b>. It's valid for 24 hours.</p>

  <div class="alert alert-info" style="flex-direction:column;align-items:stretch;">
    <b>Demo mode — no SMTP server configured.</b>
    <p style="margin:8px 0 0;">In production this link would be emailed to you. For this demo, here it is directly:</p>
    <div class="wallet-address" style="margin-top:10px;">
      <span style="word-break:break-all;"><?= e($verifyLink) ?></span>
      <button type="button" class="copy-btn" data-copy="<?= e($verifyLink) ?>">Copy</button>
    </div>
    <a href="<?= e($verifyLink) ?>" class="btn btn-primary btn-sm" style="margin-top:12px;">Verify Now</a>
  </div>

  <a href="<?= url('user/dashboard.php') ?>" class="btn btn-ghost btn-sm" style="margin-top:16px;"><i class="fa-solid fa-arrow-left"></i> Back to dashboard</a>
</div>

<?php require __DIR__ . '/../partials/user_footer.php'; ?>
