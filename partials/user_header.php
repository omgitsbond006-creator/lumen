<?php
/**
 * Shell for the logged-in user dashboard area.
 * Expects: $pageTitle, $activePage (set before include)
 */
require_login();
$viewer = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? '';
$unread = unread_notification_count($viewer['id']);

$navItems = [
    ['key' => 'dashboard', 'href' => 'user/dashboard.php', 'icon' => 'fa-gauge-high', 'label' => 'Dashboard'],
    ['key' => 'deposit', 'href' => 'user/deposit.php', 'icon' => 'fa-wallet', 'label' => 'Deposit Funds'],
    ['key' => 'invest', 'href' => 'user/invest.php', 'icon' => 'fa-seedling', 'label' => 'Invest'],
    ['key' => 'investments', 'href' => 'user/my-investments.php', 'icon' => 'fa-chart-pie', 'label' => 'My Investments'],
    ['key' => 'withdraw', 'href' => 'user/withdraw.php', 'icon' => 'fa-money-bill-transfer', 'label' => 'Withdraw'],
    ['key' => 'transactions', 'href' => 'user/transactions.php', 'icon' => 'fa-receipt', 'label' => 'Transactions'],
    ['key' => 'referrals', 'href' => 'user/referrals.php', 'icon' => 'fa-user-plus', 'label' => 'Referrals'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — <?= e(get_setting('site_name', APP_NAME)) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="stylesheet" href="<?= asset('vendor/fonts/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
</head>
<body>
<div class="app-shell">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <a href="<?= url('index.php') ?>" class="brand">
        <span class="brand-mark"><i class="fa-solid fa-chart-line"></i></span>
        <?= e(get_setting('site_name', APP_NAME)) ?>
      </a>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section-label">Main</div>
      <?php foreach ($navItems as $item): ?>
        <a href="<?= url($item['href']) ?>" class="sidebar-link <?= $activePage === $item['key'] ? 'active' : '' ?>">
          <i class="fa-solid <?= $item['icon'] ?> ico"></i> <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>

      <div class="sidebar-section-label">Account</div>
      <a href="<?= url('user/notifications.php') ?>" class="sidebar-link <?= $activePage === 'notifications' ? 'active' : '' ?>">
        <i class="fa-solid fa-bell ico"></i> Notifications
        <?php if ($unread > 0): ?><span class="count"><?= $unread ?></span><?php endif; ?>
      </a>
      <a href="<?= url('user/support.php') ?>" class="sidebar-link <?= $activePage === 'support' ? 'active' : '' ?>">
        <i class="fa-solid fa-headset ico"></i> Support
      </a>
      <a href="<?= url('user/profile.php') ?>" class="sidebar-link <?= $activePage === 'profile' ? 'active' : '' ?>">
        <i class="fa-solid fa-user-gear ico"></i> Profile Settings
      </a>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= url('logout.php') ?>" class="sidebar-link" style="margin:0;">
        <i class="fa-solid fa-arrow-right-from-bracket ico"></i> Sign Out
      </a>
    </div>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="main-content">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:14px;">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
        <span class="topbar-title"><?= e($pageTitle) ?></span>
      </div>
      <div class="topbar-actions">
        <a href="<?= url('user/notifications.php') ?>" class="bell-btn">
          <i class="fa-regular fa-bell"></i>
          <?php if ($unread > 0): ?><span class="bell-dot"></span><?php endif; ?>
        </a>
        <a href="<?= url('user/profile.php') ?>" class="user-chip">
          <span class="avatar-circle sm"><?= e(avatar_initials($viewer['full_name'])) ?></span>
          <span style="font-size:13.5px;font-weight:600;"><?= e(explode(' ', $viewer['full_name'])[0]) ?></span>
        </a>
      </div>
    </div>
    <div class="page-body">
      <?php if (!$viewer['email_verified_at']): ?>
        <div class="alert alert-warning">
          <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;"></i>
          <span>Please verify your email address (<?= e($viewer['email']) ?>) to secure your account. <a href="<?= url('user/resend-verification.php') ?>" style="color:inherit;text-decoration:underline;">Resend verification link</a>.</span>
        </div>
      <?php endif; ?>
      <?php require __DIR__ . '/flash.php'; ?>
