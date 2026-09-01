<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$perPage = 30;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$total = (int) db()->query('SELECT COUNT(*) FROM activity_log')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = db()->query("SELECT al.*, u.full_name FROM activity_log al LEFT JOIN users u ON u.id = al.admin_id ORDER BY al.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$rows = $stmt->fetchAll();

$pageTitle = 'Activity Log';
$activePage = 'activity-log';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="card" style="padding:0;">
  <div class="table-wrap" style="border:none;">
    <table class="table">
      <thead><tr><th>Date</th><th>Admin</th><th>Action</th><th>Details</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="4" class="table-empty">No activity recorded yet.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td class="text-faint" style="white-space:nowrap;"><?= format_date($r['created_at'], 'M j, Y g:i A') ?></td>
          <td><?= e($r['full_name'] ?? 'System') ?></td>
          <td><span class="badge badge-neutral"><?= e($r['action']) ?></span></td>
          <td class="text-faint"><?= e($r['details'] ?: '—') ?></td>
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
      <?php else: ?><a href="?page=<?= $p ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
