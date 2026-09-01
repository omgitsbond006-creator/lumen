<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();
$errors = [];

$plans = db()->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll();
$selectedPlanId = (int) ($_GET['plan'] ?? $_POST['plan_id'] ?? ($plans[0]['id'] ?? 0));
$prefillAmount = isset($_GET['amount']) ? (float) $_GET['amount'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $planId = (int) ($_POST['plan_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);

    $plan = null;
    foreach ($plans as $p) { if ((int) $p['id'] === $planId) { $plan = $p; break; } }

    if (!$plan) {
        $errors[] = 'Please select a valid plan.';
    } else {
        if ($amount < (float) $plan['min_amount'] || $amount > (float) $plan['max_amount']) {
            $errors[] = 'Amount must be between ' . money((float) $plan['min_amount']) . ' and ' . money((float) $plan['max_amount']) . ' for ' . $plan['name'] . '.';
        }
        if ($amount > (float) $viewer['balance']) {
            $errors[] = 'Insufficient balance. Please deposit more funds first.';
        }
    }

    if (!$errors) {
        $pdo = db();
        try {
            // record_transaction() manages its own atomic balance update + ledger entry.
            record_transaction($viewer['id'], 'investment', -$amount, 'investment', null, 'Invested in ' . $plan['name']);

            $expectedReturn = round($amount * ((float) $plan['roi_percent'] / 100) * (int) $plan['duration_days'], 2);
            $startDate = date('Y-m-d H:i:s');
            $maturityDate = date('Y-m-d H:i:s', strtotime('+' . (int) $plan['duration_days'] . ' days'));
            $nextPayout = $plan['payout_type'] === 'daily' ? date('Y-m-d H:i:s', strtotime('+1 day')) : null;

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO investments (user_id, plan_id, plan_name, amount, roi_percent, payout_type, duration_days, start_date, maturity_date, expected_return, next_payout_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $viewer['id'], $plan['id'], $plan['name'], $amount, $plan['roi_percent'], $plan['payout_type'],
                $plan['duration_days'], $startDate, $maturityDate, $expectedReturn, $nextPayout,
            ]);
            $investmentId = (int) $pdo->lastInsertId();

            // Backfill the reference_id on the ledger row record_transaction() just created.
            $pdo->prepare("UPDATE transactions SET reference_id = ? WHERE user_id = ? AND type = 'investment' AND reference_id IS NULL ORDER BY id DESC LIMIT 1")
                ->execute([$investmentId, $viewer['id']]);
            $pdo->commit();

            notify($viewer['id'], 'Investment started', 'You invested ' . money($amount) . ' in ' . $plan['name'] . '. It matures on ' . format_date($maturityDate) . '.', 'success');
            flash('success', 'Your investment in ' . $plan['name'] . ' is now active.');
            redirect('user/my-investments.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = 'Something went wrong processing your investment. Please try again.';
        }
    }
}

$pageTitle = 'New Investment';
$activePage = 'invest';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="card" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <div>
      <h3 class="card-title">Available Balance</h3>
      <span class="sub">Funds ready to invest</span>
    </div>
    <div class="stat-value"><?= money((float) $viewer['balance']) ?></div>
  </div>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><span><?= e(implode(' ', $errors)) ?></span></div>
<?php endif; ?>

<?php if ((float) $viewer['balance'] <= 0): ?>
  <div class="empty-state card">
    <div class="empty-icon"><i class="fa-solid fa-wallet"></i></div>
    <p>You need funds in your balance before you can invest.</p>
    <a href="<?= url('user/deposit.php') ?>" class="btn btn-primary btn-sm">Deposit Funds</a>
  </div>
<?php else: ?>

<form method="POST" action="<?= url('user/invest.php') ?>" id="investForm">
  <?= csrf_field() ?>
  <input type="hidden" name="plan_id" id="planIdInput" value="<?= $selectedPlanId ?>">

  <div class="plans-grid" style="margin-bottom:24px;">
    <?php foreach ($plans as $plan): ?>
      <div class="plan-card select-plan <?= (int) $plan['id'] === $selectedPlanId ? 'featured' : '' ?>"
           data-plan-id="<?= (int) $plan['id'] ?>"
           data-roi="<?= (float) $plan['roi_percent'] ?>"
           data-days="<?= (int) $plan['duration_days'] ?>"
           data-min="<?= (float) $plan['min_amount'] ?>"
           data-max="<?= (float) $plan['max_amount'] ?>"
           style="cursor:pointer;">
        <?php if ((int) $plan['id'] === $selectedPlanId): ?><div class="plan-featured-tag">Selected</div><?php endif; ?>
        <div class="plan-icon"><?= $plan['icon'] ?></div>
        <div class="plan-name"><?= e($plan['name']) ?></div>
        <div class="plan-tagline"><?= e($plan['tagline']) ?></div>
        <div class="plan-roi"><?= percent((float) $plan['roi_percent']) ?> <span>/ day</span></div>
        <ul class="plan-meta">
          <li>Duration <b><?= (int) $plan['duration_days'] ?> days</b></li>
          <li>Range <b><?= money((float) $plan['min_amount']) ?> &ndash; <?= money((float) $plan['max_amount']) ?></b></li>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h3 class="card-title" style="margin-bottom:16px;">Investment amount</h3>
    <div class="form-group">
      <input type="number" step="0.01" name="amount" id="amountInput" class="form-control" placeholder="Enter amount" value="<?= $prefillAmount !== null ? e((string) $prefillAmount) : '' ?>">
      <div class="form-hint" id="rangeHint"></div>
    </div>
    <div class="quick-amounts" data-amount-target="#amountInput" style="margin-bottom:20px;">
      <button type="button" data-fill="max" data-max="<?= (float) $viewer['balance'] ?>">Use Max Balance</button>
    </div>

    <div class="card-sm" style="background:var(--bg-elevated);margin-bottom:20px;">
      <div style="display:flex;justify-content:space-between;margin-bottom:12px;"><span class="text-faint">Daily payout</span><b id="previewDaily">$0.00</b></div>
      <div style="display:flex;justify-content:space-between;margin-bottom:12px;"><span class="text-faint">Total profit</span><b class="text-emerald" id="previewProfit">$0.00</b></div>
      <div style="display:flex;justify-content:space-between;"><span class="text-faint">Matures on</span><b id="previewMaturity">&mdash;</b></div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">Confirm Investment</button>
  </div>
</form>

<?php endif; ?>

<?php
$extraScript = <<<'HTML'
<script>
(function () {
  var cards = document.querySelectorAll('.select-plan');
  var planIdInput = document.getElementById('planIdInput');
  var amountInput = document.getElementById('amountInput');
  var rangeHint = document.getElementById('rangeHint');
  var previewDaily = document.getElementById('previewDaily');
  var previewProfit = document.getElementById('previewProfit');
  var previewMaturity = document.getElementById('previewMaturity');
  if (!cards.length) return;

  function fmt(n) { return '$' + n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }

  function activeCard() {
    return document.querySelector('.select-plan[data-plan-id="' + planIdInput.value + '"]') || cards[0];
  }

  function recalc() {
    var card = activeCard();
    var roi = parseFloat(card.getAttribute('data-roi'));
    var days = parseInt(card.getAttribute('data-days'), 10);
    var min = parseFloat(card.getAttribute('data-min'));
    var max = parseFloat(card.getAttribute('data-max'));
    var amt = parseFloat(amountInput.value) || 0;
    rangeHint.textContent = 'Accepts ' + fmt(min) + ' – ' + fmt(max);
    previewDaily.textContent = fmt(amt * (roi / 100));
    previewProfit.textContent = fmt(amt * (roi / 100) * days);
    var d = new Date();
    d.setDate(d.getDate() + days);
    previewMaturity.textContent = d.toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});
  }

  cards.forEach(function (card) {
    card.addEventListener('click', function () {
      cards.forEach(function (c) { c.classList.remove('featured'); c.querySelector('.plan-featured-tag')?.remove(); });
      card.classList.add('featured');
      var tag = document.createElement('div');
      tag.className = 'plan-featured-tag';
      tag.textContent = 'Selected';
      card.prepend(tag);
      planIdInput.value = card.getAttribute('data-plan-id');
      recalc();
    });
  });
  amountInput.addEventListener('input', recalc);
  recalc();
})();
</script>
HTML;
require __DIR__ . '/../partials/user_footer.php';
?>
