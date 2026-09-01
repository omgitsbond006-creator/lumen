<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fields = ['site_name', 'site_tagline', 'support_email', 'referral_percent', 'withdrawal_fee_percent', 'min_deposit', 'min_withdrawal'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            set_setting($field, trim($_POST[$field]));
        }
    }
    set_setting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
    log_activity($viewer['id'], 'Updated site settings');
    flash('success', 'Settings saved.');
    redirect('admin/settings.php');
}

$outbox = db()->query('SELECT * FROM sent_emails ORDER BY created_at DESC LIMIT 10')->fetchAll();

$pageTitle = 'Site Settings';
$activePage = 'settings';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="dash-grid">
  <div>
    <div class="card">
      <h3 class="card-title" style="margin-bottom:16px;">Platform Settings</h3>
      <form method="POST" action="<?= url('admin/settings.php') ?>">
        <?= csrf_field() ?>
        <div class="form-group"><label class="form-label">Site name</label><input type="text" name="site_name" class="form-control" value="<?= e(get_setting('site_name')) ?>"></div>
        <div class="form-group"><label class="form-label">Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?= e(get_setting('site_tagline')) ?>"></div>
        <div class="form-group"><label class="form-label">Support email</label><input type="email" name="support_email" class="form-control" value="<?= e(get_setting('support_email')) ?>"></div>
        <div class="dash-grid" style="grid-template-columns:1fr 1fr;">
          <div class="form-group"><label class="form-label">Referral bonus (%)</label><input type="number" step="0.01" name="referral_percent" class="form-control" value="<?= e(get_setting('referral_percent')) ?>"></div>
          <div class="form-group"><label class="form-label">Withdrawal fee (%)</label><input type="number" step="0.01" name="withdrawal_fee_percent" class="form-control" value="<?= e(get_setting('withdrawal_fee_percent')) ?>"></div>
        </div>
        <div class="dash-grid" style="grid-template-columns:1fr 1fr;">
          <div class="form-group"><label class="form-label">Minimum deposit ($)</label><input type="number" step="0.01" name="min_deposit" class="form-control" value="<?= e(get_setting('min_deposit')) ?>"></div>
          <div class="form-group"><label class="form-label">Minimum withdrawal ($)</label><input type="number" step="0.01" name="min_withdrawal" class="form-control" value="<?= e(get_setting('min_withdrawal')) ?>"></div>
        </div>
        <div class="checkbox-row" style="margin-bottom:22px;">
          <input type="checkbox" name="maintenance_mode" id="maintenance_mode" <?= get_setting('maintenance_mode') === '1' ? 'checked' : '' ?>>
          <label for="maintenance_mode">Maintenance mode (informational flag only in this demo build)</label>
        </div>
        <button type="submit" class="btn btn-primary">Save Settings</button>
      </form>
    </div>
  </div>

  <div>
    <div class="card glass-card" style="margin-bottom:20px;">
      <div class="stat-icon violet" style="margin-bottom:12px;"><i class="fa-solid fa-user-shield"></i></div>
      <h4 style="margin-bottom:6px;">Signed in as</h4>
      <p style="font-size:13.5px;"><?= e($viewer['full_name']) ?><br><?= e($viewer['email']) ?></p>
    </div>

    <div class="card">
      <h3 class="card-title" style="margin-bottom:14px;">Outbound Email Log</h3>
      <p class="text-faint" style="font-size:12.5px;margin-bottom:14px;">No SMTP server is configured for this demo — outbound emails (welcome messages, password resets) are logged here instead of actually being sent.</p>
      <?php if (!$outbox): ?>
        <div class="empty-state"><p>No emails sent yet.</p></div>
      <?php else: foreach ($outbox as $mail): ?>
        <div class="card-sm" style="background:var(--bg-elevated);margin-bottom:10px;">
          <div style="display:flex;justify-content:space-between;gap:8px;"><b style="font-size:13px;"><?= e($mail['subject']) ?></b><span class="text-faint" style="font-size:11px;"><?= time_ago($mail['created_at']) ?></span></div>
          <div class="text-faint" style="font-size:12px;margin:4px 0;">To: <?= e($mail['to_email']) ?></div>
          <div style="font-size:12.5px;white-space:pre-wrap;"><?= e(mb_strimwidth($mail['body'], 0, 220, '…')) ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
