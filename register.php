<?php
require_once __DIR__ . '/includes/init.php';
require_guest();

$pageTitle = 'Create Account';
$errors = [];
$refCode = trim($_GET['ref'] ?? ($_POST['referral_code'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $refCode = strtoupper(trim($_POST['referral_code'] ?? ''));
    $terms = isset($_POST['terms']);

    if ($fullName === '' || mb_strlen($fullName) > 120) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (find_user_by_email($email)) {
        $errors[] = 'An account with that email already exists.';
    }
    if ($pwError = validate_password_strength($password)) {
        $errors[] = $pwError;
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$terms) {
        $errors[] = 'You must agree to the Terms and Risk Disclosure to continue.';
    }

    $referrer = null;
    if ($refCode !== '') {
        $stmt = db()->prepare('SELECT * FROM users WHERE referral_code = ?');
        $stmt->execute([$refCode]);
        $referrer = $stmt->fetch();
        if (!$referrer) {
            $errors[] = 'That referral code was not recognized.';
        }
    }

    if (!$errors) {
        $referralCode = generate_referral_code($fullName);
        $stmt = db()->prepare('INSERT INTO users (full_name, email, phone, country, password_hash, referral_code, referred_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $fullName, $email, $phone ?: null, $country ?: null,
            password_hash($password, PASSWORD_DEFAULT),
            $referralCode,
            $referrer['id'] ?? null,
        ]);
        $userId = (int) db()->lastInsertId();

        send_mail($email, 'Welcome to ' . get_setting('site_name', APP_NAME), "Hi {$fullName},\n\nYour account has been created successfully. Sign in any time to explore investment plans and manage your portfolio.");
        send_verification_email($userId, $email, $fullName);
        notify($userId, 'Welcome to ' . get_setting('site_name', APP_NAME), 'Your account is ready. Make your first deposit to start investing.', 'success');

        if ($referrer) {
            notify((int) $referrer['id'], 'New referral', $fullName . ' just joined using your referral link.', 'info');
        }

        $user = find_user_by_id($userId);
        login_user($user);
        flash('success', 'Welcome to ' . get_setting('site_name', APP_NAME) . '! Your account has been created.');
        redirect('user/dashboard.php');
    }
    set_old($_POST);
}

require __DIR__ . '/partials/public_header.php';
?>

<div class="auth-shell">
  <div class="auth-visual" data-aos="fade-right">
    <div style="position:relative;z-index:1;max-width:420px;">
      <div class="eyebrow"><span class="dot"></span> Join Lumen Capital</div>
      <h2 style="font-size:30px;">Your portfolio, fully visible — from your first deposit.</h2>
      <p>Real-time balances, daily payouts, and a complete transaction history from day one.</p>
      <div style="display:flex;flex-direction:column;gap:16px;margin-top:32px;">
        <div style="display:flex;gap:12px;align-items:flex-start;"><i class="fa-solid fa-circle-check text-emerald" style="margin-top:3px;"></i><span>No hidden fees on deposits</span></div>
        <div style="display:flex;gap:12px;align-items:flex-start;"><i class="fa-solid fa-circle-check text-emerald" style="margin-top:3px;"></i><span>Daily ROI credited automatically</span></div>
        <div style="display:flex;gap:12px;align-items:flex-start;"><i class="fa-solid fa-circle-check text-emerald" style="margin-top:3px;"></i><span>Earn 5% referring friends</span></div>
      </div>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-box">
      <a href="<?= url('index.php') ?>" class="brand"><span class="brand-mark"><i class="fa-solid fa-chart-line"></i></span><?= e(get_setting('site_name', APP_NAME)) ?></a>
      <h2>Create your account</h2>
      <p class="text-faint" style="margin-bottom:24px;">Already have one? <a href="<?= url('login.php') ?>">Sign in</a></p>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><span><?= e(implode(' ', $errors)) ?></span></div>
      <?php endif; ?>

      <form method="POST" action="<?= url('register.php') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">Full name</label>
          <input type="text" name="full_name" class="form-control" value="<?= old('full_name') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone <span class="text-faint">(optional)</span></label>
          <input type="text" name="phone" class="form-control" value="<?= old('phone') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" data-password-strength required>
          <div class="progress" style="margin-top:8px;"><div class="progress-bar" id="password-strength-bar" style="width:0;"></div></div>
          <div class="form-hint" id="password-strength-label"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Referral code <span class="text-faint">(optional)</span></label>
          <input type="text" name="referral_code" class="form-control" value="<?= e($refCode) ?>">
        </div>
        <div class="form-group checkbox-row">
          <input type="checkbox" name="terms" id="terms" <?= isset($_POST['terms']) ? 'checked' : '' ?>>
          <label for="terms">I agree to the <a href="<?= url('terms.php') ?>" target="_blank">Terms of Service</a>, <a href="<?= url('privacy.php') ?>" target="_blank">Privacy Policy</a>, and <a href="<?= url('faq.php') ?>#risk" target="_blank">Risk Disclosure</a>, and understand this is a demonstration platform.</label>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
      </form>
    </div>
  </div>
</div>

<?php clear_old(); require __DIR__ . '/partials/public_footer.php'; ?>
