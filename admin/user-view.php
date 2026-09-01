<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

$userId = (int) ($_GET['id'] ?? 0);
$target = find_user_by_id($userId);
if (!$target) {
    flash('danger', 'User not found.');
    redirect('admin/users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'adjust_balance') {
        $amount = (float) ($_POST['amount'] ?? 0);
        $direction = $_POST['direction'] ?? 'credit';
        $reason = trim($_POST['reason'] ?? '');

        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
        } elseif ($reason === '') {
            flash('danger', 'Please provide a reason for this adjustment.');
        } else {
            $signedAmount = $direction === 'debit' ? -$amount : $amount;
            $type = $direction === 'debit' ? 'admin_debit' : 'admin_credit';
            try {
                record_transaction($userId, $type, $signedAmount, 'admin', $viewer['id'], 'Admin adjustment: ' . $reason);
                log_activity($viewer['id'], ucfirst($direction) . ' balance', 'user', $userId, money($amount) . ' — ' . $reason);
                notify($userId, 'Balance adjusted', 'Your balance was ' . ($direction === 'debit' ? 'debited' : 'credited') . ' ' . money($amount) . ' by an administrator: ' . $reason, 'info');
                flash('success', 'Balance ' . ($direction === 'debit' ? 'debited' : 'credited') . ' successfully.');
            } catch (Throwable $e) {
                flash('danger', 'Could not adjust balance.');
            }
        }
    } elseif ($action === 'toggle_status' && $target['id'] !== $viewer['id']) {
        $newStatus = $target['status'] === 'active' ? 'suspended' : 'active';
        db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $userId]);
        log_activity($viewer['id'], ucfirst($newStatus) . ' user', 'user', $userId, $target['full_name']);
        flash('success', $target['full_name'] . ' is now ' . $newStatus . '.');
    } elseif ($action === 'toggle_role' && $target['id'] !== $viewer['id']) {
        $newRole = $target['role'] === 'admin' ? 'user' : 'admin';
        db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $userId]);
        log_activity($viewer['id'], 'Changed role to ' . $newRole, 'user', $userId, $target['full_name']);
        flash('success', $target['full_name'] . ' role updated.');
    } elseif ($action === 'update_info') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $country = trim($_POST['country'] ?? '');

        if ($fullName === '') {
            flash('danger', 'Please enter a valid name.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Please enter a valid email address.');
        } else {
            $existing = find_user_by_email($email);
            if ($existing && (int) $existing['id'] !== $userId) {
                flash('danger', 'That email address is already used by another account.');
            } else {
                $emailChanged = strtolower($target['email']) !== $email;

                if ($emailChanged) {
                    // Ownership of the new address hasn't been proven — reset verification
                    // and send a fresh verification link to the new address.
                    db()->prepare('UPDATE users SET full_name=?, email=?, phone=?, country=?, email_verified_at=NULL WHERE id=?')
                        ->execute([$fullName, $email, $phone ?: null, $country ?: null, $userId]);
                    send_verification_email($userId, $email, $fullName);
                    log_activity($viewer['id'], 'Changed email', 'user', $userId, $target['email'] . ' → ' . $email);
                    notify($userId, 'Account email changed', 'Your account email was changed by an administrator to ' . $email . '. Please verify it.', 'warning');
                    flash('success', 'User info updated. A new verification email was sent to ' . $email . '.');
                } else {
                    db()->prepare('UPDATE users SET full_name=?, phone=?, country=? WHERE id=?')
                        ->execute([$fullName, $phone ?: null, $country ?: null, $userId]);
                    flash('success', 'User info updated.');
                }
                log_activity($viewer['id'], 'Updated user info', 'user', $userId, $fullName);
            }
        }
    } elseif ($action === 'set_password') {
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($pwError = validate_password_strength($newPass)) {
            flash('danger', $pwError);
        } elseif ($newPass !== $confirm) {
            flash('danger', 'The passwords do not match.');
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($newPass, PASSWORD_DEFAULT), $userId]);
            // Invalidate any active lockouts tied to the old credentials.
            db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?')->execute([$userId]);
            log_activity($viewer['id'], 'Reset password', 'user', $userId, $target['full_name']);
            notify($userId, 'Password changed', 'Your password was reset by an administrator. If this wasn\'t expected, contact support immediately.', 'warning');
            flash('success', "Password updated for {$target['full_name']}.");
        }
    }
    redirect('admin/user-view.php?id=' . $userId);
}

