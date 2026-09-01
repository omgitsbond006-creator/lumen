<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();

$filter = $_GET['status'] ?? 'all';
$allowed = ['all', 'active', 'completed', 'cancelled'];
if (!in_array($filter, $allowed, true)) $filter = 'all';

$sql = 'SELECT i.*, p.icon, p.is_active AS plan_is_active FROM investments i LEFT JOIN plans p ON p.id = i.plan_id WHERE i.user_id = ?';
$params = [$viewer['id']];
if ($filter !== 'all') { $sql .= ' AND i.status = ?'; $params[] = $filter; }
$sql .= ' ORDER BY i.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$investments = $stmt->fetchAll();

$counts = db()->prepare("SELECT status, COUNT(*) c FROM investments WHERE user_id = ? GROUP BY status");
$counts->execute([$viewer['id']]);
$countMap = ['active' => 0, 'completed' => 0, 'cancelled' => 0];
foreach ($counts->fetchAll() as $row) { $countMap[$row['status']] = (int) $row['c']; }
$totalCount = array_sum($countMap);

$pageTitle = 'My Investments';
$activePage = 'investments';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="tabs">
  <a href="?status=all" class="tab-link <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $totalCount ?>)</a>
  <a href="?status=active" class="tab-link <?= $filter === 'active' ? 'active' : '' ?>">Active (<?= $countMap['active'] ?>)</a>
  <a href="?status=completed" class="tab-link <?= $filter === 'completed' ? 'active' : '' ?>">Completed (<?= $countMap['completed'] ?>)</a>
  <a href="?status=cancelled" class="tab-link <?= $filter === 'cancelled' ? 'active' : '' ?>">Cancelled (<?= $countMap['cancelled'] ?>)</a>
</div>

<?php if (!$investments): ?>
  <div class="card empty-state">
    <div class="empty-icon"><i class="fa-solid fa-chart-pie"></i></div>
    <p>No investments to show here yet.</p>
    <a href="<?= url('user/invest.php') ?>" class="btn btn-primary btn-sm">Start Investing</a>
  </div>
<?php else: ?>
  <div style="display:flex;flex-direction:column;gap:16px;">
    <?php foreach ($investments as $inv):
      $elapsed = time() - strtotime($inv['start_date']);
      $total = strtotime($inv['maturity_date']) - strtotime($inv['start_date']);
      $progress = $total > 0 ? min(100, max(0, round($elapsed / $total * 100))) : 100;
      $daysLeft = max(0, ceil((strtotime($inv['maturity_date']) - time()) / 86400));
    ?>
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
          <div style="display:flex;gap:14px;">
            <div class="inv-plan-icon" style="width:52px;height:52px;font-size:22px;"><?= $inv['icon'] ?? '📈' ?></div>
            <div>
              <div style="font-weight:700;font-size:16px;"><?= e($inv['plan_name']) ?> <span class="badge <?= badge_class($inv['status']) ?>" style="margin-left:6px;"><?= e($inv['status']) ?></span></div>
              <div class="text-faint" style="font-size:13px;">Started <?= format_date($inv['start_date']) ?> &middot; <?= percent((float) $inv['roi_percent']) ?>/day &middot; <?= (int) $inv['duration_days'] ?> day term</div>
            </div>
          </div>
          <?php if ($inv['status'] === 'active'): ?>
            <div class="countdown" data-countdown="<?= date('c', strtotime($inv['maturity_date'])) ?>">
              <div class="unit"><b data-u="d">00</b><span>Days</span></div>
              <div class="unit"><b data-u="h">00</b><span>Hrs</span></div>
              <div class="unit"><b data-u="m">00</b><span>Min</span></div>
              <div class="unit"><b data-u="s">00</b><span>Sec</span></div>
            </div>
          <?php elseif ($inv['status'] === 'completed' && $inv['plan_is_active']): ?>
            <a href="<?= e(url('user/invest.php?plan=' . $inv['plan_id'] . '&amount=' . $inv['amount'])) ?>" class="btn btn-gold btn-sm"><i class="fa-solid fa-arrow-rotate-right"></i> Reinvest</a>
          <?php endif; ?>
        </div>

        <div class="progress" style="margin:18px 0 6px;"><div class="progress-bar <?= $inv['status'] === 'completed' ? 'emerald' : '' ?>" style="width:<?= $progress ?>%;"></div></div>
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-faint);margin-bottom:18px;">
          <span><?= $progress ?>% of term elapsed</span>
          <span><?= $inv['status'] === 'active' ? $daysLeft . ' day(s) remaining' : 'Matured ' . format_date($inv['maturity_date']) ?></span>
        </div>

        <div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:0;gap:14px;">
          <div class="card-sm" style="background:var(--bg-elevated);">
            <div class="text-faint" style="font-size:12px;">Principal</div>
            <div class="fw-700"><?= money((float) $inv['amount']) ?></div>
          </div>
          <div class="card-sm" style="background:var(--bg-elevated);">
            <div class="text-faint" style="font-size:12px;">Paid Out</div>
            <div class="fw-700 text-emerald"><?= money((float) $inv['paid_out']) ?></div>
          </div>
          <div class="card-sm" style="background:var(--bg-elevated);">
            <div class="text-faint" style="font-size:12px;">Expected Total</div>
            <div class="fw-700 text-gold"><?= money((float) $inv['expected_return']) ?></div>
          </div>
          <div class="card-sm" style="background:var(--bg-elevated);">
            <div class="text-faint" style="font-size:12px;">Maturity Date</div>
            <div class="fw-700"><?= format_date($inv['maturity_date']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/user_footer.php'; ?>
