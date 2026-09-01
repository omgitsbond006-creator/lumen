<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$viewer['id']]);
    flash('success', 'All notifications marked as read.');
    redirect('user/notifications.php');
}

$stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$viewer['id']]);
$notifications = $stmt->fetchAll();

$iconMap = ['success' => 'fa-circle-check', 'info' => 'fa-circle-info', 'warning' => 'fa-triangle-exclamation', 'danger' => 'fa-circle-xmark'];
$colorMap = ['success' => 'emerald', 'info' => 'violet', 'warning' => 'gold', 'danger' => 'rose'];

$pageTitle = 'Notifications';
$activePage = 'notifications';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">All Notifications</h3>
    <?php if ($notifications): ?>
      <form method="POST" action="<?= url('user/notifications.php') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-ghost btn-sm">Mark all as read</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$notifications): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fa-solid fa-bell"></i></div>
      <p>You're all caught up.</p>
    </div>
  <?php else: foreach ($notifications as $n): ?>
    <div class="timeline-item">
      <div class="timeline-dot <?= $n['type'] === 'danger' ? 'warning' : $n['type'] ?>"></div>
      <div style="flex:1;">
        <div style="display:flex;justify-content:space-between;gap:10px;">
          <span style="font-weight:700;font-size:14px;"><?= e($n['title']) ?></span>
          <?php if (!$n['is_read']): ?><span class="badge badge-primary">New</span><?php endif; ?>
        </div>
        <p style="margin:4px 0;"><?= e($n['message']) ?></p>
        <div class="text-faint" style="font-size:12px;"><?= time_ago($n['created_at']) ?></div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/../partials/user_footer.php'; ?>
