<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

$totalUsers = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalBalanceHeld = (float) db()->query("SELECT COALESCE(SUM(balance),0) FROM users WHERE role = 'user'")->fetchColumn();
$totalDeposited = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM deposits WHERE status = 'approved'")->fetchColumn();
$totalWithdrawn = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'approved'")->fetchColumn();
$totalPaidOut = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type IN ('roi_payout','maturity_payout')")->fetchColumn();
$activeInvestments = db()->query("SELECT COUNT(*) c, COALESCE(SUM(amount),0) s FROM investments WHERE status = 'active'")->fetch();
$pendingDeposits = db()->query("SELECT COUNT(*) c, COALESCE(SUM(amount),0) s FROM deposits WHERE status = 'pending'")->fetch();
$pendingWithdrawals = db()->query("SELECT COUNT(*) c, COALESCE(SUM(amount),0) s FROM withdrawals WHERE status = 'pending'")->fetch();
$openTickets = (int) db()->query("SELECT COUNT(*) FROM support_messages WHERE status = 'open'")->fetchColumn();

// Deposits vs withdrawals, last 14 days
$days = [];
for ($i = 13; $i >= 0; $i--) { $days[] = date('Y-m-d', strtotime("-{$i} days")); }
$depByDay = db()->query("SELECT DATE(created_at) d, COALESCE(SUM(amount),0) s FROM deposits WHERE status='approved' AND created_at >= NOW() - INTERVAL 14 DAY GROUP BY DATE(created_at)")->fetchAll(PDO::FETCH_KEY_PAIR);
$wdByDay = db()->query("SELECT DATE(created_at) d, COALESCE(SUM(amount),0) s FROM withdrawals WHERE status='approved' AND created_at >= NOW() - INTERVAL 14 DAY GROUP BY DATE(created_at)")->fetchAll(PDO::FETCH_KEY_PAIR);
$depSeries = array_map(fn($d) => (float) ($depByDay[$d] ?? 0), $days);
$wdSeries = array_map(fn($d) => (float) ($wdByDay[$d] ?? 0), $days);
$dayLabels = array_map(fn($d) => date('M j', strtotime($d)), $days);

// Plan popularity
$planPop = db()->query("SELECT plan_name, COUNT(*) c FROM investments GROUP BY plan_name ORDER BY c DESC")->fetchAll();

$recentDeposits = db()->query("SELECT d.*, u.full_name FROM deposits d JOIN users u ON u.id = d.user_id WHERE d.status='pending' ORDER BY d.created_at DESC LIMIT 5")->fetchAll();
$recentWithdrawals = db()->query("SELECT w.*, u.full_name FROM withdrawals w JOIN users u ON u.id = w.user_id WHERE w.status='pending' ORDER BY w.created_at DESC LIMIT 5")->fetchAll();
$recentUsers = db()->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Overview';
$activePage = 'dashboard';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="stat-grid">
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon violet"><i class="fa-solid fa-users"></i></div></div>
    <div class="stat-value"><?= number_format($totalUsers) ?></div>
    <div class="stat-label">Registered Investors</div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon gold"><i class="fa-solid fa-vault"></i></div></div>
    <div class="stat-value"><?= money($totalBalanceHeld) ?></div>
    <div class="stat-label">Total Balance Held</div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon emerald"><i class="fa-solid fa-arrow-down"></i></div></div>
    <div class="stat-value"><?= money($totalDeposited) ?></div>
    <div class="stat-label">Total Deposited</div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon rose"><i class="fa-solid fa-arrow-up"></i></div></div>
    <div class="stat-value"><?= money($totalWithdrawn) ?></div>
    <div class="stat-label">Total Withdrawn</div>
  </div>
</div>

<div class="stat-grid">
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon violet"><i class="fa-solid fa-layer-group"></i></div></div>
    <div class="stat-value"><?= (int) $activeInvestments['c'] ?></div>
    <div class="stat-label">Active Investments &middot; <?= money((float) $activeInvestments['s']) ?></div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon gold"><i class="fa-solid fa-hourglass-half"></i></div></div>
    <div class="stat-value"><?= (int) $pendingDeposits['c'] ?></div>
    <div class="stat-label">Pending Deposits &middot; <?= money((float) $pendingDeposits['s']) ?></div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon rose"><i class="fa-solid fa-hourglass-half"></i></div></div>
    <div class="stat-value"><?= (int) $pendingWithdrawals['c'] ?></div>
    <div class="stat-label">Pending Withdrawals &middot; <?= money((float) $pendingWithdrawals['s']) ?></div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon emerald"><i class="fa-solid fa-headset"></i></div></div>
    <div class="stat-value"><?= $openTickets ?></div>
    <div class="stat-label">Open Support Tickets</div>
  </div>
