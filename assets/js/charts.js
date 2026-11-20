/* Draws every <canvas data-chart='{"type":..,"data":..,"options":..}'> with Chart.js. */
(function () {
  'use strict';
  if (!window.Chart) return;
  var rtl = document.documentElement.dir === 'rtl';
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.color = '#5c6670';
  Chart.defaults.plugins.legend.rtl = rtl;
  Chart.defaults.plugins.tooltip.rtl = rtl;
  Chart.defaults.maintainAspectRatio = false;

  document.querySelectorAll('canvas[data-chart]').forEach(function (cv) {
    var cfg;
    try { cfg = JSON.parse(cv.getAttribute('data-chart')); } catch (e) { return; }
    cfg.options = cfg.options || {};
    cfg.options.responsive = true;
    if (cfg.options.scales && rtl) {
      Object.keys(cfg.options.scales).forEach(function (k) {
        if (k === 'x' || k.indexOf('x') === 0) cfg.options.scales[k].reverse = true;
      });
    }
    new Chart(cv, cfg);
  });
})();
