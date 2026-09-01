<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $invId = (int) ($_POST['investment_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = db()->prepare('SELECT * FROM investments WHERE id = ?');
    $stmt->execute([$invId]);
    $inv = $stmt->fetch();

    if ($inv && $inv['status'] === 'active' && $action === 'cancel_refund') {
        try {
            record_transaction((int) $inv['user_id'], 'admin_credit', (float) $inv['amount'], 'investment', $invId, $inv['plan_name'] . ' cancelled by admin — principal refunded');
            db()->prepare("UPDATE investments SET status = 'cancelled', next_payout_at = NULL WHERE id = ?")->execute([$invId]);
            notify((int) $inv['user_id'], 'Investment cancelled', 'Your ' . $inv['plan_name'] . ' investment was cancelled by an administrator. Your principal of ' . money((float) $inv['amount']) . ' has been refunded.', 'warning');
            log_activity($viewer['id'], 'Cancelled investment', 'investment', $invId, $inv['plan_name']);
            flash('success', 'Investment cancelled and principal refunded.');
        } catch (Throwable $e) {
            flash('danger', 'Could not cancel this investment.');
        }
    }
    redirect('admin/investments.php' . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
}

$statusFilter = $_GET['status'] ?? 'all';
$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = 'WHERE 1=1';
$params = [];
if (in_array($statusFilter, ['active', 'completed', 'cancelled'], true)) {
    $where .= ' AND i.status = ?';
    $params[] = $statusFilter;
}

$total = db()->prepare("SELECT COUNT(*) FROM investments i {$where}");
$total->execute($params);
$total = (int) $total->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = db()->prepare("SELECT i.*, u.full_name, u.email FROM investments i JOIN users u ON u.id = i.user_id {$where} ORDER BY i.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$investments = $stmt->fetchAll();

$pageTitle = 'Investments';
$activePage = 'investments';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="tabs">
  <a href="?status=all" class="tab-link <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
  <a href="?status=active" class="tab-link <?= $statusFilter === 'active' ? 'active' : '' ?>">Active</a>
  <a href="?status=completed" class="tab-link <?= $statusFilter === 'completed' ? 'active' : '' ?>">Completed</a>
  <a href="?status=cancelled" class="tab-link <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
</div>

<div class="card" style="padding:0;">
  <div class="table-wrap" style="border:none;">
    <table class="table">
      <thead><tr><th>Investor</th><th>Plan</th><th>Amount</th><th>Paid Out</th><th>Started</th><th>Matures</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$investments): ?>
        <tr><td colspan="8" class="table-empty">No investments in this view.</td></tr>
      <?php else: foreach ($investments as $inv): ?>
        <tr>
          <td><a href="<?= url('admin/user-view.php?id=' . $inv['user_id']) ?>" style="color:var(--text);"><?= e($inv['full_name']) ?></a></td>
          <td><?= e($inv['plan_name']) ?></td>
          <td class="mono"><?= money((float) $inv['amount']) ?></td>
          <td class="mono text-emerald"><?= money((float) $inv['paid_out']) ?></td>
          <td class="text-faint"><?= format_date($inv['start_date']) ?></td>
          <td class="text-faint"><?= format_date($inv['maturity_date']) ?></td>
          <td><span class="badge <?= badge_class($inv['status']) ?>"><?= e($inv['status']) ?></span></td>
          <td>
            <?php if ($inv['status'] === 'active'): ?>
              <form method="POST" action="<?= url('admin/investments.php?status=' . $statusFilter) ?>" data-confirm="Cancel this investment and refund the principal to the user?">
                <?= csrf_field() ?><input type="hidden" name="investment_id" value="<?= $inv['id'] ?>"><input type="hidden" name="action" value="cancel_refund">
                <button type="submit" class="btn btn-danger btn-sm">Cancel &amp; Refund</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <?php if ($p === $page): ?><span class="current"><?= $p ?></span>
      <?php else: ?><a href="?status=<?= $statusFilter ?>&page=<?= $p ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
