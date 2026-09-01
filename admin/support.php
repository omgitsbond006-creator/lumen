<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'reply') {
        $reply = trim($_POST['reply'] ?? '');
        if ($reply !== '') {
            db()->prepare("UPDATE support_messages SET admin_reply = ?, status = 'answered', replied_at = NOW() WHERE id = ?")
                ->execute([$reply, $ticketId]);
            $ticket = db()->prepare('SELECT * FROM support_messages WHERE id = ?');
            $ticket->execute([$ticketId]);
            $ticket = $ticket->fetch();
            if ($ticket && $ticket['user_id']) {
                notify((int) $ticket['user_id'], 'Support replied to your ticket', 'Re: ' . $ticket['subject'], 'info');
            }
            log_activity($viewer['id'], 'Replied to support message', 'support_message', $ticketId, $ticket['name'] ?? '');
            flash('success', 'Reply sent.');
        }
    } elseif ($action === 'close') {
        db()->prepare("UPDATE support_messages SET status = 'closed' WHERE id = ?")->execute([$ticketId]);
        flash('success', 'Ticket closed.');
    }
    redirect('admin/support.php' . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
}

$statusFilter = $_GET['status'] ?? 'open';
if (!in_array($statusFilter, ['open', 'answered', 'closed', 'all'], true)) $statusFilter = 'open';
$where = $statusFilter === 'all' ? '' : 'WHERE status = ' . db()->quote($statusFilter);
$tickets = db()->query("SELECT * FROM support_messages {$where} ORDER BY created_at DESC LIMIT 100")->fetchAll();
$counts = db()->query('SELECT status, COUNT(*) c FROM support_messages GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Support Inbox';
$activePage = 'support';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="tabs">
  <a href="?status=open" class="tab-link <?= $statusFilter === 'open' ? 'active' : '' ?>">Open (<?= $counts['open'] ?? 0 ?>)</a>
  <a href="?status=answered" class="tab-link <?= $statusFilter === 'answered' ? 'active' : '' ?>">Answered (<?= $counts['answered'] ?? 0 ?>)</a>
  <a href="?status=closed" class="tab-link <?= $statusFilter === 'closed' ? 'active' : '' ?>">Closed (<?= $counts['closed'] ?? 0 ?>)</a>
  <a href="?status=all" class="tab-link <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
</div>

<?php if (!$tickets): ?>
  <div class="card empty-state"><div class="empty-icon"><i class="fa-solid fa-headset"></i></div><p>No tickets in this view.</p></div>
<?php else: foreach ($tickets as $t): ?>
  <div class="card" style="margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
      <div>
        <b><?= e($t['subject']) ?></b>
        <div class="text-faint" style="font-size:12.5px;"><?= e($t['name']) ?> &middot; <?= e($t['email']) ?> &middot; <?= time_ago($t['created_at']) ?><?= $t['user_id'] ? ' &middot; <a href="' . url('admin/user-view.php?id=' . $t['user_id']) . '">view account</a>' : '' ?></div>
      </div>
      <span class="badge <?= badge_class($t['status']) ?>"><?= e($t['status']) ?></span>
    </div>
    <p style="margin-bottom:12px;"><?= nl2br(e($t['message'])) ?></p>

    <?php if ($t['admin_reply']): ?>
      <div class="card-sm" style="background:var(--bg-elevated);margin-bottom:12px;">
        <div class="text-faint" style="font-size:11px;margin-bottom:4px;"><i class="fa-solid fa-reply"></i> Replied <?= time_ago($t['replied_at']) ?></div>
        <p style="margin:0;font-size:13.5px;"><?= nl2br(e($t['admin_reply'])) ?></p>
      </div>
    <?php endif; ?>

    <?php if ($t['status'] !== 'closed'): ?>
      <form method="POST" action="<?= url('admin/support.php?status=' . $statusFilter) ?>" style="display:flex;gap:8px;align-items:flex-start;">
        <?= csrf_field() ?><input type="hidden" name="ticket_id" value="<?= $t['id'] ?>"><input type="hidden" name="action" value="reply">
        <textarea name="reply" class="form-control" rows="2" placeholder="Type a reply..." required></textarea>
        <button type="submit" class="btn btn-primary btn-sm">Send</button>
      </form>
      <form method="POST" action="<?= url('admin/support.php?status=' . $statusFilter) ?>" style="margin-top:8px;">
        <?= csrf_field() ?><input type="hidden" name="ticket_id" value="<?= $t['id'] ?>"><input type="hidden" name="action" value="close">
        <button type="submit" class="btn btn-ghost btn-sm">Close Ticket</button>
      </form>
    <?php endif; ?>
  </div>
<?php endforeach; endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
