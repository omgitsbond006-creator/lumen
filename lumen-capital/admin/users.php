<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $target = find_user_by_id($userId);

    if ($target && $target['id'] !== $viewer['id']) {
        if ($action === 'toggle_status') {
            $newStatus = $target['status'] === 'active' ? 'suspended' : 'active';
            db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $userId]);
            log_activity($viewer['id'], ucfirst($newStatus) . ' user', 'user', $userId, $target['full_name']);
            flash('success', $target['full_name'] . ' is now ' . $newStatus . '.');
        } elseif ($action === 'toggle_role') {
            $newRole = $target['role'] === 'admin' ? 'user' : 'admin';
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $userId]);
            log_activity($viewer['id'], 'Changed role to ' . $newRole, 'user', $userId, $target['full_name']);
            flash('success', $target['full_name'] . ' is now a' . ($newRole === 'admin' ? 'n admin' : ' regular user') . '.');
        }
    }
    redirect('admin/users.php?' . http_build_query(['q' => $_GET['q'] ?? '', 'status' => $_GET['status'] ?? 'all']));
}

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = 'WHERE 1=1';
$params = [];
if ($q !== '') {
    $where .= ' AND (full_name LIKE ? OR email LIKE ? OR referral_code LIKE ?)';
    $params = array_merge($params, ["%{$q}%", "%{$q}%", "%{$q}%"]);
}
if (in_array($statusFilter, ['active', 'suspended'], true)) {
    $where .= ' AND status = ?';
    $params[] = $statusFilter;
}

if (($_GET['export'] ?? '') === 'csv') {
    $stmt = db()->prepare("SELECT * FROM users {$where} ORDER BY created_at DESC");
    $stmt->execute($params);
    $csvRows = array_map(fn($u) => [
        $u['id'], $u['full_name'], $u['email'], $u['phone'], $u['country'], $u['role'], $u['status'],
        number_format((float) $u['balance'], 2, '.', ''), number_format((float) $u['total_deposited'], 2, '.', ''),
        number_format((float) $u['total_earned'], 2, '.', ''), number_format((float) $u['total_withdrawn'], 2, '.', ''),
        $u['referral_code'], $u['created_at'],
    ], $stmt->fetchAll());
    export_csv('users-' . date('Y-m-d') . '.csv',
        ['ID', 'Name', 'Email', 'Phone', 'Country', 'Role', 'Status', 'Balance', 'Total Deposited', 'Total Earned', 'Total Withdrawn', 'Referral Code', 'Joined'],
        $csvRows);
}

$total = db()->prepare("SELECT COUNT(*) FROM users {$where}");
$total->execute($params);
$total = (int) $total->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = db()->prepare("SELECT * FROM users {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Users';
$activePage = 'users';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="card" style="margin-bottom:20px;">
  <form method="GET" action="<?= url('admin/users.php') ?>" style="display:flex;gap:12px;flex-wrap:wrap;">
    <input type="text" name="q" class="form-control" style="max-width:280px;" placeholder="Search name, email, or referral code" value="<?= e($q) ?>">
    <select name="status" class="form-control" style="max-width:180px;">
      <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All statuses</option>
      <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <?php if ($q || $statusFilter !== 'all'): ?><a href="<?= url('admin/users.php') ?>" class="btn btn-ghost">Clear</a><?php endif; ?>
    <a href="?<?= http_build_query(['q' => $q, 'status' => $statusFilter, 'export' => 'csv']) ?>" class="btn btn-outline" style="margin-left:auto;"><i class="fa-solid fa-file-arrow-down"></i> Export CSV</a>
  </form>
</div>

<div class="card" style="padding:0;">
  <div class="table-wrap" style="border:none;">
    <table class="table">
      <thead><tr><th>User</th><th>Balance</th><th>Deposited</th><th>Earned</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead>
      <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="8" class="table-empty">No users match your search.</td></tr>
      <?php else: foreach ($users as $u): ?>
        <tr>
          <td>
            <a href="<?= url('admin/user-view.php?id=' . $u['id']) ?>" style="display:flex;align-items:center;gap:10px;color:var(--text);">
              <span class="avatar-circle sm"><?= e(avatar_initials($u['full_name'])) ?></span>
              <div><div class="fw-600"><?= e($u['full_name']) ?></div><div class="text-faint" style="font-size:12px;"><?= e($u['email']) ?></div></div>
            </a>
          </td>
          <td class="mono fw-600"><?= money((float) $u['balance']) ?></td>
          <td class="mono"><?= money((float) $u['total_deposited']) ?></td>
          <td class="mono text-emerald"><?= money((float) $u['total_earned']) ?></td>
          <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-neutral' ?>"><?= e($u['role']) ?></span></td>
          <td><span class="badge <?= badge_class($u['status']) ?>"><?= e($u['status']) ?></span></td>
          <td class="text-faint"><?= format_date($u['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= url('admin/user-view.php?id=' . $u['id']) ?>" class="btn btn-ghost btn-sm" title="View details"><i class="fa-solid fa-eye"></i></a>
              <?php if ($u['id'] !== $viewer['id']): ?>
                <form method="POST" action="<?= url('admin/users.php') ?>?<?= http_build_query(['q' => $q, 'status' => $statusFilter]) ?>" data-confirm="Change this user's status?">
                  <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="toggle_status">
                  <button type="submit" class="btn btn-ghost btn-sm" title="<?= $u['status'] === 'active' ? 'Suspend' : 'Reactivate' ?>">
                    <i class="fa-solid fa-<?= $u['status'] === 'active' ? 'user-slash' : 'user-check' ?>"></i>
                  </button>
                </form>
              <?php endif; ?>
            </div>
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
      <?php else: ?><a href="?q=<?= urlencode($q) ?>&status=<?= $statusFilter ?>&page=<?= $p ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
