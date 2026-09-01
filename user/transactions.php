<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();

$typeFilter = $_GET['type'] ?? 'all';
$validTypes = ['all', 'deposit', 'withdrawal', 'investment', 'roi_payout', 'maturity_payout', 'referral_bonus', 'admin_credit', 'admin_debit'];
if (!in_array($typeFilter, $validTypes, true)) $typeFilter = 'all';

$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = 'WHERE user_id = ?';
$params = [$viewer['id']];
if ($typeFilter !== 'all') { $where .= ' AND type = ?'; $params[] = $typeFilter; }

$typeLabels = [
    'deposit' => 'Deposit', 'withdrawal' => 'Withdrawal', 'investment' => 'Investment',
    'roi_payout' => 'ROI Payout', 'maturity_payout' => 'Maturity Payout', 'referral_bonus' => 'Referral Bonus',
    'admin_credit' => 'Admin Credit', 'admin_debit' => 'Admin Debit',
];

if (($_GET['export'] ?? '') === 'csv') {
    $stmt = db()->prepare("SELECT * FROM transactions {$where} ORDER BY created_at DESC");
    $stmt->execute($params);
    $csvRows = array_map(fn($tx) => [
        $tx['created_at'], $typeLabels[$tx['type']] ?? $tx['type'], $tx['description'],
        number_format((float) $tx['amount'], 2, '.', ''), number_format((float) $tx['balance_after'], 2, '.', ''), $tx['status'],
    ], $stmt->fetchAll());
    export_csv('my-transactions-' . date('Y-m-d') . '.csv', ['Date', 'Type', 'Description', 'Amount', 'Balance After', 'Status'], $csvRows);
}

$total = db()->prepare("SELECT COUNT(*) FROM transactions {$where}");
$total->execute($params);
$total = (int) $total->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = db()->prepare("SELECT * FROM transactions {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Transactions';
$activePage = 'transactions';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="card" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <div class="tabs" style="margin-bottom:0;border-bottom:none;">
      <a href="?type=all" class="tab-link <?= $typeFilter === 'all' ? 'active' : '' ?>">All</a>
      <?php foreach ($typeLabels as $key => $label): ?>
        <a href="?type=<?= $key ?>" class="tab-link <?= $typeFilter === $key ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
    <a href="?<?= http_build_query(['type' => $typeFilter, 'export' => 'csv']) ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-file-arrow-down"></i> Export CSV</a>
  </div>
</div>

<div class="card" style="padding:0;">
  <div class="table-wrap" style="border:none;">
    <table class="table">
      <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>Balance After</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="table-empty">No transactions match this filter.</td></tr>
      <?php else: foreach ($rows as $tx): ?>
        <tr>
          <td class="text-faint"><?= format_date($tx['created_at'], 'M j, Y g:i A') ?></td>
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
      <?php if ($p === $page): ?>
        <span class="current"><?= $p ?></span>
      <?php else: ?>
        <a href="?type=<?= $typeFilter ?>&page=<?= $p ?>"><?= $p ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/user_footer.php'; ?>
