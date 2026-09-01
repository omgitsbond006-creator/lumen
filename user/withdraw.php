<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();
$errors = [];

$minWithdrawal = (float) get_setting('min_withdrawal', 50);
$feePercent = (float) get_setting('withdrawal_fee_percent', 2);
$methods = db()->query('SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $amount = (float) ($_POST['amount'] ?? 0);
    $methodLabel = trim($_POST['method_label'] ?? '');
    $destination = trim($_POST['destination'] ?? '');

    if ($amount < $minWithdrawal) {
        $errors[] = 'Minimum withdrawal amount is ' . money($minWithdrawal) . '.';
    }
    if ($amount > (float) $viewer['balance']) {
        $errors[] = 'You cannot withdraw more than your available balance.';
    }
    if ($methodLabel === '') {
        $errors[] = 'Please choose a withdrawal method.';
    }
    if ($destination === '') {
        $errors[] = 'Please enter a destination address or account.';
    }

    if (!$errors) {
        $fee = round($amount * ($feePercent / 100), 2);
        $net = round($amount - $fee, 2);

        try {
            // Reserve the funds immediately so the balance can't be double-spent
            // while the request is pending admin review.
            record_transaction($viewer['id'], 'withdrawal', -$amount, 'withdrawal', null, 'Withdrawal request submitted', 'pending');

            $stmt = db()->prepare('INSERT INTO withdrawals (user_id, amount, fee, net_amount, method_label, destination)
                VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$viewer['id'], $amount, $fee, $net, $methodLabel, $destination]);
            $withdrawalId = (int) db()->lastInsertId();

            db()->prepare("UPDATE transactions SET reference_id = ? WHERE user_id = ? AND type = 'withdrawal' AND reference_id IS NULL ORDER BY id DESC LIMIT 1")
                ->execute([$withdrawalId, $viewer['id']]);

            if ($destination && !$viewer['payout_wallet']) {
                db()->prepare('UPDATE users SET payout_wallet = ? WHERE id = ?')->execute([$destination, $viewer['id']]);
            }

            notify($viewer['id'], 'Withdrawal submitted', 'Your withdrawal request of ' . money($amount) . ' is pending review.', 'info');
            $admins = db()->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
            foreach ($admins as $a) {
                notify((int) $a['id'], 'New withdrawal request', $viewer['full_name'] . ' requested a withdrawal of ' . money($amount) . '.', 'warning');
            }

            flash('success', 'Your withdrawal request has been submitted and is pending approval.');
            redirect('user/withdraw.php');
        } catch (Throwable $e) {
            $errors[] = 'Something went wrong submitting your withdrawal. Please try again.';
        }
    }
}

$myWithdrawals = db()->prepare('SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$myWithdrawals->execute([$viewer['id']]);
$myWithdrawals = $myWithdrawals->fetchAll();

$pageTitle = 'Withdraw Funds';
$activePage = 'withdraw';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="dash-grid">
  <div>
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Request a withdrawal</h3>
          <span class="sub">Available balance: <b class="text-emerald"><?= money((float) $viewer['balance']) ?></b></span>
        </div>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><span><?= e(implode(' ', $errors)) ?></span></div>
      <?php endif; ?>

      <?php if ((float) $viewer['balance'] < $minWithdrawal): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
          <p>Your balance is below the minimum withdrawal of <?= money($minWithdrawal) ?>.</p>
        </div>
      <?php else: ?>
        <form method="POST" action="<?= url('user/withdraw.php') ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">Amount</label>
            <input type="number" step="0.01" min="<?= $minWithdrawal ?>" max="<?= (float) $viewer['balance'] ?>" name="amount" id="wdAmount" class="form-control" required>
            <div class="quick-amounts" data-amount-target="#wdAmount">
              <button type="button" data-fill="max" data-max="<?= (float) $viewer['balance'] ?>">Withdraw All</button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Method</label>
            <select name="method_label" class="form-control" required>
              <?php foreach ($methods as $m): ?>
                <option value="<?= e($m['currency_name']) ?>"><?= e($m['currency_name']) ?><?= $m['network'] ? ' (' . e($m['network']) . ')' : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Destination address / account</label>
            <input type="text" name="destination" class="form-control" value="<?= old('destination', $viewer['payout_wallet'] ?? '') ?>" placeholder="Wallet address or bank details" required>
          </div>
          <div class="card-sm" style="background:var(--bg-elevated);margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:10px;"><span class="text-faint">Network / processing fee (<?= percent($feePercent) ?>)</span><span id="wdFee">$0.00</span></div>
            <div style="display:flex;justify-content:space-between;"><span class="text-faint">You will receive</span><b class="text-emerald" id="wdNet">$0.00</b></div>
          </div>
          <button type="submit" class="btn btn-primary btn-block btn-lg">Submit Withdrawal Request</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="card glass-card" style="margin-bottom:20px;">
      <div class="stat-icon violet" style="margin-bottom:12px;"><i class="fa-solid fa-shield-halved"></i></div>
      <h4 style="margin-bottom:6px;">Reviewed for your security</h4>
      <p style="font-size:13.5px;">Withdrawal requests are reserved from your balance immediately and reviewed by our team before funds are released, protecting your account from unauthorized transfers.</p>
    </div>

    <div class="card">
      <h3 class="card-title" style="margin-bottom:14px;">Your Withdrawal History</h3>
      <?php if (!$myWithdrawals): ?>
        <div class="empty-state"><p>No withdrawals yet.</p></div>
      <?php else: foreach ($myWithdrawals as $w): ?>
        <div class="investment-row">
          <div class="inv-plan-icon"><i class="fa-solid fa-arrow-up"></i></div>
          <div class="inv-info">
            <div class="inv-name"><?= money((float) $w['amount']) ?> <span class="text-faint" style="font-weight:400;">&middot; <?= e($w['method_label']) ?></span></div>
            <div class="inv-sub"><?= time_ago($w['created_at']) ?> &middot; net <?= money((float) $w['net_amount']) ?></div>
          </div>
          <div class="inv-right"><span class="badge <?= badge_class($w['status']) ?>"><?= e($w['status']) ?></span></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php
$extraScript = '<script>
(function(){
  var amount = document.getElementById("wdAmount");
  var fee = document.getElementById("wdFee");
  var net = document.getElementById("wdNet");
  if (!amount) return;
  var feePct = ' . json_encode($feePercent) . ';
  function fmt(n){ return "$" + n.toLocaleString("en-US",{minimumFractionDigits:2,maximumFractionDigits:2}); }
  function recalc(){
    var amt = parseFloat(amount.value) || 0;
    var f = amt * (feePct/100);
    fee.textContent = fmt(f);
    net.textContent = fmt(amt - f);
  }
  amount.addEventListener("input", recalc);
  recalc();
})();
</script>';
require __DIR__ . '/../partials/user_footer.php';
?>
