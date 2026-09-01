<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $planId = (int) ($_POST['plan_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_active') {
        db()->prepare('UPDATE plans SET is_active = NOT is_active WHERE id = ?')->execute([$planId]);
        log_activity($viewer['id'], 'Toggled plan visibility', 'plan', $planId);
        flash('success', 'Plan visibility updated.');
    } elseif ($action === 'delete') {
        $inUse = db()->prepare('SELECT COUNT(*) FROM investments WHERE plan_id = ?');
        $inUse->execute([$planId]);
        if ((int) $inUse->fetchColumn() > 0) {
            flash('danger', 'This plan has existing investments and cannot be deleted. Deactivate it instead.');
        } else {
            db()->prepare('DELETE FROM plans WHERE id = ?')->execute([$planId]);
            log_activity($viewer['id'], 'Deleted plan', 'plan', $planId);
            flash('success', 'Plan deleted.');
        }
    } elseif ($action === 'toggle_featured') {
        db()->prepare('UPDATE plans SET featured = NOT featured WHERE id = ?')->execute([$planId]);
        flash('success', 'Featured status updated.');
    }
    redirect('admin/plans.php');
}

$plans = db()->query('SELECT p.*, (SELECT COUNT(*) FROM investments i WHERE i.plan_id = p.id) as inv_count FROM plans p ORDER BY sort_order ASC')->fetchAll();

$pageTitle = 'Investment Plans';
$activePage = 'plans';
require __DIR__ . '/../partials/admin_header.php';
?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
  <a href="<?= url('admin/plan-form.php') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Plan</a>
</div>

<div class="card" style="padding:0;">
  <div class="table-wrap" style="border:none;">
    <table class="table">
      <thead><tr><th>Plan</th><th>ROI / day</th><th>Duration</th><th>Range</th><th>Investors</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($plans as $p): ?>
        <tr>
          <td><span style="font-size:18px;margin-right:6px;"><?= $p['icon'] ?></span><b><?= e($p['name']) ?></b><?php if ($p['featured']): ?> <span class="badge badge-primary">Featured</span><?php endif; ?></td>
          <td class="mono"><?= percent((float) $p['roi_percent']) ?></td>
          <td><?= (int) $p['duration_days'] ?> days</td>
          <td class="mono"><?= money((float) $p['min_amount']) ?> &ndash; <?= money((float) $p['max_amount']) ?></td>
          <td><?= (int) $p['inv_count'] ?></td>
          <td><span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-neutral' ?>"><?= $p['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="<?= url('admin/plan-form.php?id=' . $p['id']) ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" action="<?= url('admin/plans.php') ?>"><?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= $p['id'] ?>"><input type="hidden" name="action" value="toggle_active">
                <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-<?= $p['is_active'] ? 'eye-slash' : 'eye' ?>"></i></button></form>
              <form method="POST" action="<?= url('admin/plans.php') ?>"><?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= $p['id'] ?>"><input type="hidden" name="action" value="toggle_featured">
                <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-star"></i></button></form>
              <form method="POST" action="<?= url('admin/plans.php') ?>" data-confirm="Delete this plan permanently?"><?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= $p['id'] ?>"><input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
