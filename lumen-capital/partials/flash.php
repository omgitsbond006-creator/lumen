<?php
/**
 * Renders queued flash messages as a floating toast stack.
 * Expects init.php to already be loaded.
 */
$flashes = get_flashes();
if ($flashes):
?>
<div id="flash-stack">
  <?php foreach ($flashes as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" data-flash>
      <span><?= e($f['message']) ?></span>
      <button type="button" class="alert-close">&times;</button>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
