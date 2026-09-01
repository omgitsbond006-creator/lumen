<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
require_once __DIR__ . '/../cron/process_payouts.php';
$viewer = current_user();

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $result = process_all_due_payouts();
    log_activity($viewer['id'], 'Ran payout engine', null, null,
        "{$result['payouts']} daily payouts (" . money($result['payout_total']) . "), {$result['matured']} matured (" . money($result['matured_total']) . ')');
    flash('success', 'Payout engine ran successfully.');
}

$dueNow = (int) db()->query("SELECT COUNT(*) FROM investments WHERE status='active' AND payout_type='daily' AND next_payout_at IS NOT NULL AND next_payout_at <= NOW() AND next_payout_at < maturity_date")->fetchColumn();
$maturingNow = (int) db()->query("SELECT COUNT(*) FROM investments WHERE status='active' AND maturity_date <= NOW()")->fetchColumn();
$nextUp = db()->query("SELECT plan_name, next_payout_at FROM investments WHERE status='active' AND payout_type='daily' AND next_payout_at IS NOT NULL ORDER BY next_payout_at ASC LIMIT 5")->fetchAll();

$pageTitle = 'Run Payouts';
$activePage = 'dashboard';
require __DIR__ . '/../partials/admin_header.php';
?>

<a href="<?= url('admin/index.php') ?>" class="btn btn-ghost btn-sm" style="margin-bottom:16px;"><i class="fa-solid fa-arrow-left"></i> Overview</a>

<?php if ($result): ?>
  <div class="card glass-card" style="margin-bottom:20px;border-color:rgba(34,197,94,.4);">
    <h3 style="margin-bottom:14px;"><i class="fa-solid fa-circle-check text-emerald"></i> Payout run complete</h3>
    <div class="stat-grid" style="margin-bottom:0;">
      <div class="stat-card"><div class="stat-value text-emerald"><?= $result['payouts'] ?></div><div class="stat-label">Daily payouts credited</div></div>
      <div class="stat-card"><div class="stat-value text-emerald"><?= money($result['payout_total']) ?></div><div class="stat-label">Total daily payout amount</div></div>
      <div class="stat-card"><div class="stat-value text-gold"><?= $result['matured'] ?></div><div class="stat-label">Investments matured</div></div>
      <div class="stat-card"><div class="stat-value text-gold"><?= money($result['matured_total']) ?></div><div class="stat-label">Total matured amount</div></div>
    </div>
    <?php if ($result['errors']): ?>
      <div class="alert alert-danger" style="margin-top:16px;flex-direction:column;align-items:stretch;">
        <b>Some items could not be processed:</b>
        <ul style="margin:8px 0 0;"><?php foreach ($result['errors'] as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="dash-grid">
  <div>
    <div class="card">
      <h3 class="card-title" style="margin-bottom:6px;">Payout Engine</h3>
      <p style="margin-bottom:20px;">This simulates what a scheduled cron job (<code>cron/process_payouts.php</code>) would do automatically: credit daily ROI to every active investment that's due, and mature any investment whose term has ended — returning principal plus any remaining profit to the investor's balance.</p>

      <div class="stat-grid" style="margin-bottom:24px;">
        <div class="stat-card card-sm" style="background:var(--bg-elevated);">
          <div class="stat-value"><?= $dueNow ?></div>
          <div class="stat-label">Daily payouts due right now</div>
        </div>
        <div class="stat-card card-sm" style="background:var(--bg-elevated);">
          <div class="stat-value"><?= $maturingNow ?></div>
          <div class="stat-label">Investments ready to mature</div>
        </div>
      </div>

      <form method="POST" action="<?= url('admin/run-payouts.php') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-gold btn-lg btn-block"><i class="fa-solid fa-bolt"></i> Run Payout Engine Now</button>
      </form>
    </div>
  </div>
  <div>
    <div class="card">
      <h3 class="card-title" style="margin-bottom:14px;">Next Payouts Due</h3>
      <?php if (!$nextUp): ?>
        <div class="empty-state"><p>No active daily-payout investments.</p></div>
      <?php else: foreach ($nextUp as $n): ?>
        <div class="investment-row">
          <div class="inv-plan-icon"><i class="fa-solid fa-clock"></i></div>
          <div class="inv-info">
            <div class="inv-name"><?= e($n['plan_name']) ?></div>
            <div class="inv-sub"><?= format_date($n['next_payout_at'], 'M j, g:i A') ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