</div>

<div class="card glass-card" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
  <div>
    <h3 class="card-title" style="margin-bottom:4px;">Payout Engine</h3>
    <span class="sub">Manually process due daily payouts and matured investments (in production this runs via cron).</span>
  </div>
  <a href="<?= url('admin/run-payouts.php') ?>" class="btn btn-gold"><i class="fa-solid fa-bolt"></i> Run Payouts Now</a>
</div>

<div class="dash-grid" style="margin-bottom:20px;">
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Deposits vs Withdrawals</h3>
        <span class="sub">Last 14 days, approved amounts</span>
      </div>
    </div>
    <canvas id="flowChart" height="110"></canvas>
  </div>
  <div class="card">
    <div class="card-header"><h3 class="card-title">Plan Popularity</h3></div>
    <canvas id="planChart" height="200"></canvas>
  </div>
</div>

<div class="dash-grid">
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <h3 class="card-title">Pending Deposits</h3>
        <a href="<?= url('admin/deposits.php') ?>" class="btn btn-ghost btn-sm">Review all</a>
      </div>
      <?php if (!$recentDeposits): ?>
        <div class="empty-state"><p>No pending deposits.</p></div>
      <?php else: foreach ($recentDeposits as $d): ?>
        <div class="investment-row">
          <div class="inv-plan-icon"><i class="fa-solid fa-coins"></i></div>
          <div class="inv-info">
            <div class="inv-name"><?= e($d['full_name']) ?></div>
            <div class="inv-sub"><?= money((float) $d['amount']) ?> &middot; <?= e($d['currency_code']) ?> &middot; <?= time_ago($d['created_at']) ?></div>
          </div>
          <div class="inv-right"><span class="badge badge-warning">Pending</span></div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Pending Withdrawals</h3>
        <a href="<?= url('admin/withdrawals.php') ?>" class="btn btn-ghost btn-sm">Review all</a>
      </div>
      <?php if (!$recentWithdrawals): ?>
        <div class="empty-state"><p>No pending withdrawals.</p></div>
      <?php else: foreach ($recentWithdrawals as $w): ?>
        <div class="investment-row">
          <div class="inv-plan-icon"><i class="fa-solid fa-arrow-up"></i></div>
          <div class="inv-info">
            <div class="inv-name"><?= e($w['full_name']) ?></div>
            <div class="inv-sub"><?= money((float) $w['amount']) ?> &middot; <?= e($w['method_label']) ?> &middot; <?= time_ago($w['created_at']) ?></div>
          </div>
          <div class="inv-right"><span class="badge badge-warning">Pending</span></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Newest Investors</h3>
        <a href="<?= url('admin/users.php') ?>" class="btn btn-ghost btn-sm">All users</a>
      </div>
      <?php foreach ($recentUsers as $u): ?>
        <div class="investment-row">
          <span class="avatar-circle sm"><?= e(avatar_initials($u['full_name'])) ?></span>
          <div class="inv-info" style="margin-left:6px;">
            <div class="inv-name"><?= e($u['full_name']) ?></div>
            <div class="inv-sub"><?= time_ago($u['created_at']) ?></div>
          </div>
          <div class="inv-right"><span class="badge <?= badge_class($u['status']) ?>"><?= e($u['status']) ?></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php
$extraScript = '<script>
new Chart(document.getElementById("flowChart"), {
  type: "bar",
  data: {
    labels: ' . json_encode($dayLabels) . ',
    datasets: [
      { label: "Deposits", data: ' . json_encode($depSeries) . ', backgroundColor: "#22c55e" },
      { label: "Withdrawals", data: ' . json_encode($wdSeries) . ', backgroundColor: "#f0465c" },
    ]
  },
  options: {
    plugins: { legend: { labels: { color: "#96a0b8" } } },
    scales: {
      x: { stacked: false, ticks: { color: "#5c6684" }, grid: { display: false } },
      y: { ticks: { color: "#5c6684", callback: v => "$" + v.toLocaleString() }, grid: { color: "rgba(255,255,255,0.05)" } },
    }
  }
});
new Chart(document.getElementById("planChart"), {
  type: "doughnut",
  data: {
    labels: ' . json_encode(array_column($planPop, 'plan_name')) . ',
    datasets: [{ data: ' . json_encode(array_map('intval', array_column($planPop, 'c'))) . ', backgroundColor: ["#6d5bf0","#f0b90b","#22c55e","#fb7185","#6366f1"] }]
  },
  options: { plugins: { legend: { position: "bottom", labels: { color: "#96a0b8", boxWidth: 12, font: {size: 11} } } } }
});
</script>';
require __DIR__ . '/../partials/admin_footer.php';
?>
