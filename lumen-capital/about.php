<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'About Us';
$activePage = 'about';
$pageDescription = 'Learn about the mission and principles behind Lumen Capital.';

require __DIR__ . '/partials/public_header.php';
?>

<section class="section" style="padding-top:60px;">
  <div class="container-narrow text-center" data-aos="fade-up">
    <div class="eyebrow"><span class="dot"></span> About Lumen Capital</div>
    <h1 style="font-size:36px;">Investing should feel like clarity, not a leap of faith.</h1>
    <p style="font-size:16px;">We built Lumen Capital because most investment platforms hide the one thing investors actually want: a clear, honest view of where their money is and how it's performing.</p>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="dash-grid" data-aos="fade-up">
      <div class="card">
        <h3>Our mission</h3>
        <p>To give everyday investors institutional-quality tooling — real-time dashboards, transparent ledgers, and predictable plan structures — without the jargon or the opacity that usually comes with digital asset investing.</p>
        <p>Every deposit, payout, and withdrawal on Lumen Capital is logged permanently and visible to the account holder. Nothing happens to your balance that you can't see for yourself.</p>
      </div>
      <div class="card">
        <h3>What we stand for</h3>
        <ul style="display:flex;flex-direction:column;gap:12px;padding-left:0;list-style:none;">
          <li><i class="fa-solid fa-circle-check text-emerald"></i>&nbsp; Radical transparency in every transaction</li>
          <li><i class="fa-solid fa-circle-check text-emerald"></i>&nbsp; No hidden fees on deposits or plan participation</li>
          <li><i class="fa-solid fa-circle-check text-emerald"></i>&nbsp; Clear risk disclosure, always</li>
          <li><i class="fa-solid fa-circle-check text-emerald"></i>&nbsp; Support from real people, not automated loops</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="text-center" style="max-width:560px;margin:0 auto 40px;" data-aos="fade-up">
      <div class="eyebrow"><span class="dot"></span> How we operate</div>
      <h2>A platform built around three principles</h2>
    </div>
    <div class="steps-grid">
      <div class="card card-hover" data-aos="fade-up">
        <div class="stat-icon violet" style="margin-bottom:16px;"><i class="fa-solid fa-eye"></i></div>
        <h4>Visibility first</h4>
        <p>Your dashboard shows live balances, active positions, and a full historical ledger — no waiting on statements.</p>
      </div>
      <div class="card card-hover" data-aos="fade-up" data-aos-delay="80">
        <div class="stat-icon gold" style="margin-bottom:16px;"><i class="fa-solid fa-scale-balanced"></i></div>
        <h4>Predictable structure</h4>
        <p>Plan terms, minimums, and daily rates are fixed and published upfront. What you see is what you get.</p>
      </div>
      <div class="card card-hover" data-aos="fade-up" data-aos-delay="160">
        <div class="stat-icon rose" style="margin-bottom:16px;"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <h4>Accountable support</h4>
        <p>Every support request lands in an inbox our team actively works through — you'll always get a real reply.</p>
      </div>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="card glass-card text-center" data-aos="fade-up">
      <div class="eyebrow"><span class="dot"></span> A note on this project</div>
      <h3>Built as an academic demonstration</h3>
      <p style="max-width:600px;margin:0 auto;">Lumen Capital is a fully-functional demo platform showcasing account management, a plan-based investment engine, and an administrative back office — built end-to-end in PHP and MySQL. No real funds or cryptocurrency are ever transacted.</p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/public_footer.php'; ?>
