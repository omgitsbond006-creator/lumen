<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$viewer = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || mb_strlen($subject) > 160) $errors[] = 'Please enter a subject.';
    if ($message === '' || mb_strlen($message) < 10) $errors[] = 'Your message should be at least 10 characters.';

    if (!$errors) {
        db()->prepare('INSERT INTO support_messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)')
            ->execute([$viewer['id'], $viewer['full_name'], $viewer['email'], $subject, $message]);
        flash('success', 'Your support ticket has been submitted. We\'ll reply here soon.');
        redirect('user/support.php');
    }
}

$tickets = db()->prepare('SELECT * FROM support_messages WHERE user_id = ? ORDER BY created_at DESC');
$tickets->execute([$viewer['id']]);
$tickets = $tickets->fetchAll();

$pageTitle = 'Support';
$activePage = 'support';
require __DIR__ . '/../partials/user_header.php';
?>

<div class="dash-grid">
  <div>
    <div class="card">
      <h3 class="card-title" style="margin-bottom:16px;">Open a ticket</h3>
      <?php if ($errors): ?><div class="alert alert-danger"><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>
      <form method="POST" action="<?= url('user/support.php') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Message</label>
          <textarea name="message" class="form-control" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Submit Ticket</button>
      </form>
    </div>
  </div>
  <div>
    <div class="card">
      <h3 class="card-title" style="margin-bottom:14px;">Your Tickets</h3>
      <?php if (!$tickets): ?>
        <div class="empty-state"><p>No support tickets yet.</p></div>
      <?php else: foreach ($tickets as $t): ?>
        <div class="card-sm" style="background:var(--bg-elevated);margin-bottom:12px;">
          <div style="display:flex;justify-content:space-between;gap:10px;margin-bottom:6px;">
            <b style="font-size:13.5px;"><?= e($t['subject']) ?></b>
            <span class="badge <?= badge_class($t['status']) ?>"><?= e($t['status']) ?></span>
          </div>
          <p style="font-size:13px;margin-bottom:8px;"><?= e($t['message']) ?></p>
          <div class="text-faint" style="font-size:11.5px;"><?= time_ago($t['created_at']) ?></div>
          <?php if ($t['admin_reply']): ?>
            <div style="margin-top:10px;padding-top:10px;border-top:1px dashed var(--border);">
              <div class="text-faint" style="font-size:11px;margin-bottom:4px;"><i class="fa-solid fa-reply"></i> Support team replied</div>
              <p style="font-size:13px;"><?= e($t['admin_reply']) ?></p>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/user_footer.php'; ?>
