<?php
require_once __DIR__ . '/includes/init.php';
require_guest();

$pageTitle = 'Forgot Password';
$error = null;
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $user = find_user_by_email($email);

    // Always show a generic confirmation (never reveal whether an email exists).
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $stmt = db()->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
        $stmt->execute([$user['id'], $token]);

        $resetLink = url('reset-password.php?token=' . $token);
        send_mail($email, 'Reset your ' . get_setting('site_name', APP_NAME) . ' password',
            "We received a request to reset your password. This link expires in 30 minutes:\n\n{$resetLink}\n\nIf you didn't request this, you can ignore this email.");
    }

    flash('info', 'If an account exists for that email, a password reset link has been sent.');
}

require __DIR__ . '/partials/public_header.php';
?>

<section class="section" style="padding-top:60px;">
  <div class="container-narrow">
    <div class="card" data-aos="fade-up" style="max-width:460px;margin:0 auto;">
      <h2 style="margin-bottom:6px;">Reset your password</h2>
      <p style="margin-bottom:24px;">Enter the email address on your account and we'll send you a reset link.</p>

      <?php if ($resetLink): ?>
        <div class="alert alert-info" style="flex-direction:column;align-items:stretch;">
          <b>Demo mode — no SMTP server configured.</b>
          <p style="margin:8px 0 0;">In production this link would be emailed to you. For this demo, here it is directly:</p>
          <div class="wallet-address" style="margin-top:10px;">
            <span style="word-break:break-all;"><?= e($resetLink) ?></span>
            <button type="button" class="copy-btn" data-copy="<?= e($resetLink) ?>">Copy</button>
          </div>
          <a href="<?= e($resetLink) ?>" class="btn btn-primary btn-sm" style="margin-top:12px;">Open reset link</a>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= url('forgot-password.php') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
      </form>
      <p class="text-center" style="margin-top:18px;font-size:13.5px;"><a href="<?= url('login.php') ?>"><i class="fa-solid fa-arrow-left"></i> Back to sign in</a></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/public_footer.php'; ?>
