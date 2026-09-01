<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'create') {
        $stmt = db()->prepare('INSERT INTO payment_methods (currency_code, currency_name, network, address, instructions, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            strtoupper(trim($_POST['currency_code'] ?? '')),
            trim($_POST['currency_name'] ?? ''),
            trim($_POST['network'] ?? '') ?: null,
            trim($_POST['address'] ?? ''),
            trim($_POST['instructions'] ?? '') ?: null,
            (int) ($_POST['sort_order'] ?? 0),
        ]);
        log_activity($viewer['id'], 'Added payment method', 'payment_method', (int) db()->lastInsertId());
        flash('success', 'Payment method added.');
    } elseif ($action === 'toggle' && $id) {
        db()->prepare('UPDATE payment_methods SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
        flash('success', 'Payment method updated.');
    } elseif ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM payment_methods WHERE id = ?')->execute([$id]);
        log_activity($viewer['id'], 'Deleted payment method', 'payment_method', $id);
        flash('success', 'Payment method deleted.');
    }
    redirect('admin/payment-methods.php');
}

$methods = db()->query('SELECT * FROM payment_methods ORDER BY sort_order ASC')->fetchAll();

$pageTitle = 'Payment Methods';
$activePage = 'payment-methods';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="dash-grid">
  <div>
    <div class="card" style="padding:0;">
      <div class="table-wrap" style="border:none;">
        <table class="table">
          <thead><tr><th>Currency</th><th>Network</th><th>Address</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($methods as $m): ?>
            <tr>
              <td><b><?= e($m['currency_code']) ?></b><div class="text-faint" style="font-size:12px;"><?= e($m['currency_name']) ?></div></td>
              <td class="text-faint"><?= e($m['network'] ?: '—') ?></td>
              <td class="mono" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($m['address']) ?></td>
              <td><span class="badge <?= $m['is_active'] ? 'badge-success' : 'badge-neutral' ?>"><?= $m['is_active'] ? 'Active' : 'Hidden' ?></span></td>
              <td>
                <div style="display:flex;gap:6px;">
                  <form method="POST" action="<?= url('admin/payment-methods.php') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="toggle">
                    <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-<?= $m['is_active'] ? 'eye-slash' : 'eye' ?>"></i></button></form>
                  <form method="POST" action="<?= url('admin/payment-methods.php') ?>" data-confirm="Delete this payment method?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div>
    <div class="card">
      <h3 class="card-title" style="margin-bottom:16px;">Add Payment Method</h3>
      <form method="POST" action="<?= url('admin/payment-methods.php') ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <div class="form-group"><label class="form-label">Currency code</label><input type="text" name="currency_code" class="form-control" placeholder="e.g. BTC" required></div>
        <div class="form-group"><label class="form-label">Currency name</label><input type="text" name="currency_name" class="form-control" placeholder="e.g. Bitcoin" required></div>
        <div class="form-group"><label class="form-label">Network <span class="text-faint">(optional)</span></label><input type="text" name="network" class="form-control" placeholder="e.g. TRC-20"></div>
        <div class="form-group"><label class="form-label">Deposit address</label><input type="text" name="address" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Instructions <span class="text-faint">(optional)</span></label><textarea name="instructions" class="form-control" rows="2"></textarea></div>
        <div class="form-group"><label class="form-label">Sort order</label><input type="number" name="sort_order" class="form-control" value="0"></div>
        <button type="submit" class="btn btn-primary btn-block">Add Method</button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
