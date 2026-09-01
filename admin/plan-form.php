<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

$planId = (int) ($_GET['id'] ?? 0);
$plan = null;
if ($planId) {
    $stmt = db()->prepare('SELECT * FROM plans WHERE id = ?');
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();
    if (!$plan) { flash('danger', 'Plan not found.'); redirect('admin/plans.php'); }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? '📈');
    $themeColor = trim($_POST['theme_color'] ?? 'emerald');
    $minAmount = (float) ($_POST['min_amount'] ?? 0);
    $maxAmount = (float) ($_POST['max_amount'] ?? 0);
    $roiPercent = (float) ($_POST['roi_percent'] ?? 0);
    $payoutType = $_POST['payout_type'] ?? 'daily';
    $durationDays = (int) ($_POST['duration_days'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    if ($name === '') $errors[] = 'Plan name is required.';
    if ($minAmount <= 0 || $maxAmount <= $minAmount) $errors[] = 'Max amount must be greater than min amount.';
    if ($roiPercent <= 0) $errors[] = 'ROI percent must be greater than zero.';
    if ($durationDays <= 0) $errors[] = 'Duration must be at least 1 day.';

    if (!$errors) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        if ($plan) {
            db()->prepare('UPDATE plans SET name=?, slug=?, tagline=?, description=?, icon=?, theme_color=?, min_amount=?, max_amount=?, roi_percent=?, payout_type=?, duration_days=?, featured=?, is_active=?, sort_order=? WHERE id=?')
                ->execute([$name, $slug, $tagline, $description, $icon, $themeColor, $minAmount, $maxAmount, $roiPercent, $payoutType, $durationDays, $featured, $isActive, $sortOrder, $plan['id']]);
            log_activity($viewer['id'], 'Updated plan', 'plan', $plan['id'], $name);
            flash('success', 'Plan updated.');
        } else {
            $stmt = db()->prepare('INSERT INTO plans (name, slug, tagline, description, icon, theme_color, min_amount, max_amount, roi_percent, payout_type, duration_days, featured, is_active, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $slug, $tagline, $description, $icon, $themeColor, $minAmount, $maxAmount, $roiPercent, $payoutType, $durationDays, $featured, $isActive, $sortOrder]);
            log_activity($viewer['id'], 'Created plan', 'plan', (int) db()->lastInsertId(), $name);
            flash('success', 'Plan created.');
        }
        redirect('admin/plans.php');
    }
}

$pageTitle = $plan ? 'Edit Plan' : 'Add Plan';
$activePage = 'plans';
require __DIR__ . '/../partials/admin_header.php';
?>

<a href="<?= url('admin/plans.php') ?>" class="btn btn-ghost btn-sm" style="margin-bottom:16px;"><i class="fa-solid fa-arrow-left"></i> All Plans</a>

<div class="card" style="max-width:700px;">
  <?php if ($errors): ?><div class="alert alert-danger"><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>
  <form method="POST" action="<?= url('admin/plan-form.php' . ($plan ? '?id=' . $plan['id'] : '')) ?>">
    <?= csrf_field() ?>
    <div class="dash-grid" style="grid-template-columns:1fr 1fr;">
      <div class="form-group"><label class="form-label">Plan name</label><input type="text" name="name" class="form-control" value="<?= e($plan['name'] ?? '') ?>" required></div>
      <div class="form-group"><label class="form-label">Icon (emoji)</label><input type="text" name="icon" class="form-control" value="<?= e($plan['icon'] ?? '📈') ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Tagline</label><input type="text" name="tagline" class="form-control" value="<?= e($plan['tagline'] ?? '') ?>"></div>
    <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= e($plan['description'] ?? '') ?></textarea></div>
    <div class="dash-grid" style="grid-template-columns:1fr 1fr;">
      <div class="form-group"><label class="form-label">Minimum amount ($)</label><input type="number" step="0.01" name="min_amount" class="form-control" value="<?= e($plan['min_amount'] ?? '100') ?>" required></div>
      <div class="form-group"><label class="form-label">Maximum amount ($)</label><input type="number" step="0.01" name="max_amount" class="form-control" value="<?= e($plan['max_amount'] ?? '1000') ?>" required></div>
    </div>
    <div class="dash-grid" style="grid-template-columns:1fr 1fr;">
      <div class="form-group"><label class="form-label">ROI percent (per payout period)</label><input type="number" step="0.001" name="roi_percent" class="form-control" value="<?= e($plan['roi_percent'] ?? '1.5') ?>" required></div>
      <div class="form-group"><label class="form-label">Duration (days)</label><input type="number" name="duration_days" class="form-control" value="<?= e($plan['duration_days'] ?? '30') ?>" required></div>
    </div>
    <div class="dash-grid" style="grid-template-columns:1fr 1fr;">
      <div class="form-group">
        <label class="form-label">Payout type</label>
        <select name="payout_type" class="form-control">
          <option value="daily" <?= ($plan['payout_type'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Daily</option>
          <option value="end_of_term" <?= ($plan['payout_type'] ?? '') === 'end_of_term' ? 'selected' : '' ?>>End of term (lump sum)</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Sort order</label><input type="number" name="sort_order" class="form-control" value="<?= e($plan['sort_order'] ?? '0') ?>"></div>
    </div>
    <div class="checkbox-row" style="margin-bottom:14px;">
      <input type="checkbox" name="featured" id="featured" <?= !empty($plan['featured']) ? 'checked' : '' ?>><label for="featured">Feature this plan (shown as "Most Popular")</label>
    </div>
    <div class="checkbox-row" style="margin-bottom:22px;">
      <input type="checkbox" name="is_active" id="is_active" <?= ($plan['is_active'] ?? 1) ? 'checked' : '' ?>><label for="is_active">Visible to investors</label>
    </div>
    <button type="submit" class="btn btn-primary btn-block"><?= $plan ? 'Save Changes' : 'Create Plan' ?></button>
  </form>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
