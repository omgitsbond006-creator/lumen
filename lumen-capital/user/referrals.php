<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();

$referralLink = url('register.php?ref=' . $viewer['referral_code']);
$referralPercent = get_setting('referral_percent', '5');

$referred = db()->prepare('SELECT id, full_name, email, total_deposited, created_at FROM users WHERE referred_by = ? ORDER BY created_at DESC');
$referred->execute([$viewer['id']]);
$referred = $referred->fetchAll();

$bonusEarned = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ? AND type = 'referral_bonus'");
$bonusEarned->execute([$viewer['id']]);
$bonusEarned = (float) $bonusEarned->fetchColumn();

$pageTitle = 'Referrals';
$activePage = 'referrals';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="stat-grid">
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon violet"><i class="fa-solid fa-user-group"></i></div></div>
    <div class="stat-value"><?= count($referred) ?></div>
    <div class="stat-label">Total Referrals</div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon gold"><i class="fa-solid fa-sack-dollar"></i></div></div>
    <div class="stat-value text-gold"><?= money($bonusEarned) ?></div>
    <div class="stat-label">Bonus Earned</div>
  </div>
  <div class="card stat-card">
    <div class="stat-top"><div class="stat-icon emerald"><i class="fa-solid fa-percent"></i></div></div>
    <div class="stat-value"><?= e($referralPercent) ?>%</div>
    <div class="stat-label">Commission Rate</div>
  </div>
</div>

<div class="card glass-card" style="margin-bottom:20px;">
  <h3 class="card-title" style="margin-bottom:6px;">Your referral link</h3>
  <p style="margin-bottom:16px;">Share this link — when someone signs up and their first deposit is approved, you earn <?= e($referralPercent) ?>% of that deposit as a bonus.</p>
  <div class="wallet-address">
    <span style="word-break:break-all;"><?= e($referralLink) ?></span>
    <button type="button" class="copy-btn" data-copy="<?= e($referralLink) ?>"><i class="fa-regular fa-copy"></i> Copy Link</button>
  </div>
  <div style="margin-top:14px;display:flex;gap:10px;align-items:center;">
    <span class="text-faint" style="font-size:13px;">Your code:</span>
    <span class="badge badge-primary" style="font-size:13px;"><?= e($viewer['referral_code']) ?></span>
  </div>
</div>

<div class="card">
  <h3 class="card-title" style="margin-bottom:14px;">People you've referred</h3>
  <?php if (!$referred): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fa-solid fa-user-plus"></i></div>
      <p>No referrals yet. Share your link to start earning.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Name</th><th>Joined</th><th>Total Deposited</th></tr></thead>
        <tbody>
        <?php foreach ($referred as $r): ?>
          <tr>
            <td style="display:flex;align-items:center;gap:10px;">
              <span class="avatar-circle sm"><?= e(avatar_initials($r['full_name'])) ?></span>
              <?= e($r['full_name']) ?>
            </td>
            <td class="text-faint"><?= format_date($r['created_at']) ?></td>
            <td class="mono"><?= money((float) $r['total_deposited']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/user_footer.php'; ?>
