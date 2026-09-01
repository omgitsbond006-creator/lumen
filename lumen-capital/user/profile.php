<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();
$errors = [];
$pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $payoutWallet = trim($_POST['payout_wallet'] ?? '');

        if ($fullName === '' || mb_strlen($fullName) > 120) {
            $errors[] = 'Please enter a valid name.';
        }

        if (!$errors) {
            db()->prepare('UPDATE users SET full_name = ?, phone = ?, country = ?, payout_wallet = ? WHERE id = ?')
                ->execute([$fullName, $phone ?: null, $country ?: null, $payoutWallet ?: null, $viewer['id']]);
            flash('success', 'Your profile has been updated.');
            redirect('user/profile.php');
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $viewer['password_hash'])) {
            $pwErrors[] = 'Your current password is incorrect.';
        } elseif ($pwError = validate_password_strength($newPass)) {
            $pwErrors[] = $pwError;
        } elseif ($newPass !== $confirm) {
            $pwErrors[] = 'New passwords do not match.';
        }

        if (!$pwErrors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($newPass, PASSWORD_DEFAULT), $viewer['id']]);
            flash('success', 'Your password has been changed.');
            redirect('user/profile.php');
        }
    }

    $viewer = current_user();
}

$pageTitle = 'Profile Settings';
$activePage = 'profile';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="dash-grid">
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <span class="avatar-circle lg"><?= e(avatar_initials($viewer['full_name'])) ?></span>
        <div>
          <div style="font-weight:700;font-size:17px;"><?= e($viewer['full_name']) ?></div>
          <div class="text-faint"><?= e($viewer['email']) ?></div>
        </div>
      </div>

      <?php if ($errors): ?><div class="alert alert-danger"><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>

      <form method="POST" action="<?= url('user/profile.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_profile">
        <div class="form-group">
          <label class="form-label">Full name</label>
          <input type="text" name="full_name" class="form-control" value="<?= e($viewer['full_name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email address</label>
          <input type="email" class="form-control" value="<?= e($viewer['email']) ?>" disabled>
          <div class="form-hint">Contact support to change your email address.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= e($viewer['phone']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Country</label>
          <input type="text" name="country" class="form-control" value="<?= e($viewer['country']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Default payout wallet / account</label>
          <input type="text" name="payout_wallet" class="form-control" value="<?= e($viewer['payout_wallet']) ?>" placeholder="Used to pre-fill withdrawal requests">
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>

    <div class="card">
      <h3 class="card-title" style="margin-bottom:16px;">Change password</h3>
      <?php if ($pwErrors): ?><div class="alert alert-danger"><span><?= e(implode(' ', $pwErrors)) ?></span></div><?php endif; ?>
      <form method="POST" action="<?= url('user/profile.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
          <label class="form-label">Current password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">New password</label>
          <input type="password" name="new_password" class="form-control" data-password-strength required>
          <div class="progress" style="margin-top:8px;"><div class="progress-bar" id="password-strength-bar" style="width:0;"></div></div>
          <div class="form-hint" id="password-strength-label"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm new password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-outline">Update Password</button>
      </form>
    </div>
  </div>

  <div>
    <div class="card">
      <h3 class="card-title" style="margin-bottom:16px;">Account Summary</h3>
      <div style="display:flex;flex-direction:column;gap:14px;font-size:13.5px;">
        <div style="display:flex;justify-content:space-between;"><span class="text-faint">Member since</span><b><?= format_date($viewer['created_at']) ?></b></div>
        <div style="display:flex;justify-content:space-between;"><span class="text-faint">Last login</span><b><?= $viewer['last_login_at'] ? time_ago($viewer['last_login_at']) : 'This session' ?></b></div>
        <div style="display:flex;justify-content:space-between;"><span class="text-faint">Total deposited</span><b><?= money((float) $viewer['total_deposited']) ?></b></div>
        <div style="display:flex;justify-content:space-between;"><span class="text-faint">Total earned</span><b class="text-emerald"><?= money((float) $viewer['total_earned']) ?></b></div>
        <div style="display:flex;justify-content:space-between;"><span class="text-faint">Total withdrawn</span><b><?= money((float) $viewer['total_withdrawn']) ?></b></div>
        <div style="display:flex;justify-content:space-between;"><span class="text-faint">Referral code</span><b><?= e($viewer['referral_code']) ?></b></div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/user_footer.php'; ?>
