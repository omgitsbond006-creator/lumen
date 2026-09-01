<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Home';
$activePage = 'home';
$pageDescription = 'Lumen Capital is a digital asset investment platform. Deposit, choose a yield plan, and track your returns in real time.';

$plans = db()->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Real, DB-driven platform stats for the hero strip (no invented numbers).
$totalUsers = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalDeposited = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM deposits WHERE status = 'approved'")->fetchColumn();
$totalPaidOut = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type IN ('roi_payout','maturity_payout')")->fetchColumn();
$activeInvestments = (int) db()->query("SELECT COUNT(*) FROM investments WHERE status = 'active'")->fetchColumn();

require __DIR__ . '/partials/public_header.php';
?>

<section class="hero">
  <div class="container">
    <div class="hero-grid">
      <div data-aos="fade-up">
        <div class="eyebrow"><span class="dot"></span> Now onboarding new investors</div>
        <h1>Grow your portfolio with <span class="accent">clarity</span>, not guesswork.</h1>
        <p class="hero-lead">Lumen Capital gives you curated digital-asset yield plans, transparent daily payouts, and a real-time dashboard — so you always know exactly where your money stands.</p>
        <div class="hero-cta">
          <a href="<?= url('register.php') ?>" class="btn btn-primary btn-lg"><i class="fa-solid fa-rocket"></i> Start Investing</a>
          <a href="<?= url('plans.php') ?>" class="btn btn-outline btn-lg">View Plans</a>
        </div>
        <div class="hero-trust">
          <div><strong data-counter="<?= $totalUsers ?>" data-suffix="+">0</strong>Investors onboard</div>
          <div><strong data-counter="<?= (int) $totalDeposited ?>" data-prefix="$" data-suffix="+">0</strong>Total deposited</div>
          <div><strong data-counter="<?= (int) $totalPaidOut ?>" data-prefix="$" data-suffix="+">0</strong>Paid out to investors</div>
          <div><strong data-counter="<?= $activeInvestments ?>" data-suffix="+">0</strong>Active positions</div>
        </div>
      </div>
      <div class="hero-visual" data-aos="fade-left" data-aos-delay="150">
        <div class="glass-card" style="padding:26px;">
          <div class="card-header">
            <div>
              <div class="card-title">Portfolio Value</div>
              <div class="text-faint" style="font-size:12.5px;">Live example</div>
            </div>
            <span class="badge badge-success"><i class="fa-solid fa-arrow-trend-up"></i> +18.4%</span>
          </div>
          <div class="amount-display" style="text-align:left;padding:0 0 10px;">$12,486.30</div>
          <canvas id="heroChart" height="140"></canvas>
        </div>
        <div class="hero-card-float" style="top:-22px;right:-14px;">
          <div style="font-size:11px;color:var(--text-faint);">Growth Fund</div>
          <div style="font-weight:700;color:var(--emerald-light);">+1.80% <span style="color:var(--text-faint);font-weight:400;">/ day</span></div>
        </div>
        <div class="hero-card-float" style="bottom:-18px;left:-18px;animation-delay:1.2s;">
          <div style="font-size:11px;color:var(--text-faint);">Next payout</div>
          <div style="font-weight:700;">06 : 42 : 18</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="steps-grid">
      <div class="step-card" data-aos="fade-up">
        <div class="step-num">01</div>
        <h4>Create your account</h4>
        <p>Sign up in under a minute — no paperwork, no waiting period.</p>
      </div>
      <div class="step-card" data-aos="fade-up" data-aos-delay="80">
        <div class="step-num">02</div>
        <h4>Fund your wallet</h4>
        <p>Deposit via Bitcoin, Ethereum, USDT, or bank wire. Funds are confirmed and credited to your balance.</p>
      </div>
      <div class="step-card" data-aos="fade-up" data-aos-delay="160">
        <div class="step-num">03</div>
        <h4>Choose a plan</h4>
        <p>Pick the yield strategy that matches your goals and risk appetite.</p>
      </div>
      <div class="step-card" data-aos="fade-up" data-aos-delay="240">
        <div class="step-num">04</div>
        <h4>Track &amp; withdraw</h4>
        <p>Watch daily payouts land in your dashboard, then withdraw whenever you're ready.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="plans">
  <div class="container">
    <div class="text-center" style="max-width:600px;margin:0 auto 50px;" data-aos="fade-up">
      <div class="eyebrow"><span class="dot"></span> Investment Plans</div>
      <h2>A yield plan for every kind of investor</h2>
      <p>Every plan pays out daily and returns your full principal at maturity. No lock-in surprises, no hidden fees.</p>
    </div>
    <div class="plans-grid">
      <?php foreach ($plans as $i => $plan): ?>
        <div class="plan-card <?= $plan['featured'] ? 'featured' : '' ?>" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
          <?php if ($plan['featured']): ?><div class="plan-featured-tag">Most Popular</div><?php endif; ?>
          <div class="plan-icon"><?= $plan['icon'] ?></div>
          <div class="plan-name"><?= e($plan['name']) ?></div>
          <div class="plan-tagline"><?= e($plan['tagline']) ?></div>
          <div class="plan-roi"><?= percent((float) $plan['roi_percent']) ?> <span>/ day</span></div>
          <ul class="plan-meta">
            <li>Duration <b><?= (int) $plan['duration_days'] ?> days</b></li>
            <li>Min. deposit <b><?= money((float) $plan['min_amount']) ?></b></li>
            <li>Max. deposit <b><?= money((float) $plan['max_amount']) ?></b></li>
            <li>Total return <b class="text-emerald"><?= percent((float) $plan['roi_percent'] * (int) $plan['duration_days']) ?></b></li>
          </ul>
          <a href="<?= url('register.php') ?>" class="btn <?= $plan['featured'] ? 'btn-primary' : 'btn-outline' ?> btn-block">Choose Plan</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="card glass-card" style="padding:40px;" data-aos="fade-up">
      <div class="dash-grid">
        <div>
          <div class="eyebrow"><span class="dot"></span> See it in action</div>
          <h2 style="margin-bottom:14px;">Compounding, made visual.</h2>
          <p style="max-width:460px;">This is an illustrative example of how a $1,000 position on our Growth Fund plan (1.8%/day, daily payout) could accumulate over its 30-day term versus a static balance. Actual results depend on the plan you choose — see <a href="<?= url('plans.php') ?>">all plans</a>.</p>
          <p class="text-faint" style="font-size:12px;">Illustrative projection only. Not a guarantee of future returns.</p>
        </div>
        <div>
          <canvas id="compoundChart" height="220"></canvas>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="text-center" style="max-width:600px;margin:0 auto 50px;" data-aos="fade-up">
      <div class="eyebrow"><span class="dot"></span> Why Lumen Capital</div>
      <h2>Built for transparency, from day one</h2>
    </div>
    <div class="steps-grid">
      <?php
      $features = [
          ['icon' => 'fa-shield-halved', 'title' => 'Bank-grade security', 'desc' => 'Encrypted credentials, session protection, and audited admin controls on every account.'],
          ['icon' => 'fa-clock', 'title' => 'Daily payouts', 'desc' => 'Watch your returns credit to your balance every single day, not just at maturity.'],
          ['icon' => 'fa-list-check', 'title' => 'Full transaction ledger', 'desc' => 'Every deposit, payout, and withdrawal is logged and visible to you, permanently.'],
          ['icon' => 'fa-user-plus', 'title' => 'Referral rewards', 'desc' => 'Invite friends and earn a bonus when their first deposit is approved.'],
          ['icon' => 'fa-headset', 'title' => 'Real support', 'desc' => 'A support inbox monitored by our team — not a bot maze.'],
          ['icon' => 'fa-mobile-screen', 'title' => 'Built for every device', 'desc' => 'A dashboard that feels just as sharp on your phone as it does on desktop.'],
      ];
      foreach ($features as $i => $f): ?>
        <div class="card card-hover" data-aos="fade-up" data-aos-delay="<?= $i * 70 ?>">
          <div class="stat-icon violet" style="margin-bottom:16px;"><i class="fa-solid <?= $f['icon'] ?>"></i></div>
          <h4><?= e($f['title']) ?></h4>
          <p><?= e($f['desc']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="text-center" style="max-width:600px;margin:0 auto 50px;" data-aos="fade-up">
      <div class="eyebrow"><span class="dot"></span> Testimonials</div>
      <h2>Trusted by investors who value clarity</h2>
      <p class="text-faint" style="font-size:12.5px;">Illustrative testimonials for demonstration purposes.</p>
    </div>
    <div class="testimonial-grid">
      <?php
      $testimonials = [
          ['name' => 'M. Alvarez', 'role' => 'Growth Fund investor', 'quote' => 'The daily payout view is what sold me — I can actually see my plan working instead of waiting a month to find out.'],
          ['name' => 'R. Okafor', 'role' => 'Starter Vault investor', 'quote' => 'Clean dashboard, clear numbers, and support actually replies. Exactly what I wanted from a first investment.'],
          ['name' => 'T. Nakamura', 'role' => 'Elite Portfolio investor', 'quote' => 'Having a full transaction history I can export and review gives me a lot more confidence than a black-box app.'],
      ];
      foreach ($testimonials as $i => $t): ?>
        <div class="card card-hover" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
          <div class="stars">★★★★★</div>
          <p>&ldquo;<?= e($t['quote']) ?>&rdquo;</p>
          <div class="testimonial-person">
            <div class="testimonial-avatar"><?= e(avatar_initials($t['name'])) ?></div>
            <div>
              <div style="font-weight:600;font-size:13.5px;"><?= e($t['name']) ?></div>
              <div class="text-faint" style="font-size:12px;"><?= e($t['role']) ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="glass-card text-center" style="padding:64px 30px;" data-aos="zoom-in">
      <h2 style="font-size:32px;">Ready to put your capital to work?</h2>
      <p style="max-width:480px;margin:0 auto 28px;">Create your account in minutes and make your first deposit today.</p>
      <a href="<?= url('register.php') ?>" class="btn btn-primary btn-lg"><i class="fa-solid fa-rocket"></i> Create Free Account</a>
    </div>
  </div>
</section>

<?php
$extraScript = <<<HTML
<script>
  // Hero mini sparkline (illustrative demo data)
  new Chart(document.getElementById('heroChart'), {
    type: 'line',
    data: {
      labels: Array.from({length: 14}, (_, i) => 'D' + (i+1)),
      datasets: [{
        data: [10200,10280,10350,10310,10420,10510,10600,10740,10830,10990,11150,11360,11980,12486],
        borderColor: '#8b7bff',
        backgroundColor: 'rgba(109,91,240,0.15)',
        fill: true, tension: 0.4, pointRadius: 0, borderWidth: 2.5,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { x: { display: false }, y: { display: false } },
      elements: { line: { borderJoinStyle: 'round' } },
    }
  });

  // Compounding illustration: \$1000 @ 1.8%/day for 30 days vs static balance
  var principal = 1000, roi = 1.8, days = 30;
  var compounding = [], flat = [];
  var running = principal;
  for (var i = 0; i <= days; i++) {
    flat.push(principal);
    compounding.push(Math.round(running * 100) / 100);
    running += principal * (roi / 100); // simple daily payout accumulation on principal
  }
  new Chart(document.getElementById('compoundChart'), {
    type: 'line',
    data: {
      labels: Array.from({length: days + 1}, (_, i) => 'Day ' + i),
      datasets: [
        { label: 'Growth Fund payouts', data: compounding, borderColor: '#f0b90b', backgroundColor: 'rgba(240,185,11,0.1)', fill: true, tension: 0.3, pointRadius: 0, borderWidth: 2.5 },
        { label: 'Static balance', data: flat, borderColor: '#5c6684', borderDash: [4,4], pointRadius: 0, borderWidth: 1.5 },
      ]
    },
    options: {
      plugins: { legend: { labels: { color: '#96a0b8', font: { size: 11 } } } },
      scales: {
        x: { ticks: { color: '#5c6684', maxTicksLimit: 6 }, grid: { color: 'rgba(255,255,255,0.05)' } },
        y: { ticks: { color: '#5c6684' }, grid: { color: 'rgba(255,255,255,0.05)' } },
      }
    }
  });
</script>
HTML;
require __DIR__ . '/partials/public_footer.php';
?>
