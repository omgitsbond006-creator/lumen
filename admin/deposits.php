<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $depositId = (int) ($_POST['deposit_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note = trim($_POST['admin_note'] ?? '');

    $stmt = db()->prepare('SELECT * FROM deposits WHERE id = ?');
    $stmt->execute([$depositId]);
    $deposit = $stmt->fetch();

    if (!$deposit || $deposit['status'] !== 'pending') {
        flash('danger', 'This deposit has already been processed.');
        redirect('admin/deposits.php');
    }

    $user = find_user_by_id((int) $deposit['user_id']);

    if ($action === 'approve') {
        try {
            record_transaction((int) $deposit['user_id'], 'deposit', (float) $deposit['amount'], 'deposit', $depositId, 'Deposit via ' . $deposit['method_label'] . ' approved');
            db()->prepare("UPDATE deposits SET status = 'approved', admin_id = ?, admin_note = ?, processed_at = NOW() WHERE id = ?")
                ->execute([$viewer['id'], $note ?: null, $depositId]);

            notify((int) $deposit['user_id'], 'Deposit approved', 'Your deposit of ' . money((float) $deposit['amount']) . ' has been approved and credited.', 'success');
            log_activity($viewer['id'], 'Approved deposit', 'deposit', $depositId, money((float) $deposit['amount']) . ' for ' . $user['full_name']);

            // Referral bonus on this investor's FIRST approved deposit.
            if ($user && $user['referred_by']) {
                $priorApproved = db()->prepare("SELECT COUNT(*) FROM deposits WHERE user_id = ? AND status = 'approved' AND id != ?");
                $priorApproved->execute([$user['id'], $depositId]);
                if ((int) $priorApproved->fetchColumn() === 0) {
                    $referralPercent = (float) get_setting('referral_percent', 5);
                    $bonus = round((float) $deposit['amount'] * ($referralPercent / 100), 2);
                    if ($bonus > 0) {
                        record_transaction((int) $user['referred_by'], 'referral_bonus', $bonus, 'user', $user['id'], 'Referral bonus — ' . $user['full_name'] . ' first deposit');
                        notify((int) $user['referred_by'], 'Referral bonus earned', 'You earned ' . money($bonus) . ' because ' . $user['full_name'] . ' made their first deposit.', 'success');
                    }
                }
            }

            flash('success', 'Deposit approved and credited to ' . $user['full_name'] . '.');
        } catch (Throwable $e) {
            flash('danger', 'Could not process this deposit.');
        }
    } elseif ($action === 'reject') {
        db()->prepare("UPDATE deposits SET status = 'rejected', admin_id = ?, admin_note = ?, processed_at = NOW() WHERE id = ?")
            ->execute([$viewer['id'], $note ?: 'Not confirmed', $depositId]);
        notify((int) $deposit['user_id'], 'Deposit rejected', 'Your deposit of ' . money((float) $deposit['amount']) . ' could not be confirmed. Reason: ' . ($note ?: 'Not specified'), 'danger');
        log_activity($viewer['id'], 'Rejected deposit', 'deposit', $depositId, $user['full_name']);
        flash('success', 'Deposit rejected.');
    }
    redirect('admin/deposits.php?status=' . ($_GET['status'] ?? 'pending'));
}

$statusFilter = $_GET['status'] ?? 'pending';
if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) $statusFilter = 'pending';

$where = $statusFilter === 'all' ? '' : "WHERE d.status = " . db()->quote($statusFilter);

if (($_GET['export'] ?? '') === 'csv') {
    $all = db()->query("SELECT d.*, u.full_name, u.email FROM deposits d JOIN users u ON u.id = d.user_id {$where} ORDER BY d.created_at DESC")->fetchAll();
    $csvRows = array_map(fn($d) => [
        $d['created_at'], $d['full_name'], $d['email'], number_format((float) $d['amount'], 2, '.', ''),
        $d['currency_code'], $d['method_label'], $d['txn_reference'], $d['status'], $d['admin_note'], $d['processed_at'],
    ], $all);
    export_csv('deposits-' . date('Y-m-d') . '.csv', ['Date', 'User', 'Email', 'Amount', 'Currency', 'Method', 'Reference', 'Status', 'Admin Note', 'Processed At'], $csvRows);
}

