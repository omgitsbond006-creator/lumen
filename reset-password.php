<?php
require_once __DIR__ . '/includes/init.php';
require_guest();

$pageTitle = 'Reset Password';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];

$stmt = db()->prepare('SELECT pr.*, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()');
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    flash('danger', 'That password reset link is invalid or has expired. Please request a new one.');
    redirect('forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($pwError = validate_password_strength($password)) {
        $errors[] = $pwError;
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        db()->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$reset['id']]);

        flash('success', 'Your password has been updated. You can now sign in.');
        redirect('login.php');
    }
}

require __DIR__ . '/partials/public_header.php';
?>

<section class="section" style="padding-top:60px;">
  <div class="container-narrow">
    <div class="card" data-aos="fade-up" style="max-width:460px;margin:0 auto;">
      <h2 style="margin-bottom:6px;">Choose a new password</h2>
      <p style="margin-bottom:24px;">Resetting password for <b><?= e($reset['email']) ?></b></p>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><span><?= e(implode(' ', $errors)) ?></span></div>
      <?php endif; ?>

      <form method="POST" action="<?= url('reset-password.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
          <label class="form-label">New password</label>
          <input type="password" name="password" class="form-control" data-password-strength required>
          <div class="progress" style="margin-top:8px;"><div class="progress-bar" id="password-strength-bar" style="width:0;"></div></div>
          <div class="form-hint" id="password-strength-label"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm new password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Update Password</button>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/public_footer.php'; ?>
