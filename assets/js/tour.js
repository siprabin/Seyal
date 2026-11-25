/*
 * Seyal guided tour. Steps come from app/tour.php (window.SEYAL_TOUR).
 * - Starts by itself on a person's first login; "?" → Take the tour (any [data-tour-start] button) replays it.
 * - A step points at an element with data-tour="…". If that element is not on the page (e.g. manager-only
 *   menu items) the step is skipped; if it exists but is hidden (e.g. the side menu on a phone) the step
 *   is shown in the middle of the screen instead.
 * - All text is set with textContent, never as HTML.
 */
(function () {
  'use strict';
  var cfg = window.SEYAL_TOUR;
  if (!cfg || !cfg.steps) return;
  var L = cfg.labels || {};
  var rtl = document.documentElement.getAttribute('dir') === 'rtl';
  var state = null;   // { steps, i, els, onKey, onMove, lastFocus }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function findTarget(step) {
    return step.target ? document.querySelector('[data-tour="' + step.target + '"]') : null;
  }

  function isVisible(node) {
    if (!node || !node.getClientRects().length) return false;
    var cs = window.getComputedStyle(node);
    if (cs.visibility === 'hidden' || cs.display === 'none') return false;
    var r = node.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
  }

  function build() {
    var backdrop = el('div', 'tour-backdrop');
    var spot = el('div', 'tour-spot');
    var pop = el('div', 'tour-pop');
    pop.setAttribute('role', 'dialog');
    pop.setAttribute('aria-modal', 'true');
    pop.setAttribute('aria-labelledby', 'tour-title');
    pop.setAttribute('aria-describedby', 'tour-body');
    var close = el('button', 'btn-close tour-close');
    close.type = 'button';
    close.setAttribute('aria-label', L.close || 'Close');
    var progress = el('div', 'tour-progress small text-muted');
    var title = el('h2', 'tour-title h6 mb-1');
    title.id = 'tour-title';
    var body = el('p', 'tour-body small mb-3');
    body.id = 'tour-body';
    body.setAttribute('aria-live', 'polite');
    var dots = el('div', 'tour-dots');
    var bar = el('div', 'd-flex align-items-center gap-2');
    var skip = el('button', 'btn btn-link btn-sm text-muted p-0 me-auto', L.skip || 'Skip');
    skip.type = 'button';
    var back = el('button', 'btn btn-outline-secondary btn-sm', L.back || 'Back');
    back.type = 'button';
    var next = el('button', 'btn btn-primary btn-sm', L.next || 'Next');
    next.type = 'button';
    bar.append(skip, back, next);
    pop.append(close, progress, title, body, dots, bar);
    document.body.append(backdrop, spot, pop);

    close.addEventListener('click', function () { end(); });
    skip.addEventListener('click', function () { end(); });
    back.addEventListener('click', function () { go(state.i - 1); });
    next.addEventListener('click', function () {
      if (state.i >= state.steps.length - 1) end(); else go(state.i + 1);
    });
    return { backdrop: backdrop, spot: spot, pop: pop, progress: progress, title: title, body: body, dots: dots, back: back, next: next };
  }

  function place() {
    if (!state) return;
    var step = state.steps[state.i];
    var E = state.els;
    var target = findTarget(step);
    var pop = E.pop;
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var gap = 12;
    pop.style.left = pop.style.top = '';

    if (!target || !isVisible(target)) {
      // No element to point at: dim the page and show the card in the middle.
      E.spot.style.display = 'none';
      E.backdrop.classList.add('dim');
      pop.classList.add('centered');
      return;
    }
    E.backdrop.classList.remove('dim');
    pop.classList.remove('centered');
    var r = target.getBoundingClientRect();
    var pad = 6;
    E.spot.style.display = 'block';
    E.spot.style.left = (r.left - pad) + 'px';
    E.spot.style.top = (r.top - pad) + 'px';
    E.spot.style.width = (r.width + pad * 2) + 'px';
    E.spot.style.height = (r.height + pad * 2) + 'px';

    var pw = pop.offsetWidth;
    var ph = pop.offsetHeight;
    var side = function (s) {
      switch (s) {
        case 'right': return r.right + gap + pw <= vw - 8 ? { left: r.right + gap, top: r.top + r.height / 2 - ph / 2 } : null;
        case 'left': return r.left - gap - pw >= 8 ? { left: r.left - gap - pw, top: r.top + r.height / 2 - ph / 2 } : null;
        case 'bottom': return r.bottom + gap + ph <= vh - 8 ? { left: r.left + r.width / 2 - pw / 2, top: r.bottom + gap } : null;
        case 'top': return r.top - gap - ph >= 8 ? { left: r.left + r.width / 2 - pw / 2, top: r.top - gap - ph } : null;
      }
      return null;
    };
    var order = rtl ? ['left', 'right', 'bottom', 'top'] : ['right', 'left', 'bottom', 'top'];
    var pos = null;
    for (var k = 0; k < order.length && !pos; k++) pos = side(order[k]);
    if (!pos) {
      pop.classList.add('centered');
      return;
    }
    pop.style.left = Math.max(8, Math.min(pos.left, vw - pw - 8)) + 'px';
    pop.style.top = Math.max(8, Math.min(pos.top, vh - ph - 8)) + 'px';
  }

  function go(i) {
    if (!state || i < 0 || i >= state.steps.length) return;
    state.i = i;
    var step = state.steps[i];
    var E = state.els;
    E.progress.textContent = (L.progress || ':n / :total').replace(':n', String(i + 1)).replace(':total', String(state.steps.length));
    E.title.textContent = step.title;
    E.body.textContent = step.body;
    E.back.style.visibility = i === 0 ? 'hidden' : 'visible';
    E.next.textContent = i === state.steps.length - 1 ? (L.finish || 'Finish') : (L.next || 'Next');
    E.dots.textContent = '';
    for (var d = 0; d < state.steps.length; d++) E.dots.append(el('span', d === i ? 'on' : ''));
    var target = findTarget(step);
    if (target && isVisible(target)) target.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    place();
    window.requestAnimationFrame(place);   // again after any scrolling has settled
    E.next.focus({ preventScroll: true });
  }

  function start() {
    if (state) return;
    // Skip steps whose element isn't on this page (menu items this person can't see) or is switched off
    // for this screen size (display:none, e.g. the menu collapse button on phones). Elements that are only
    // out of view (the slide-in menu on phones) keep their step, shown in the middle of the screen.
    var steps = cfg.steps.filter(function (s) {
      if (!s.target) return true;
      var t = findTarget(s);
      return t && t.getClientRects().length > 0;
    });
    if (!steps.length) return;
    var lastFocus = document.activeElement;
    state = { steps: steps, i: 0, els: build(), lastFocus: lastFocus };
    document.documentElement.classList.add('tour-open');
    state.onKey = function (e) {
      if (e.key === 'Escape') { e.preventDefault(); end(); }
      else if (e.key === 'ArrowRight') { e.preventDefault(); rtl ? go(state.i - 1) : (state.i >= state.steps.length - 1 ? end() : go(state.i + 1)); }
      else if (e.key === 'ArrowLeft') { e.preventDefault(); rtl ? (state.i >= state.steps.length - 1 ? end() : go(state.i + 1)) : go(state.i - 1); }
      else if (e.key === 'Tab') {
        // Keep keyboard focus inside the tour card.
        var f = state.els.pop.querySelectorAll('button');
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    };
    state.onMove = function () { place(); };
    document.addEventListener('keydown', state.onKey, true);
    window.addEventListener('resize', state.onMove);
    window.addEventListener('scroll', state.onMove, true);
    go(0);
  }

  function end() {
    if (!state) return;
    var s = state;
    state = null;
    document.removeEventListener('keydown', s.onKey, true);
    window.removeEventListener('resize', s.onMove);
    window.removeEventListener('scroll', s.onMove, true);
    s.els.backdrop.remove();
    s.els.spot.remove();
    s.els.pop.remove();
    document.documentElement.classList.remove('tour-open');
    if (s.lastFocus && s.lastFocus.focus) s.lastFocus.focus({ preventScroll: true });
    // Remember that the tour was seen (finished or skipped), so it doesn't start by itself again.
    if (cfg.autostart && window.appPost) {
      cfg.autostart = false;
      window.appPost(cfg.doneUrl, {}).catch(function () {});
    }
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-tour-start]') : null;
    if (!b) return;
    e.preventDefault();
    // Let the "?" dropdown close first.
    window.setTimeout(start, 150);
  });

  var wantsTour = /[?&]tour=1(&|$)/.test(window.location.search);
  if (cfg.autostart || wantsTour) {
    window.addEventListener('load', function () { window.setTimeout(start, 400); });
  }

  window.SeyalTour = { start: start, end: end };
})();
