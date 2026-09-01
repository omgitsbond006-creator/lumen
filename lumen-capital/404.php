<?php
require_once __DIR__ . '/includes/init.php';
http_response_code(404);

$pageTitle = 'Page Not Found';
$pageDescription = 'The page you were looking for could not be found.';

require __DIR__ . '/partials/public_header.php';
?>

<section class="section text-center" style="padding-top:120px;">
  <div class="container-narrow">
    <div class="eyebrow"><span class="dot"></span> 404</div>
    <h1 style="font-size:44px;">This page took an early withdrawal.</h1>
    <p style="max-width:480px;margin:0 auto 30px;">We couldn't find the page you were looking for. It may have moved, or the link might be out of date.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
      <a href="<?= url('index.php') ?>" class="btn btn-primary btn-lg"><i class="fa-solid fa-house"></i> Back to Home</a>
      <a href="<?= url('contact.php') ?>" class="btn btn-outline btn-lg">Contact Support</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/public_footer.php'; ?>
