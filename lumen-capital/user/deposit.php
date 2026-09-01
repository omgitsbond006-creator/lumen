<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();
$errors = [];

$methods = db()->query('SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll();
$minDeposit = (float) get_setting('min_deposit', 50);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $amount = (float) ($_POST['amount'] ?? 0);
    $methodCode = $_POST['method'] ?? '';
    $txnRef = trim($_POST['txn_reference'] ?? '');
    $note = trim($_POST['note'] ?? '');

    $method = null;
    foreach ($methods as $m) {
        if ($m['currency_code'] === $methodCode) { $method = $m; break; }
    }

    if ($amount < $minDeposit) {
        $errors[] = 'Minimum deposit amount is ' . money($minDeposit) . '.';
    }
    if (!$method) {
        $errors[] = 'Please select a valid payment method.';
    }
    if ($txnRef === '') {
        $errors[] = 'Please enter the transaction reference / hash for your transfer.';
    }

    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO deposits (user_id, amount, currency_code, method_label, address_used, txn_reference, note)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$viewer['id'], $amount, $method['currency_code'], $method['currency_name'], $method['address'], $txnRef, $note ?: null]);

        notify($viewer['id'], 'Deposit submitted', 'Your deposit of ' . money($amount) . ' via ' . $method['currency_name'] . ' is pending review.', 'info');

        $admins = db()->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $a) {
            notify((int) $a['id'], 'New deposit pending', $viewer['full_name'] . ' submitted a deposit of ' . money($amount) . '.', 'warning');
        }

        flash('success', 'Your deposit request has been submitted and is awaiting confirmation.');
        redirect('user/deposit.php');
    }
}

$myDeposits = db()->prepare('SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$myDeposits->execute([$viewer['id']]);
$myDeposits = $myDeposits->fetchAll();

$pageTitle = 'Deposit Funds';
$activePage = 'deposit';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="dash-grid">
  <div>
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Fund your account</h3>
          <span class="sub">Choose a currency, send funds, then submit your reference below</span>
        </div>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><span><?= e(implode(' ', $errors)) ?></span></div>
      <?php endif; ?>

      <form method="POST" action="<?= url('user/deposit.php') ?>" data-method-group>
        <?= csrf_field() ?>
        <input type="hidden" name="method" value="<?= e($methods[0]['currency_code'] ?? '') ?>" data-method-input>

        <div class="method-tabs">
          <?php foreach ($methods as $i => $m): ?>
            <div class="method-tab <?= $i === 0 ? 'active' : '' ?>" data-method="<?= e($m['currency_code']) ?>">
              <i class="fa-solid fa-coins"></i> <?= e($m['currency_code']) ?>
            </div>
          <?php endforeach; ?>
        </div>

        <?php foreach ($methods as $i => $m): ?>
          <div class="wallet-box" data-method-panel="<?= e($m['currency_code']) ?>" style="display:<?= $i === 0 ? 'block' : 'none' ?>;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
              <b><?= e($m['currency_name']) ?><?= $m['network'] ? ' &middot; ' . e($m['network']) : '' ?></b>
              <span class="badge badge-primary">Deposit Address</span>
            </div>
            <div class="wallet-address">
              <span><?= e($m['address']) ?></span>
              <button type="button" class="copy-btn" data-copy="<?= e($m['address']) ?>"><i class="fa-regular fa-copy"></i> Copy</button>
            </div>
            <?php if ($m['instructions']): ?><p class="form-hint" style="margin-top:10px;"><?= e($m['instructions']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>

        <div class="form-group">
          <label class="form-label">Amount (USD equivalent)</label>
          <input type="number" step="0.01" min="<?= $minDeposit ?>" name="amount" id="amount" class="form-control" placeholder="Minimum <?= money($minDeposit) ?>" required>
          <div class="quick-amounts" data-amount-target="#amount">
            <button type="button" data-fill="100">$100</button>
            <button type="button" data-fill="500">$500</button>
            <button type="button" data-fill="1000">$1,000</button>
            <button type="button" data-fill="5000">$5,000</button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Transaction reference / hash</label>
          <input type="text" name="txn_reference" class="form-control" placeholder="e.g. transaction hash from your wallet" required>
          <div class="form-hint">This helps our team match your transfer quickly.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Note <span class="text-faint">(optional)</span></label>
          <textarea name="note" class="form-control" rows="2" placeholder="Anything else we should know?"></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">Submit Deposit</button>
      </form>
    </div>
  </div>

  <div>
    <div class="card glass-card" style="margin-bottom:20px;">
      <div class="stat-icon emerald" style="margin-bottom:12px;"><i class="fa-solid fa-circle-info"></i></div>
      <h4 style="margin-bottom:6px;">How approval works</h4>
      <p style="font-size:13.5px;">Once submitted, your deposit appears as <span class="badge badge-warning">Pending</span> until an administrator confirms the transfer. It's then credited instantly to your balance.</p>
    </div>

    <div class="card">
      <h3 class="card-title" style="margin-bottom:14px;">Your Recent Deposits</h3>
      <?php if (!$myDeposits): ?>
        <div class="empty-state"><p>No deposits yet.</p></div>
      <?php else: foreach ($myDeposits as $d): ?>
        <div class="investment-row">
          <div class="inv-plan-icon"><i class="fa-solid fa-coins"></i></div>
          <div class="inv-info">
            <div class="inv-name"><?= money((float) $d['amount']) ?> <span class="text-faint" style="font-weight:400;">&middot; <?= e($d['currency_code']) ?></span></div>
            <div class="inv-sub"><?= time_ago($d['created_at']) ?></div>
          </div>
          <div class="inv-right"><span class="badge <?= badge_class($d['status']) ?>"><?= e($d['status']) ?></span></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/user_footer.php'; ?>
