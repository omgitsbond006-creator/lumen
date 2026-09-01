<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$typeFilter = $_GET['type'] ?? 'all';
$validTypes = ['all', 'deposit', 'withdrawal', 'investment', 'roi_payout', 'maturity_payout', 'referral_bonus', 'admin_credit', 'admin_debit'];
if (!in_array($typeFilter, $validTypes, true)) $typeFilter = 'all';
$q = trim($_GET['q'] ?? '');

$perPage = 25;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = 'WHERE 1=1';
$params = [];
if ($typeFilter !== 'all') { $where .= ' AND t.type = ?'; $params[] = $typeFilter; }
if ($q !== '') { $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)'; $params[] = "%{$q}%"; $params[] = "%{$q}%"; }

$typeLabels = [
    'deposit' => 'Deposit', 'withdrawal' => 'Withdrawal', 'investment' => 'Investment',
    'roi_payout' => 'ROI Payout', 'maturity_payout' => 'Maturity Payout', 'referral_bonus' => 'Referral Bonus',
    'admin_credit' => 'Admin Credit', 'admin_debit' => 'Admin Debit',
];

if (($_GET['export'] ?? '') === 'csv') {
    $stmt = db()->prepare("SELECT t.*, u.full_name, u.email FROM transactions t JOIN users u ON u.id = t.user_id {$where} ORDER BY t.created_at DESC");
    $stmt->execute($params);
    $csvRows = array_map(fn($tx) => [
        $tx['created_at'], $tx['full_name'], $tx['email'], $typeLabels[$tx['type']] ?? $tx['type'],
        $tx['description'], number_format((float) $tx['amount'], 2, '.', ''), number_format((float) $tx['balance_after'], 2, '.', ''), $tx['status'],
    ], $stmt->fetchAll());
    export_csv('transactions-' . date('Y-m-d') . '.csv', ['Date', 'User', 'Email', 'Type', 'Description', 'Amount', 'Balance After', 'Status'], $csvRows);
}

$total = db()->prepare("SELECT COUNT(*) FROM transactions t JOIN users u ON u.id = t.user_id {$where}");
$total->execute($params);
$total = (int) $total->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = db()->prepare("SELECT t.*, u.full_name, u.email FROM transactions t JOIN users u ON u.id = t.user_id {$where} ORDER BY t.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Transaction Ledger';
$activePage = 'transactions';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="card" style="margin-bottom:20px;">
  <form method="GET" action="<?= url('admin/transactions.php') ?>" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" class="form-control" style="max-width:260px;" placeholder="Search by user name or email" value="<?= e($q) ?>">
    <select name="type" class="form-control" style="max-width:200px;">
      <option value="all">All types</option>
      <?php foreach ($typeLabels as $key => $label): ?>
        <option value="<?= $key ?>" <?= $typeFilter === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <?php if ($q || $typeFilter !== 'all'): ?><a href="<?= url('admin/transactions.php') ?>" class="btn btn-ghost">Clear</a><?php endif; ?>
    <a href="?<?= http_build_query(['q' => $q, 'type' => $typeFilter, 'export' => 'csv']) ?>" class="btn btn-outline"><i class="fa-solid fa-file-arrow-down"></i> Export CSV</a>
  </form>
  <div class="text-faint" style="font-size:13px;margin-top:10px;"><?= number_format($total) ?> total records</div>
</div>

<div class="card" style="padding:0;">
  <div class="table-wrap" style="border:none;">
    <table class="table">
      <thead><tr><th>Date</th><th>User</th><th>Type</th><th>Description</th><th>Amount</th><th>Balance After</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="table-empty">No transactions match this filter.</td></tr>
      <?php else: foreach ($rows as $tx): ?>
        <tr>
          <td class="text-faint" style="white-space:nowrap;"><?= format_date($tx['created_at'], 'M j, Y g:i A') ?></td>
          <td><a href="<?= url('admin/user-view.php?id=' . $tx['user_id']) ?>" style="color:var(--text);"><?= e($tx['full_name']) ?></a></td>
          <td><span class="badge badge-neutral"><?= e($typeLabels[$tx['type']] ?? $tx['type']) ?></span></td>
          <td><?= e($tx['description'] ?: '—') ?></td>
          <td class="mono fw-600 <?= $tx['amount'] >= 0 ? 'text-emerald' : 'text-red' ?>"><?= money((float) $tx['amount'], true) ?></td>
          <td class="mono"><?= money((float) $tx['balance_after']) ?></td>
          <td><span class="badge <?= badge_class($tx['status']) ?>"><?= e($tx['status']) ?></span></td>
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
      <?php else: ?><a href="?q=<?= urlencode($q) ?>&type=<?= $typeFilter ?>&page=<?= $p ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
