/* Small progressive enhancements. Every page also works without JS except the Kanban drag. */
(function () {
  'use strict';

  // Confirm before destructive actions: <form data-confirm="Delete this?">
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) {
      e.preventDefault();
    }
  });

  // Auto-submit filter forms when a select changes: <form class="js-autosubmit">
  document.querySelectorAll('form.js-autosubmit select, form.js-autosubmit input[type=checkbox]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  // Ask for a reason when a due/target date is changed on an existing record.
  document.querySelectorAll('[data-reason-for]').forEach(function (wrap) {
    var input = document.getElementById(wrap.getAttribute('data-reason-for'));
    if (!input) return;
    var original = input.getAttribute('data-original') || '';
    var reason = wrap.querySelector('input,textarea');
    function sync() {
      var changed = original !== '' && input.value !== original;
      wrap.classList.toggle('d-none', !changed);
      if (reason) reason.required = changed;
    }
    input.addEventListener('change', sync);
    input.addEventListener('input', sync);
    sync();
  });

  // POST helper for AJAX calls (adds CSRF + AJAX headers).
  window.appPost = function (url, data) {
    var body = new URLSearchParams(data);
    body.append('_csrf', window.APP.csrf);
    return fetch(url, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body,
      credentials: 'same-origin'
    }).then(function (r) {
      return r.json().then(function (j) { if (!r.ok || !j.ok) throw new Error(j.error || 'Error'); return j; });
    });
  };

  // Enable Bootstrap tooltips.
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) { new bootstrap.Tooltip(el); });
  }
})();

/*
 * Location at Start Day / Close Day: <form data-location="ask|required">.
 * Asks the browser for the position, adds it as hidden fields, then submits. With "ask" the form is sent
 * even when the person blocks location (the status is recorded); with "required" it is not.
 * Browsers only allow location on HTTPS pages (and on localhost).
 */
(function () {
  'use strict';
  var t = (window.APP && window.APP.t) || {};

  function field(form, name, value) {
    var input = form.querySelector('input[name="' + name + '"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      form.appendChild(input);
    }
    input.value = value;
  }

  document.querySelectorAll('form[data-location]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (form.getAttribute('data-loc-done')) return;   // second pass: really submit
      e.preventDefault();
      var required = form.getAttribute('data-location') === 'required';
      var btn = form.querySelector('button:not([type]), button[type="submit"]');
      var btnHtml = btn ? btn.innerHTML : '';

      function send(status) {
        field(form, 'loc_status', status);
        form.setAttribute('data-loc-done', '1');
        form.submit();
      }
      function fail(status) {
        if (required) {
          if (btn) { btn.disabled = false; btn.innerHTML = btnHtml; }
          window.alert(t.locationRequired || 'Location is required.');
          return;
        }
        send(status);
      }

      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ' + (t.locating || '');
      }
      if (!navigator.geolocation || window.isSecureContext === false) {
        fail('unsupported');
        return;
      }
      navigator.geolocation.getCurrentPosition(function (pos) {
        field(form, 'loc_lat', pos.coords.latitude);
        field(form, 'loc_lng', pos.coords.longitude);
        field(form, 'loc_acc', pos.coords.accuracy);
        send('ok');
      }, function (err) {
        fail(err.code === 1 ? 'denied' : (err.code === 3 ? 'timeout' : 'unavailable'));
      }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
    });
  });
})();

/* Collapsible sidebar on desktop. The state is kept per browser; <head> applies it before the page paints. */
(function () {
  'use strict';
  var root = document.documentElement;
  var toggles = document.querySelectorAll('[data-sidebar-toggle]');
  if (!toggles.length) return;
  var placement = root.getAttribute('dir') === 'rtl' ? 'left' : 'right';

  var desktop = window.matchMedia('(min-width: 992px)');
  function iconsOnly() {   // on phones the menu is a full-width slide-in panel, never icons-only
    return root.classList.contains('sidebar-collapsed') && desktop.matches;
  }

  // A menu item shows its full label as a tooltip when the menu is collapsed to icons,
  // or when the label is too long for the menu and is cut off with "…".
  var tips = [];
  if (window.bootstrap) {
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(function (link) {
      var label = link.querySelector('span:not(.badge)');
      if (!label) return;
      tips.push(new bootstrap.Tooltip(link, { title: label.textContent, placement: placement, trigger: 'hover focus' }));
      link.addEventListener('show.bs.tooltip', function (e) {
        var cutOff = label.scrollWidth > label.clientWidth + 1;
        if (!iconsOnly() && !cutOff) e.preventDefault();
      });
    });
  }

  function sync() {
    var collapsed = root.classList.contains('sidebar-collapsed');
    toggles.forEach(function (b) { b.setAttribute('aria-expanded', collapsed ? 'false' : 'true'); });
    tips.forEach(function (t) { t.hide(); });
  }
  if (desktop.addEventListener) desktop.addEventListener('change', sync);

  toggles.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var collapsed = root.classList.toggle('sidebar-collapsed');
      try { localStorage.setItem('seyal.sidebar', collapsed ? 'collapsed' : 'expanded'); } catch (e) {}
      sync();
    });
  });
  sync();
})();

/* Show/hide a block with a switch: <input data-toggle-target="#id"> */
(function () {
  'use strict';
  document.querySelectorAll('[data-toggle-target]').forEach(function (sw) {
    var target = document.querySelector(sw.getAttribute('data-toggle-target'));
    if (!target) return;
    function sync() { target.classList.toggle('d-none', !sw.checked); }
    sw.addEventListener('change', sync);
    sync();
  });
  // Live clock on the Start Day screen.
  var clock = document.getElementById('clock');
  if (clock) {
    setInterval(function () {
      clock.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }, 15000);
  }
})();
