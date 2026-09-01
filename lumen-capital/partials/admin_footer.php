    </div><!-- /.page-body -->
  </div><!-- /.main-content -->
</div><!-- /.app-shell -->

<script src="<?= asset('js/app.js') ?>"></script>
<script>
  (function () {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (toggle && sidebar) {
      toggle.addEventListener('click', function () {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('show');
      });
    }
    if (overlay) {
      overlay.addEventListener('click', function () {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
      });
    }
  })();
</script>
<?php if (!empty($extraScript)) echo $extraScript; ?>
</body>
</html>
