<?php
require_once __DIR__ . '/includes/init.php';
require_guest();

$pageTitle = 'Sign In';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $result = attempt_login($email, $password);
    if ($result['success']) {
        login_user($result['user']);
        flash('success', 'Welcome back, ' . explode(' ', $result['user']['full_name'])[0] . '!');
        redirect($result['user']['role'] === 'admin' ? 'admin/index.php' : 'user/dashboard.php');
    } else {
        $error = $result['message'];
    }
    set_old(['email' => $email]);
}

require __DIR__ . '/partials/public_header.php';
?>

<div class="auth-shell">
  <div class="auth-visual" data-aos="fade-right">
    <div style="position:relative;z-index:1;max-width:420px;">
      <div class="eyebrow"><span class="dot"></span> Welcome back</div>
      <h2 style="font-size:30px;">Pick up right where you left off.</h2>
      <p>Your balance, active investments, and payout history are exactly as you left them.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-box">
      <a href="<?= url('index.php') ?>" class="brand"><span class="brand-mark"><i class="fa-solid fa-chart-line"></i></span><?= e(get_setting('site_name', APP_NAME)) ?></a>
      <h2>Sign in to your account</h2>
      <p class="text-faint" style="margin-bottom:24px;">New here? <a href="<?= url('register.php') ?>">Create an account</a></p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><span><?= e($error) ?></span></div>
      <?php endif; ?>

      <form method="POST" action="<?= url('login.php') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label" style="display:flex;justify-content:space-between;">Password <a href="<?= url('forgot-password.php') ?>" style="font-weight:400;font-size:12.5px;">Forgot password?</a></label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
      </form>

      <div class="divider-or">or try a demo account</div>
      <div class="card-sm" style="background:var(--bg-elevated);font-size:12.5px;line-height:1.9;">
        <b>Investor demo:</b> demo@lumencapital.test / Demo@2026!<br>
        <b>Admin demo:</b> admin@lumencapital.test / Admin@2026!
      </div>
    </div>
  </div>
</div>

<?php clear_old(); require __DIR__ . '/partials/public_footer.php'; ?>
