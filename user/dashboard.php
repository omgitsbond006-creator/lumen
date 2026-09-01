<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();
$userId = $viewer['id'];

$activeInvestments = db()->prepare("SELECT i.*, p.icon FROM investments i LEFT JOIN plans p ON p.id = i.plan_id WHERE i.user_id = ? AND i.status = 'active' ORDER BY i.created_at DESC");
$activeInvestments->execute([$userId]);
$activeInvestments = $activeInvestments->fetchAll();

$activeSum = 0;
foreach ($activeInvestments as $inv) { $activeSum += (float) $inv['amount']; }

$recentTx = db()->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 8');
$recentTx->execute([$userId]);
$recentTx = $recentTx->fetchAll();

$chartRows = db()->prepare('SELECT created_at, balance_after FROM transactions WHERE user_id = ? ORDER BY created_at ASC');
$chartRows->execute([$userId]);
$chartRows = $chartRows->fetchAll();
$chartLabels = array_map(fn($r) => date('M j', strtotime($r['created_at'])), $chartRows);
$chartData = array_map(fn($r) => (float) $r['balance_after'], $chartRows);
if (empty($chartData)) { $chartLabels = ['Today']; $chartData = [(float) $viewer['balance']]; }

$referralCount = db()->prepare('SELECT COUNT(*) FROM users WHERE referred_by = ?');
$referralCount->execute([$userId]);
$referralCount = (int) $referralCount->fetchColumn();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="stat-grid">
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon violet"><i class="fa-solid fa-wallet"></i></div></div>
    <div class="stat-value"><?= money((float) $viewer['balance']) ?></div>
    <div class="stat-label">Available Balance</div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon gold"><i class="fa-solid fa-layer-group"></i></div></div>
    <div class="stat-value"><?= money($activeSum) ?></div>
    <div class="stat-label"><?= count($activeInvestments) ?> Active Investment<?= count($activeInvestments) === 1 ? '' : 's' ?></div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon emerald"><i class="fa-solid fa-sack-dollar"></i></div></div>
    <div class="stat-value text-emerald"><?= money((float) $viewer['total_earned']) ?></div>
    <div class="stat-label">Total Earned (lifetime)</div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon rose"><i class="fa-solid fa-user-plus"></i></div></div>
    <div class="stat-value"><?= $referralCount ?></div>
    <div class="stat-label">Referred Investors</div>
  </div>
</div>

<div class="dash-grid">
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <div>
          <h3 class="card-title">Balance History</h3>
          <span class="sub">Every deposit, payout, and withdrawal, plotted over time</span>
        </div>
      </div>
      <canvas id="balanceChart" height="90"></canvas>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Active Investments</h3>
          <span class="sub">Positions currently earning daily returns</span>
        </div>
        <a href="<?= url('user/my-investments.php') ?>" class="btn btn-ghost btn-sm">View all</a>
      </div>

      <?php if (!$activeInvestments): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="fa-solid fa-seedling"></i></div>
          <p>You don't have any active investments yet.</p>
          <a href="<?= url('user/invest.php') ?>" class="btn btn-primary btn-sm">Start Investing</a>
        </div>
      <?php else: foreach (array_slice($activeInvestments, 0, 5) as $inv):
        $elapsed = time() - strtotime($inv['start_date']);
        $total = strtotime($inv['maturity_date']) - strtotime($inv['start_date']);
        $progress = $total > 0 ? min(100, max(0, round($elapsed / $total * 100))) : 0;
      ?>
        <div class="investment-row">
          <div class="inv-plan-icon"><?= $inv['icon'] ?? '📈' ?></div>
          <div class="inv-info">
            <div class="inv-name"><?= e($inv['plan_name']) ?></div>
            <div class="inv-sub"><?= money((float) $inv['amount']) ?> &middot; matures <?= format_date($inv['maturity_date']) ?></div>
            <div class="progress" style="margin-top:8px;max-width:260px;"><div class="progress-bar" style="width:<?= $progress ?>%;"></div></div>
          </div>
          <div class="inv-right">
            <div class="text-emerald fw-600"><?= money((float) $inv['paid_out'], true) ?></div>
            <div class="text-faint" style="font-size:12px;"><?= $progress ?>% complete</div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:20px;">
      <h3 class="card-title" style="margin-bottom:16px;">Quick Actions</h3>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <a href="<?= url('user/deposit.php') ?>" class="btn btn-primary btn-block"><i class="fa-solid fa-arrow-down"></i> Deposit Funds</a>
        <a href="<?= url('user/invest.php') ?>" class="btn btn-outline btn-block"><i class="fa-solid fa-seedling"></i> New Investment</a>
        <a href="<?= url('user/withdraw.php') ?>" class="btn btn-outline btn-block"><i class="fa-solid fa-arrow-up"></i> Withdraw</a>
      </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <h3 class="card-title">Recent Activity</h3>
        <a href="<?= url('user/transactions.php') ?>" class="btn btn-ghost btn-sm">View all</a>
      </div>
      <?php if (!$recentTx): ?>
        <div class="empty-state"><p>No transactions yet.</p></div>
      <?php else: foreach ($recentTx as $tx): ?>
        <div class="timeline-item">
          <div class="timeline-dot <?= $tx['amount'] >= 0 ? 'success' : 'warning' ?>"></div>
          <div style="flex:1;">
            <div style="display:flex;justify-content:space-between;gap:10px;">
              <span style="font-size:13.5px;font-weight:600;"><?= e($tx['description'] ?: ucwords(str_replace('_', ' ', $tx['type']))) ?></span>
              <span class="mono fw-600 <?= $tx['amount'] >= 0 ? 'text-emerald' : 'text-red' ?>" style="white-space:nowrap;"><?= money((float) $tx['amount'], true) ?></span>
            </div>
            <div class="text-faint" style="font-size:12px;"><?= time_ago($tx['created_at']) ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="card glass-card">
      <div class="stat-icon gold" style="margin-bottom:12px;"><i class="fa-solid fa-gift"></i></div>
      <h4 style="margin-bottom:6px;">Refer &amp; Earn</h4>
      <p style="font-size:13px;">Earn <?= e(get_setting('referral_percent', '5')) ?>% when someone you refer makes their first deposit.</p>
      <a href="<?= url('user/referrals.php') ?>" class="btn btn-gold btn-sm btn-block">My Referral Link</a>
    </div>
  </div>
</div>

<?php
$extraScript = '<script>
new Chart(document.getElementById("balanceChart"), {
  type: "line",
  data: {
    labels: ' . json_encode($chartLabels) . ',
    datasets: [{
      data: ' . json_encode($chartData) . ',
      borderColor: "#8b7bff",
      backgroundColor: "rgba(109,91,240,0.12)",
      fill: true, tension: 0.35, pointRadius: 0, borderWidth: 2.5,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: {
      x: { ticks: { color: "#5c6684", maxTicksLimit: 8 }, grid: { display: false } },
      y: { ticks: { color: "#5c6684", callback: (v) => "$" + v.toLocaleString() }, grid: { color: "rgba(255,255,255,0.05)" } },
    }
  }
});
</script>';
require __DIR__ . '/../partials/user_footer.php';
?>
