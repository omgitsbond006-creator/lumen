<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'FAQ';
$activePage = 'faq';
$pageDescription = 'Answers to common questions about deposits, withdrawals, plans, and security on Lumen Capital.';

$faqGroups = [
    'Getting Started' => [
        ['q' => 'How do I open an account?', 'a' => 'Click "Get Started", fill in your name, email, and a password, and you\'re in. No paperwork or waiting period.'],
        ['q' => 'Is there a minimum age or eligibility requirement?', 'a' => 'This is a demonstration platform built for an academic project — it is not intended for real financial transactions.'],
        ['q' => 'Can I have more than one account?', 'a' => 'Each investor should maintain a single account tied to one email address so your ledger and referral history stay accurate.'],
    ],
    'Deposits & Withdrawals' => [
        ['q' => 'How do deposits work?', 'a' => 'From your dashboard, choose a currency (Bitcoin, Ethereum, USDT, or bank wire), send funds to the address shown, then submit the reference for our team to confirm. Once approved, the amount is credited to your balance instantly.'],
        ['q' => 'How long does approval take?', 'a' => 'Deposit and withdrawal requests are reviewed by our admin team. In this demo environment, an administrator can process a request in seconds from the admin dashboard.'],
        ['q' => 'Is there a withdrawal fee?', 'a' => 'A small percentage fee applies to withdrawals to cover network costs — the exact rate is shown before you confirm your request.'],
        ['q' => 'What is the minimum withdrawal amount?', 'a' => 'The current minimum withdrawal is shown directly on the withdrawal page and is configurable by the platform administrator.'],
    ],
    'Plans & Returns' => [
        ['q' => 'How is the daily ROI calculated?', 'a' => 'Each plan has a fixed daily percentage applied to your invested principal. That amount is credited to your balance once per day for the duration of the plan.'],
        ['q' => 'Do I get my principal back?', 'a' => 'Yes — your original investment amount remains yours and is not deducted by daily payouts; it becomes available again once the position matures or if you choose to reinvest.'],
        ['q' => 'Can I run multiple investments at once?', 'a' => 'Yes, you can hold as many active investments across different plans as your available balance allows.'],
        ['q' => 'What happens when a plan matures?', 'a' => 'On the maturity date, the position is marked complete and moves out of your active investments list — you keep every daily payout already credited.'],
    ],
    'Security' => [
        ['q' => 'How is my account protected?', 'a' => 'Passwords are hashed (never stored in plain text), sessions are protected against fixation, and repeated failed logins temporarily lock the account.'],
        ['q' => 'Who can see my transaction history?', 'a' => 'Only you and platform administrators can view your ledger. Every entry is immutable once recorded.'],
    ],
];

require __DIR__ . '/partials/public_header.php';
?>

<section class="section" style="padding-top:60px;">
  <div class="container-narrow" data-aos="fade-up">
    <div class="text-center" style="margin-bottom:50px;">
      <div class="eyebrow"><span class="dot"></span> FAQ</div>
      <h1 style="font-size:36px;">Frequently asked questions</h1>
      <p>Can't find what you're looking for? <a href="<?= url('contact.php') ?>">Contact our team</a>.</p>
    </div>

    <?php foreach ($faqGroups as $group => $items): ?>
      <h4 style="margin:36px 0 6px;color:var(--text-faint);text-transform:uppercase;font-size:12.5px;letter-spacing:.06em;"><?= e($group) ?></h4>
      <div class="accordion">
        <?php foreach ($items as $item): ?>
          <div class="accordion-item">
            <div class="accordion-header">
              <span><?= e($item['q']) ?></span>
              <span class="icon"><i class="fa-solid fa-plus"></i></span>
            </div>
            <div class="accordion-body"><p><?= e($item['a']) ?></p></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <div class="card glass-card" id="risk" style="margin-top:50px;">
      <h3><i class="fa-solid fa-triangle-exclamation text-gold"></i> Risk disclosure</h3>
      <p>All investing carries risk, including the potential loss of principal. Daily yield figures shown on this platform are the plan's stated rate and are not guaranteed returns from any real market or fund. This platform is an academic software demonstration; no real money, digital assets, or securities are transacted.</p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/public_footer.php'; ?>
