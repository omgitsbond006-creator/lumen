<?php
/**
 * Shell for the admin dashboard area.
 * Expects: $pageTitle, $activePage (set before include)
 */
require_admin();
$viewer = current_user();
$pageTitle = $pageTitle ?? 'Admin';
$activePage = $activePage ?? '';

// Namespaced with a nav_ prefix so these never collide with same-named
// variables a page sets before including this header (e.g. admin/index.php
// builds its own $pendingDeposits/$pendingWithdrawals arrays for its stat
// cards — `require` shares scope, so distinct names are required here).
$navPendingDeposits = (int) db()->query("SELECT COUNT(*) FROM deposits WHERE status = 'pending'")->fetchColumn();
$navPendingWithdrawals = (int) db()->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$navOpenTickets = (int) db()->query("SELECT COUNT(*) FROM support_messages WHERE status = 'open'")->fetchColumn();

$navItems = [
    ['key' => 'dashboard', 'href' => 'admin/index.php', 'icon' => 'fa-gauge-high', 'label' => 'Overview'],
    ['key' => 'users', 'href' => 'admin/users.php', 'icon' => 'fa-users', 'label' => 'Users'],
    ['key' => 'plans', 'href' => 'admin/plans.php', 'icon' => 'fa-layer-group', 'label' => 'Investment Plans'],
    ['key' => 'investments', 'href' => 'admin/investments.php', 'icon' => 'fa-chart-pie', 'label' => 'Investments'],
    ['key' => 'deposits', 'href' => 'admin/deposits.php', 'icon' => 'fa-arrow-down', 'label' => 'Deposits', 'count' => $navPendingDeposits],
    ['key' => 'withdrawals', 'href' => 'admin/withdrawals.php', 'icon' => 'fa-arrow-up', 'label' => 'Withdrawals', 'count' => $navPendingWithdrawals],
    ['key' => 'transactions', 'href' => 'admin/transactions.php', 'icon' => 'fa-receipt', 'label' => 'Transactions'],
    ['key' => 'payment-methods', 'href' => 'admin/payment-methods.php', 'icon' => 'fa-credit-card', 'label' => 'Payment Methods'],
    ['key' => 'support', 'href' => 'admin/support.php', 'icon' => 'fa-headset', 'label' => 'Support Inbox', 'count' => $navOpenTickets],
    ['key' => 'activity-log', 'href' => 'admin/activity-log.php', 'icon' => 'fa-clock-rotate-left', 'label' => 'Activity Log'],
    ['key' => 'settings', 'href' => 'admin/settings.php', 'icon' => 'fa-sliders', 'label' => 'Site Settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — <?= e(get_setting('site_name', APP_NAME)) ?> Admin</title>
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
      <a href="<?= url('admin/index.php') ?>" class="brand">
        <span class="brand-mark"><i class="fa-solid fa-chart-line"></i></span>
        <?= e(get_setting('site_name', APP_NAME)) ?> <span class="badge badge-primary" style="margin-left:2px;">Admin</span>
      </a>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section-label">Management</div>
      <?php foreach ($navItems as $item): ?>
        <a href="<?= url($item['href']) ?>" class="sidebar-link <?= $activePage === $item['key'] ? 'active' : '' ?>">
          <i class="fa-solid <?= $item['icon'] ?> ico"></i> <?= e($item['label']) ?>
          <?php if (!empty($item['count'])): ?><span class="count"><?= $item['count'] ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= url('index.php') ?>" class="sidebar-link" style="margin-bottom:2px;">
        <i class="fa-solid fa-arrow-up-right-from-square ico"></i> View Site
      </a>
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
        <a href="<?= url('admin/support.php') ?>" class="bell-btn">
          <i class="fa-regular fa-bell"></i>
          <?php if (($navPendingDeposits + $navPendingWithdrawals + $navOpenTickets) > 0): ?><span class="bell-dot"></span><?php endif; ?>
        </a>
        <a href="<?= url('admin/settings.php') ?>" class="user-chip">
          <span class="avatar-circle sm"><?= e(avatar_initials($viewer['full_name'])) ?></span>
          <span style="font-size:13.5px;font-weight:600;"><?= e(explode(' ', $viewer['full_name'])[0]) ?></span>
        </a>
      </div>
    </div>
    <div class="page-body">
      <?php require __DIR__ . '/flash.php'; ?>
