<?php
/**
 * Shared <head> + navbar for all public marketing pages.
 * Expects (optionally): $pageTitle, $activePage, $pageDescription
 */
$pageTitle = $pageTitle ?? get_setting('site_name', APP_NAME);
$pageDescription = $pageDescription ?? 'A modern digital asset investment platform — deposit, invest across curated yield plans, and track your portfolio in real time.';
$activePage = $activePage ?? '';
$loggedIn = is_logged_in();
$viewer = $loggedIn ? current_user() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — <?= e(get_setting('site_name', APP_NAME)) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="stylesheet" href="<?= asset('vendor/fonts/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/aos/aos.css') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
</head>
<body class="bg-mesh">

<nav class="navbar">
  <div class="container">
    <a href="<?= url('index.php') ?>" class="brand">
      <span class="brand-mark"><i class="fa-solid fa-chart-line"></i></span>
      <?= e(get_setting('site_name', APP_NAME)) ?>
    </a>
    <div class="nav-links" id="navLinks">
      <a href="<?= url('index.php') ?>" class="<?= $activePage === 'home' ? 'active' : '' ?>">Home</a>
      <a href="<?= url('plans.php') ?>" class="<?= $activePage === 'plans' ? 'active' : '' ?>">Investment Plans</a>
      <a href="<?= url('about.php') ?>" class="<?= $activePage === 'about' ? 'active' : '' ?>">About</a>
      <a href="<?= url('faq.php') ?>" class="<?= $activePage === 'faq' ? 'active' : '' ?>">FAQ</a>
      <a href="<?= url('contact.php') ?>" class="<?= $activePage === 'contact' ? 'active' : '' ?>">Contact</a>
    </div>
    <div class="nav-actions">
      <?php if ($loggedIn): ?>
        <a href="<?= url(is_admin() ? 'admin/index.php' : 'user/dashboard.php') ?>" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-gauge"></i> Dashboard
        </a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" class="btn btn-ghost btn-sm">Sign In</a>
        <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">Get Started</a>
      <?php endif; ?>
      <button class="nav-toggle" id="navToggle" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</nav>

<?php require __DIR__ . '/flash.php'; ?>
