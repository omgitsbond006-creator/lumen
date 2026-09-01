<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Investment Plans';
$activePage = 'plans';
$pageDescription = 'Compare Lumen Capital investment plans — daily ROI, duration, and minimum deposit for every strategy.';

$plans = db()->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

require __DIR__ . '/partials/public_header.php';
?>

<section class="section" style="padding-top:60px;">
  <div class="container">
    <div class="text-center" style="max-width:640px;margin:0 auto 50px;" data-aos="fade-up">
      <div class="eyebrow"><span class="dot"></span> Investment Plans</div>
      <h1 style="font-size:38px;">Choose the strategy that fits your goals</h1>
      <p>Every Lumen Capital plan pays daily returns directly to your balance and returns your full principal once the term matures.</p>
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
            <li>Payout type <b style="text-transform:capitalize;"><?= e(str_replace('_', ' ', $plan['payout_type'])) ?></b></li>
            <li>Total return <b class="text-emerald"><?= percent((float) $plan['roi_percent'] * (int) $plan['duration_days']) ?></b></li>
          </ul>
          <p style="font-size:13.5px;"><?= e($plan['description']) ?></p>
          <a href="<?= url('register.php') ?>" class="btn <?= $plan['featured'] ? 'btn-primary' : 'btn-outline' ?> btn-block">Get Started</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="card glass-card" data-aos="fade-up">
      <div class="card-header">
        <div>
          <h3 class="card-title">Return calculator</h3>
          <span class="sub" style="color:var(--text-faint);font-size:12.5px;">Estimate your projected earnings before you deposit.</span>
        </div>
      </div>
      <div class="dash-grid">
        <div>
          <div class="form-group">
            <label class="form-label">Investment amount</label>
            <input type="number" id="calcAmount" class="form-control" value="1000" min="1" step="0.01">
          </div>
          <div class="form-group">
            <label class="form-label">Plan</label>
            <select id="calcPlan" class="form-control">
              <?php foreach ($plans as $plan): ?>
                <option value="<?= (int) $plan['id'] ?>"
                  data-roi="<?= (float) $plan['roi_percent'] ?>"
                  data-days="<?= (int) $plan['duration_days'] ?>"
                  data-min="<?= (float) $plan['min_amount'] ?>"
                  data-max="<?= (float) $plan['max_amount'] ?>">
                  <?= e($plan['name']) ?> — <?= percent((float) $plan['roi_percent']) ?>/day, <?= (int) $plan['duration_days'] ?>d
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint" id="calcRange"></div>
          </div>
        </div>
        <div class="card-sm" style="background:var(--bg-elevated);">
          <div style="display:flex;justify-content:space-between;margin-bottom:14px;">
            <span class="text-faint">Daily payout</span><b id="calcDaily">$0.00</b>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:14px;">
            <span class="text-faint">Total profit</span><b class="text-emerald" id="calcProfit">$0.00</b>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span class="text-faint">Return at maturity</span><b class="text-gold" id="calcTotal">$0.00</b>
          </div>
        </div>
      </div>
      <p class="text-faint" style="font-size:12px;margin-top:18px;">Illustrative estimate based on the plan's stated daily rate. Not a guarantee of returns.</p>
    </div>
  </div>
</section>

<?php
$extraScript = <<<'HTML'
<script>
  (function () {
    var amount = document.getElementById('calcAmount');
    var plan = document.getElementById('calcPlan');
    var daily = document.getElementById('calcDaily');
    var profit = document.getElementById('calcProfit');
    var total = document.getElementById('calcTotal');
    var range = document.getElementById('calcRange');
    function fmt(n) { return '$' + n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    function recalc() {
      var opt = plan.options[plan.selectedIndex];
      var roi = parseFloat(opt.getAttribute('data-roi'));
      var days = parseInt(opt.getAttribute('data-days'), 10);
      var min = parseFloat(opt.getAttribute('data-min'));
      var max = parseFloat(opt.getAttribute('data-max'));
      var amt = parseFloat(amount.value) || 0;
      var dailyAmt = amt * (roi / 100);
      var profitAmt = dailyAmt * days;
      range.textContent = 'Accepts ' + fmt(min) + ' – ' + fmt(max);
      daily.textContent = fmt(dailyAmt);
      profit.textContent = fmt(profitAmt);
      total.textContent = fmt(amt + profitAmt);
    }
    amount.addEventListener('input', recalc);
    plan.addEventListener('change', recalc);
    recalc();
  })();
</script>
HTML;
require __DIR__ . '/partials/public_footer.php';
?>
