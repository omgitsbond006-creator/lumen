<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Contact';
$activePage = 'contact';
$pageDescription = 'Get in touch with the Lumen Capital support team.';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($subject === '' || mb_strlen($subject) > 160) $errors[] = 'Please enter a subject.';
    if ($message === '' || mb_strlen($message) < 10) $errors[] = 'Your message should be at least 10 characters.';

    if (!$errors) {
        $viewer = current_user();
        $stmt = db()->prepare('INSERT INTO support_messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$viewer['id'] ?? null, $name, $email, $subject, $message]);

        flash('success', 'Thanks for reaching out — our team will get back to you shortly.');
        redirect('contact.php');
    }
    set_old($_POST);
}

require __DIR__ . '/partials/public_header.php';
?>

<section class="section" style="padding-top:60px;">
  <div class="container">
    <div class="text-center" style="max-width:560px;margin:0 auto 50px;" data-aos="fade-up">
      <div class="eyebrow"><span class="dot"></span> Contact</div>
      <h1 style="font-size:36px;">We'd love to hear from you</h1>
      <p>Questions about a plan, a deposit, or your account? Send us a message.</p>
    </div>

    <div class="dash-grid" data-aos="fade-up">
      <div class="card">
        <?php if ($errors): ?>
          <div class="alert alert-danger"><span><?= e(implode(' ', $errors)) ?></span></div>
        <?php endif; ?>
        <form method="POST" action="<?= url('contact.php') ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">Full name</label>
            <input type="text" name="name" class="form-control" value="<?= old('name', current_user()['full_name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" value="<?= old('email', current_user()['email'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Subject</label>
            <input type="text" name="subject" class="form-control" value="<?= old('subject') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="5" required><?= old('message') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Send Message</button>
        </form>
      </div>

      <div>
        <div class="card card-sm" style="margin-bottom:16px;">
          <div class="stat-icon violet" style="margin-bottom:14px;"><i class="fa-solid fa-envelope"></i></div>
          <h4 style="margin-bottom:6px;">Email support</h4>
          <p><?= e(get_setting('support_email')) ?></p>
        </div>
        <div class="card card-sm" style="margin-bottom:16px;">
          <div class="stat-icon gold" style="margin-bottom:14px;"><i class="fa-solid fa-clock"></i></div>
          <h4 style="margin-bottom:6px;">Response time</h4>
          <p>Our team typically responds within one business day.</p>
        </div>
        <div class="card card-sm">
          <div class="stat-icon emerald" style="margin-bottom:14px;"><i class="fa-solid fa-circle-question"></i></div>
          <h4 style="margin-bottom:6px;">Check the FAQ first</h4>
          <p>Most questions about deposits, plans, and security are answered on our <a href="<?= url('faq.php') ?>">FAQ page</a>.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php clear_old(); require __DIR__ . '/partials/public_footer.php'; ?>