$target = find_user_by_id($userId);
$investments = db()->prepare('SELECT * FROM investments WHERE user_id = ? ORDER BY created_at DESC');
$investments->execute([$userId]);
$investments = $investments->fetchAll();

$deposits = db()->prepare('SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$deposits->execute([$userId]);
$deposits = $deposits->fetchAll();

$withdrawals = db()->prepare('SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$withdrawals->execute([$userId]);
$withdrawals = $withdrawals->fetchAll();

$transactions = db()->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 15');
$transactions->execute([$userId]);
$transactions = $transactions->fetchAll();

$referredBy = null;
if ($target['referred_by']) $referredBy = find_user_by_id((int) $target['referred_by']);

$pageTitle = $target['full_name'];
$activePage = 'users';
require __DIR__ . '/../partials/admin_header.php';
?>

<a href="<?= url('admin/users.php') ?>" class="btn btn-ghost btn-sm" style="margin-bottom:16px;"><i class="fa-solid fa-arrow-left"></i> All Users</a>

<div class="card glass-card" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
    <div style="display:flex;gap:16px;align-items:center;">
      <span class="avatar-circle lg"><?= e(avatar_initials($target['full_name'])) ?></span>
      <div>
        <div style="font-size:19px;font-weight:700;"><?= e($target['full_name']) ?> <span class="badge <?= $target['role'] === 'admin' ? 'badge-primary' : 'badge-neutral' ?>"><?= e($target['role']) ?></span> <span class="badge <?= badge_class($target['status']) ?>"><?= e($target['status']) ?></span></div>
        <div class="text-faint"><?= e($target['email']) ?><?= $target['phone'] ? ' &middot; ' . e($target['phone']) : '' ?></div>
        <div class="text-faint" style="font-size:12.5px;">Joined <?= format_date($target['created_at']) ?> &middot; Referral code <?= e($target['referral_code']) ?><?= $referredBy ? ' &middot; Referred by ' . e($referredBy['full_name']) : '' ?></div>
      </div>
    </div>
    <?php if ($target['id'] !== $viewer['id']): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <form method="POST" action="<?= url('admin/user-view.php?id=' . $userId) ?>" data-confirm="Change this user's status?">
        <?= csrf_field() ?><input type="hidden" name="action" value="toggle_status">
        <button type="submit" class="btn btn-sm <?= $target['status'] === 'active' ? 'btn-danger' : 'btn-success' ?>">
          <?= $target['status'] === 'active' ? 'Suspend Account' : 'Reactivate Account' ?>
        </button>
      </form>
      <form method="POST" action="<?= url('admin/user-view.php?id=' . $userId) ?>" data-confirm="Change this user's role?">
        <?= csrf_field() ?><input type="hidden" name="action" value="toggle_role">
        <button type="submit" class="btn btn-outline btn-sm">
          <?= $target['role'] === 'admin' ? 'Remove Admin' : 'Make Admin' ?>
        </button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="stat-grid">
  <div class="card stat-card"><div class="stat-value"><?= money((float) $target['balance']) ?></div><div class="stat-label">Current Balance</div></div>
  <div class="card stat-card"><div class="stat-value"><?= money((float) $target['total_deposited']) ?></div><div class="stat-label">Total Deposited</div></div>
  <div class="card stat-card"><div class="stat-value text-emerald"><?= money((float) $target['total_earned']) ?></div><div class="stat-label">Total Earned</div></div>
  <div class="card stat-card"><div class="stat-value"><?= money((float) $target['total_withdrawn']) ?></div><div class="stat-label">Total Withdrawn</div></div>
</div>

<div class="dash-grid" style="margin-bottom:20px;">
  <div>
    <div class="card" style="margin-bottom:20px;">
      <h3 class="card-title" style="margin-bottom:14px;">Investments</h3>
      <?php if (!$investments): ?><div class="empty-state"><p>No investments.</p></div>
      <?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Plan</th><th>Amount</th><th>Paid Out</th><th>Status</th><th>Matures</th></tr></thead>
        <tbody>
        <?php foreach ($investments as $inv): ?>
          <tr>
            <td><?= e($inv['plan_name']) ?></td>
            <td class="mono"><?= money((float) $inv['amount']) ?></td>
            <td class="mono text-emerald"><?= money((float) $inv['paid_out']) ?></td>
            <td><span class="badge <?= badge_class($inv['status']) ?>"><?= e($inv['status']) ?></span></td>
            <td class="text-faint"><?= format_date($inv['maturity_date']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3 class="card-title" style="margin-bottom:14px;">Recent Transactions</h3>
      <?php if (!$transactions): ?><div class="empty-state"><p>No transactions.</p></div>
      <?php else: foreach ($transactions as $tx): ?>
        <div class="timeline-item">
          <div class="timeline-dot <?= $tx['amount'] >= 0 ? 'success' : 'warning' ?>"></div>
          <div style="flex:1;display:flex;justify-content:space-between;gap:10px;">
            <div><span style="font-size:13.5px;"><?= e($tx['description'] ?: ucwords(str_replace('_',' ',$tx['type']))) ?></span><div class="text-faint" style="font-size:11.5px;"><?= time_ago($tx['created_at']) ?></div></div>
            <span class="mono fw-600 <?= $tx['amount'] >= 0 ? 'text-emerald' : 'text-red' ?>"><?= money((float) $tx['amount'], true) ?></span>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:20px;">
      <h3 class="card-title" style="margin-bottom:14px;">Adjust Balance</h3>
      <form method="POST" action="<?= url('admin/user-view.php?id=' . $userId) ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="adjust_balance">
        <div class="form-group">
          <label class="form-label">Direction</label>
          <select name="direction" class="form-control">
            <option value="credit">Credit (add funds)</option>
            <option value="debit">Debit (remove funds)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Amount</label>
          <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Reason</label>
          <input type="text" name="reason" class="form-control" placeholder="e.g. Manual correction, bonus, refund" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Apply Adjustment</button>
      </form>
    </div>

    <div class="card" style="margin-bottom:20px;">
      <h3 class="card-title" style="margin-bottom:14px;">Edit Info</h3>
      <form method="POST" action="<?= url('admin/user-view.php?id=' . $userId) ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="update_info">
        <div class="form-group"><label class="form-label">Full name</label><input type="text" name="full_name" class="form-control" value="<?= e($target['full_name']) ?>" required></div>
        <div class="form-group">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" value="<?= e($target['email']) ?>" required>
          <div class="form-hint">Changing this resets email verification and sends a new verification link.</div>
        </div>
        <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($target['phone']) ?>"></div>
        <div class="form-group"><label class="form-label">Country</label><input type="text" name="country" class="form-control" value="<?= e($target['country']) ?>"></div>
        <button type="submit" class="btn btn-outline btn-block">Save Info</button>
      </form>
    </div>

    <div class="card">
      <h3 class="card-title" style="margin-bottom:14px;">Set Password</h3>
      <form method="POST" action="<?= url('admin/user-view.php?id=' . $userId) ?>" data-confirm="Set a new password for this user?">
        <?= csrf_field() ?><input type="hidden" name="action" value="set_password">
        <div class="form-group">
          <label class="form-label">New password</label>
          <input type="password" name="new_password" class="form-control" data-password-strength placeholder="Min 8 chars, 1 uppercase, 1 number" required>
          <div class="progress" style="margin-top:8px;"><div class="progress-bar" id="password-strength-bar" style="width:0;"></div></div>
          <div class="form-hint" id="password-strength-label"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-outline btn-block">Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