$deposits = db()->query("SELECT d.*, u.full_name, u.email FROM deposits d JOIN users u ON u.id = d.user_id {$where} ORDER BY d.created_at DESC LIMIT 100")->fetchAll();

$counts = db()->query("SELECT status, COUNT(*) c FROM deposits GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Deposits';
$activePage = 'deposits';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="tabs" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
  <div style="display:flex;flex-wrap:wrap;">
  <a href="?status=pending" class="tab-link <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending (<?= $counts['pending'] ?? 0 ?>)</a>
  <a href="?status=approved" class="tab-link <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved (<?= $counts['approved'] ?? 0 ?>)</a>
  <a href="?status=rejected" class="tab-link <?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected (<?= $counts['rejected'] ?? 0 ?>)</a>
  <a href="?status=all" class="tab-link <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
  </div>
  <a href="?<?= http_build_query(['status' => $statusFilter, 'export' => 'csv']) ?>" class="btn btn-outline btn-sm" style="align-self:center;"><i class="fa-solid fa-file-arrow-down"></i> Export CSV</a>
</div>

<?php if (!$deposits): ?>
  <div class="card empty-state"><div class="empty-icon"><i class="fa-solid fa-coins"></i></div><p>No deposits in this view.</p></div>
<?php else: foreach ($deposits as $d): ?>
  <div class="card" style="margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div style="display:flex;gap:14px;">
        <span class="avatar-circle"><?= e(avatar_initials($d['full_name'])) ?></span>
        <div>
          <div class="fw-700"><?= e($d['full_name']) ?> <span class="text-faint fw-400" style="font-size:12.5px;">&middot; <?= e($d['email']) ?></span></div>
          <div style="margin-top:4px;"><span class="mono fw-700" style="font-size:16px;"><?= money((float) $d['amount']) ?></span> <span class="badge badge-neutral"><?= e($d['currency_code']) ?></span></div>
          <div class="text-faint" style="font-size:12.5px;margin-top:4px;">Ref: <?= e($d['txn_reference'] ?: '—') ?> &middot; <?= time_ago($d['created_at']) ?></div>
          <?php if ($d['note']): ?><div class="text-faint" style="font-size:12.5px;">Note: <?= e($d['note']) ?></div><?php endif; ?>
          <?php if ($d['admin_note']): ?><div class="text-faint" style="font-size:12.5px;">Admin note: <?= e($d['admin_note']) ?></div><?php endif; ?>
        </div>
      </div>
      <div style="text-align:right;">
        <span class="badge <?= badge_class($d['status']) ?>" style="margin-bottom:10px;display:inline-block;"><?= e($d['status']) ?></span>
        <?php if ($d['status'] === 'pending'): ?>
          <div style="display:flex;gap:8px;">
            <form method="POST" action="<?= url('admin/deposits.php?status=' . $statusFilter) ?>" data-confirm="Approve this deposit and credit the user's balance?">
              <?= csrf_field() ?><input type="hidden" name="deposit_id" value="<?= $d['id'] ?>"><input type="hidden" name="action" value="approve">
              <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> Approve</button>
            </form>
            <form method="POST" action="<?= url('admin/deposits.php?status=' . $statusFilter) ?>" data-confirm="Reject this deposit?">
              <?= csrf_field() ?><input type="hidden" name="deposit_id" value="<?= $d['id'] ?>"><input type="hidden" name="action" value="reject">
              <input type="text" name="admin_note" placeholder="Reason (optional)" class="form-control" style="display:inline-block;width:160px;margin-right:6px;">
              <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark"></i> Reject</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
