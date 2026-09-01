<?php
require_once __DIR__ . '/includes/init.php';

$token = trim($_GET['token'] ?? '');

$stmt = db()->prepare('SELECT * FROM email_verifications WHERE token = ? AND used = 0 AND expires_at > NOW()');
$stmt->execute([$token]);
$verification = $stmt->fetch();

if (!$verification) {
    flash('danger', 'That verification link is invalid or has expired. You can request a new one from your dashboard.');
    redirect(is_logged_in() ? 'user/dashboard.php' : 'login.php');
}

db()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?')->execute([$verification['user_id']]);
db()->prepare('UPDATE email_verifications SET used = 1 WHERE id = ?')->execute([$verification['id']]);

flash('success', 'Your email address has been verified. Thanks!');
redirect(is_logged_in() ? 'user/dashboard.php' : 'login.php');
