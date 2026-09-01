<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $withdrawalId = (int) ($_POST['withdrawal_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note = trim($_POST['admin_note'] ?? '');

    $stmt = db()->prepare('SELECT * FROM withdrawals WHERE id = ?');
    $stmt->execute([$withdrawalId]);
    $withdrawal = $stmt->fetch();

    if (!$withdrawal || $withdrawal['status'] !== 'pending') {
        flash('danger', 'This withdrawal has already been processed.');
        redirect('admin/withdrawals.php');
    }
    $user = find_user_by_id((int) $withdrawal['user_id']);

    if ($action === 'approve') {
        // Funds were already reserved (deducted) when the request was submitted.
        db()->prepare("UPDATE withdrawals SET status = 'approved', admin_id = ?, admin_note = ?, processed_at = NOW() WHERE id = ?")
            ->execute([$viewer['id'], $note ?: null, $withdrawalId]);
        db()->prepare("UPDATE transactions SET status = 'completed' WHERE user_id = ? AND type = 'withdrawal' AND reference_id = ?")
            ->execute([$withdrawal['user_id'], $withdrawalId]);

        notify((int) $withdrawal['user_id'], 'Withdrawal approved', 'Your withdrawal of ' . money((float) $withdrawal['amount']) . ' has been approved and sent.', 'success');
        log_activity($viewer['id'], 'Approved withdrawal', 'withdrawal', $withdrawalId, money((float) $withdrawal['amount']) . ' for ' . $user['full_name']);
        flash('success', 'Withdrawal approved.');
    } elseif ($action === 'reject') {
        try {
            // Refund the reserved amount back to the user's available balance.
            record_transaction((int) $withdrawal['user_id'], 'admin_credit', (float) $withdrawal['amount'], 'withdrawal', $withdrawalId, 'Withdrawal request rejected — funds refunded');
            db()->prepare("UPDATE withdrawals SET status = 'rejected', admin_id = ?, admin_note = ?, processed_at = NOW() WHERE id = ?")
                ->execute([$viewer['id'], $note ?: 'Not approved', $withdrawalId]);
            db()->prepare("UPDATE transactions SET status = 'reversed' WHERE user_id = ? AND type = 'withdrawal' AND reference_id = ?")
                ->execute([$withdrawal['user_id'], $withdrawalId]);

            notify((int) $withdrawal['user_id'], 'Withdrawal rejected', 'Your withdrawal of ' . money((float) $withdrawal['amount']) . ' was rejected and the amount has been refunded to your balance. Reason: ' . ($note ?: 'Not specified'), 'danger');
            log_activity($viewer['id'], 'Rejected withdrawal', 'withdrawal', $withdrawalId, $user['full_name']);
            flash('success', 'Withdrawal rejected and funds refunded.');
        } catch (Throwable $e) {
            flash('danger', 'Could not process this rejection.');
        }
    }
    redirect('admin/withdrawals.php?status=' . ($_GET['status'] ?? 'pending'));
}

$statusFilter = $_GET['status'] ?? 'pending';
if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) $statusFilter = 'pending';

$where = $statusFilter === 'all' ? '' : "WHERE w.status = " . db()->quote($statusFilter);

if (($_GET['export'] ?? '') === 'csv') {
    $all = db()->query("SELECT w.*, u.full_name, u.email FROM withdrawals w JOIN users u ON u.id = w.user_id {$where} ORDER BY w.created_at DESC")->fetchAll();
    $csvRows = array_map(fn($w) => [
        $w['created_at'], $w['full_name'], $w['email'], number_format((float) $w['amount'], 2, '.', ''),
        number_format((float) $w['fee'], 2, '.', ''), number_format((float) $w['net_amount'], 2, '.', ''),
        $w['method_label'], $w['destination'], $w['status'], $w['admin_note'], $w['processed_at'],
    ], $all);
    export_csv('withdrawals-' . date('Y-m-d') . '.csv', ['Date', 'User', 'Email', 'Amount', 'Fee', 'Net Amount', 'Method', 'Destination', 'Status', 'Admin Note', 'Processed At'], $csvRows);
}

$withdrawals = db()->query("SELECT w.*, u.full_name, u.email FROM withdrawals w JOIN users u ON u.id = w.user_id {$where} ORDER BY w.created_at DESC LIMIT 100")->fetchAll();
$counts = db()->query("SELECT status, COUNT(*) c FROM withdrawals GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Withdrawals';
$activePage = 'withdrawals';
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

<?php if (!$withdrawals): ?>
  <div class="card empty-state"><div class="empty-icon"><i class="fa-solid fa-arrow-up"></i></div><p>No withdrawals in this view.</p></div>
<?php else: foreach ($withdrawals as $w): ?>
  <div class="card" style="margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div style="display:flex;gap:14px;">
        <span class="avatar-circle"><?= e(avatar_initials($w['full_name'])) ?></span>
        <div>
          <div class="fw-700"><?= e($w['full_name']) ?> <span class="text-faint fw-400" style="font-size:12.5px;">&middot; <?= e($w['email']) ?></span></div>
          <div style="margin-top:4px;"><span class="mono fw-700" style="font-size:16px;"><?= money((float) $w['amount']) ?></span> <span class="badge badge-neutral"><?= e($w['method_label']) ?></span> <span class="text-faint">net <?= money((float) $w['net_amount']) ?></span></div>
          <div class="text-faint" style="font-size:12.5px;margin-top:4px;">To: <?= e($w['destination']) ?></div>
          <div class="text-faint" style="font-size:12.5px;"><?= time_ago($w['created_at']) ?></div>
          <?php if ($w['admin_note']): ?><div class="text-faint" style="font-size:12.5px;">Admin note: <?= e($w['admin_note']) ?></div><?php endif; ?>
        </div>
      </div>
      <div style="text-align:right;">
        <span class="badge <?= badge_class($w['status']) ?>" style="margin-bottom:10px;display:inline-block;"><?= e($w['status']) ?></span>
        <?php if ($w['status'] === 'pending'): ?>
          <div style="display:flex;gap:8px;">
            <form method="POST" action="<?= url('admin/withdrawals.php?status=' . $statusFilter) ?>" data-confirm="Approve and send this withdrawal?">
              <?= csrf_field() ?><input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>"><input type="hidden" name="action" value="approve">
              <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> Approve</button>
            </form>
            <form method="POST" action="<?= url('admin/withdrawals.php?status=' . $statusFilter) ?>" data-confirm="Reject and refund this withdrawal?">
              <?= csrf_field() ?><input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>"><input type="hidden" name="action" value="reject">
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
