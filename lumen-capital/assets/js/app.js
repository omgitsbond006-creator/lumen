/* =====================================================================
   LUMEN CAPITAL — app.js
   Shared front-end interactivity: nav toggles, flash auto-dismiss,
   countdowns, copy-to-clipboard, accordions, amount steppers,
   password strength meter, animated counters.
   ===================================================================== */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initNavToggle();
    initSidebarToggle();
    initFlashAutoDismiss();
    initCountdowns();
    initCopyButtons();
    initAccordions();
    initAmountSteppers();
    initPasswordStrength();
    initCounters();
    initMethodTabs();
    initConfirmForms();
  });

  // -------------------------------------------------------------
  // Public nav (mobile hamburger)
  // -------------------------------------------------------------
  function initNavToggle() {
    var toggle = document.querySelector('.nav-toggle');
    var links = document.querySelector('.nav-links');
    if (!toggle || !links) return;
    toggle.addEventListener('click', function () {
      links.classList.toggle('open');
    });
  }

  // -------------------------------------------------------------
  // Dashboard sidebar (mobile)
  // -------------------------------------------------------------
  function initSidebarToggle() {
    var toggle = document.querySelector('.sidebar-toggle');
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    if (!toggle || !sidebar) return;
    function close() {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('show');
    }
    toggle.addEventListener('click', function () {
      sidebar.classList.add('open');
      if (overlay) overlay.classList.add('show');
    });
    if (overlay) overlay.addEventListener('click', close);
  }

  // -------------------------------------------------------------
  // Flash messages: auto dismiss + manual close
  // -------------------------------------------------------------
  function initFlashAutoDismiss() {
    document.querySelectorAll('.alert[data-flash]').forEach(function (el) {
      var closeBtn = el.querySelector('.alert-close');
      if (closeBtn) closeBtn.addEventListener('click', function () { el.remove(); });
      setTimeout(function () {
        el.style.transition = 'opacity .3s ease';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 300);
      }, 6000);
    });
  }

  // -------------------------------------------------------------
  // Countdown timers: <span class="countdown" data-target="ISO date">
  // -------------------------------------------------------------
  function initCountdowns() {
    var els = document.querySelectorAll('[data-countdown]');
    if (!els.length) return;

    function render() {
      els.forEach(function (el) {
        var target = new Date(el.getAttribute('data-countdown')).getTime();
        var now = Date.now();
        var diff = target - now;
        var d = el.querySelector('[data-u="d"]');
        var h = el.querySelector('[data-u="h"]');
        var m = el.querySelector('[data-u="m"]');
        var s = el.querySelector('[data-u="s"]');
        if (diff <= 0) {
          if (d) d.textContent = '00';
          if (h) h.textContent = '00';
          if (m) m.textContent = '00';
          if (s) s.textContent = '00';
          el.classList.add('countdown-done');
          return;
        }
        var days = Math.floor(diff / 86400000);
        var hours = Math.floor((diff % 86400000) / 3600000);
        var mins = Math.floor((diff % 3600000) / 60000);
        var secs = Math.floor((diff % 60000) / 1000);
        if (d) d.textContent = String(days).padStart(2, '0');
        if (h) h.textContent = String(hours).padStart(2, '0');
        if (m) m.textContent = String(mins).padStart(2, '0');
        if (s) s.textContent = String(secs).padStart(2, '0');
      });
    }
    render();
    setInterval(render, 1000);
  }

  // -------------------------------------------------------------
  // Copy-to-clipboard buttons: <button class="copy-btn" data-copy="text">
  // -------------------------------------------------------------
  function initCopyButtons() {
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var text = btn.getAttribute('data-copy');
        var done = function () {
          var original = btn.innerHTML;
          btn.innerHTML = 'Copied!';
          setTimeout(function () { btn.innerHTML = original; }, 1600);
        };
        if (navigator.clipboard) {
          navigator.clipboard.writeText(text).then(done).catch(done);
        } else {
          var ta = document.createElement('textarea');
          ta.value = text;
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
          done();
        }
      });
    });
  }

  // -------------------------------------------------------------
  // FAQ accordions
  // -------------------------------------------------------------
  function initAccordions() {
    document.querySelectorAll('.accordion-header').forEach(function (header) {
      header.addEventListener('click', function () {
        var item = header.closest('.accordion-item');
        var wasOpen = item.classList.contains('open');
        item.parentElement.querySelectorAll('.accordion-item').forEach(function (i) {
          i.classList.remove('open');
        });
        if (!wasOpen) item.classList.add('open');
      });
    });
  }

  // -------------------------------------------------------------
  // Amount stepper / quick-select buttons for deposit/invest/withdraw
  // <button class="quick-amounts" data-fill="500"> targeting #amount-input
  // -------------------------------------------------------------
  function initAmountSteppers() {
    document.querySelectorAll('[data-fill]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var targetSel = btn.closest('[data-amount-target]') ? btn.closest('[data-amount-target]').getAttribute('data-amount-target') : '#amount';
        var input = document.querySelector(targetSel);
        if (!input) return;
        var val = btn.getAttribute('data-fill');
        if (val === 'max') {
          input.value = btn.getAttribute('data-max') || input.value;
        } else {
          input.value = val;
        }
        input.dispatchEvent(new Event('input'));
        btn.parentElement.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
      });
    });
  }

  // -------------------------------------------------------------
  // Password strength meter
  // -------------------------------------------------------------
  function initPasswordStrength() {
    var input = document.querySelector('[data-password-strength]');
    var meter = document.querySelector('#password-strength-bar');
    var label = document.querySelector('#password-strength-label');
    if (!input || !meter) return;
    input.addEventListener('input', function () {
      var val = input.value;
      var score = 0;
      if (val.length >= 8) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;
      if (val.length >= 12) score++;
      var pct = (score / 5) * 100;
      var colors = ['#f0465c', '#f0465c', '#f59e0b', '#f59e0b', '#22c55e', '#22c55e'];
      var labels = ['Very weak', 'Very weak', 'Weak', 'Fair', 'Strong', 'Excellent'];
      meter.style.width = pct + '%';
      meter.style.background = colors[score];
      if (label) label.textContent = val ? labels[score] : '';
    });
  }

  // -------------------------------------------------------------
  // Animated number counters for landing page stats
  // <span data-counter="12500" data-prefix="$" data-suffix="+">
  // -------------------------------------------------------------
  function initCounters() {
    var els = document.querySelectorAll('[data-counter]');
    if (!els.length) return;
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        observer.unobserve(el);
        var target = parseFloat(el.getAttribute('data-counter'));
        var prefix = el.getAttribute('data-prefix') || '';
        var suffix = el.getAttribute('data-suffix') || '';
        var decimals = el.getAttribute('data-decimals') ? parseInt(el.getAttribute('data-decimals'), 10) : 0;
        var duration = 1400;
        var startTime = null;
        function step(ts) {
          if (!startTime) startTime = ts;
          var progress = Math.min((ts - startTime) / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          var current = target * eased;
          el.textContent = prefix + current.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
          if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
      });
    }, { threshold: 0.4 });
    els.forEach(function (el) { observer.observe(el); });
  }

  // -------------------------------------------------------------
  // Deposit method tabs
  // -------------------------------------------------------------
  function initMethodTabs() {
    var tabs = document.querySelectorAll('.method-tab');
    if (!tabs.length) return;
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var group = tab.closest('[data-method-group]');
        if (!group) return;
        group.querySelectorAll('.method-tab').forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');
        group.querySelectorAll('[data-method-panel]').forEach(function (p) {
          p.style.display = (p.getAttribute('data-method-panel') === tab.getAttribute('data-method')) ? 'block' : 'none';
        });
        var hiddenInput = group.querySelector('[data-method-input]');
        if (hiddenInput) hiddenInput.value = tab.getAttribute('data-method');
      });
    });
  }

  // -------------------------------------------------------------
  // Confirm dialogs for destructive admin actions
  // <form data-confirm="Are you sure?">
  // -------------------------------------------------------------
  function initConfirmForms() {
    document.querySelectorAll('[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!confirm(form.getAttribute('data-confirm'))) {
          e.preventDefault();
        }
      });
    });
  }
})();
