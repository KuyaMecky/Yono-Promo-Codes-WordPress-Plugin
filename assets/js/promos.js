/* Yono Promos – widget + modal + timers (plugin-safe, idempotent) */
(function () {
  if (window.__YONO_PROMOS_LOADED__) return;
  window.__YONO_PROMOS_LOADED__ = true;

  // ---------- Utils ----------
  function $(s, r) { return (r || document).querySelector(s); }
  function $all(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }

  function copyText(text) {
    return new Promise(function (resolve, reject) {
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(resolve).catch(reject);
      } else {
        try {
          var ta = document.createElement('textarea');
          ta.value = text;
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
          resolve();
        } catch (e) { reject(e); }
      }
    });
  }

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function fmtLong(ms) {
    if (ms <= 0) return '0s';
    var s = Math.floor(ms / 1000);
    var d = Math.floor(s / 86400); s %= 86400;
    var h = Math.floor(s / 3600); s %= 3600;
    var m = Math.floor(s / 60); s %= 60;
    if (d) return d + 'd ' + h + 'h ' + m + 'm ' + s + 's';
    if (h) return h + 'h ' + m + 'm ' + s + 's';
    if (m) return m + 'm ' + s + 's';
    return s + 's';
  }
  function fmtCompact(ms) {
    if (ms <= 0) return '00:00:00';
    var s = Math.floor(ms / 1000);
    var h = Math.floor(s / 3600); s %= 3600;
    var m = Math.floor(s / 60); s %= 60;
    if (h > 99) h = 99;
    return pad(h) + ':' + pad(m) + ':' + pad(s);
  }
  function parseISO(x) { return x ? Date.parse(x) : 0; }

  // ---------- Hydrate a .yono-promos container (copy + timers) ----------
  function hydratePromos(container) {
    if (!container || container.__hydrated) return;
    container.__hydrated = true;

    var settings = {};
    try { settings = JSON.parse(container.getAttribute('data-settings') || '{}'); } catch (e) { settings = {}; }

    var showCopy = (settings.showCopy !== false);
    var showTimer = (settings.showTimer !== false);
    var format = (settings.format === 'compact') ? 'compact' : 'long';
    var formatFn = (format === 'compact') ? fmtCompact : fmtLong;

    $all('.yono-promo-card', container).forEach(function (card) {
      var codeEl = $('.promo-code', card);
      var copyBtn = $('.promo-copy', card);
      var timerEl = $('.promo-timer', card);

      // Copy button
      if (showCopy && copyBtn && codeEl) {
        copyBtn.addEventListener('click', function () {
          var txt = codeEl.textContent.trim();
          copyText(txt).then(function () {
            var old = copyBtn.textContent;
            copyBtn.textContent = 'Copied';
            copyBtn.classList.add('copied');
            setTimeout(function () {
              copyBtn.textContent = old || 'Copy';
              copyBtn.classList.remove('copied');
            }, 1200);
          });
        });
      } else if (copyBtn) {
        copyBtn.style.display = 'none';
      }

      // Timers
      if (!showTimer || !timerEl) return;

      var start = parseISO(card.getAttribute('data-start') || '');
      var end = parseISO(card.getAttribute('data-end') || '');

      function setStatus(cls) {
        card.classList.remove('status-upcoming', 'status-active', 'status-expired');
        if (cls) card.classList.add('status-' + cls);
      }

      function tick() {
        var now = Date.now();
        if (start && now < start) {
          setStatus('upcoming');
          var ms = start - now;
          timerEl.textContent = 'Starts in ' + formatFn(ms);
          timerEl.setAttribute('aria-label', timerEl.textContent);
          return;
        }
        if (end && now <= end) {
          setStatus('active');
          var ms2 = end - now;
          timerEl.textContent = 'Ends in ' + formatFn(ms2);
          timerEl.setAttribute('aria-label', timerEl.textContent);
          return;
        }
        if (end && now > end) {
          setStatus('expired');
          timerEl.textContent = 'Expired';
          timerEl.setAttribute('aria-label', 'Expired');
          return;
        }
        setStatus('active');
        timerEl.textContent = 'Active';
        timerEl.setAttribute('aria-label', 'Active');
      }

      tick();
      var int = setInterval(tick, 1000);
      var obs = new MutationObserver(function () {
        if (!document.body.contains(card)) { clearInterval(int); obs.disconnect(); }
      });
      obs.observe(document.body, { childList: true, subtree: true });
    });
  }

  // Hydrate any promos rendered directly in the page
  function hydrateAllPromosInDocument() {
    $all('.yono-promos').forEach(hydratePromos);
  }

  // ---------- Modal wiring (supports new/legacy markup) ----------
  var STATE = {
    popup: null,
    trigger: null,
    tabs: [],
    panes: [],
    inited: false,
    open: false
  };

  function resolveNodes() {
    // Triggers: supports .promo-trigger and .yono-promo-fab
    STATE.trigger = $('.promo-trigger') || $('.yono-promo-fab');
    // Popups: support #promoPopup and legacy #yonoPromoModal
    STATE.popup = $('#promoPopup') || $('#yonoPromoModal');

    if (!STATE.popup) return;

    STATE.tabs = $all('.tab-button', STATE.popup);
    STATE.panes = $all('.tab-content', STATE.popup);
  }

  function activateTab(period) {
    if (!STATE.popup) return;
    STATE.tabs.forEach(function (b) {
      var on = b.getAttribute('data-period') === period;
      b.classList.toggle('active', on);
      b.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    STATE.panes.forEach(function (p) {
      var on = p.getAttribute('data-period') === period;
      p.classList.toggle('active', on);
      if (on) {
        // hydrate promos inside the active pane
        hydratePromos($('.yono-promos', p));
      }
    });
  }

  function openPopup() {
    resolveNodes();
    if (!STATE.popup) return;

    // lazy-hydrate all panes once
    $all('.yono-promos', STATE.popup).forEach(hydratePromos);

    STATE.popup.classList.add('show');
    STATE.popup.removeAttribute('hidden');
    document.documentElement.style.overflow = 'hidden';
    if (STATE.trigger) STATE.trigger.setAttribute('aria-expanded', 'true');

    // Default to 'morning' if present, else first tab
    var def = $('[data-period="morning"].tab-button', STATE.popup) || STATE.tabs[0];
    if (def) activateTab(def.getAttribute('data-period'));

    // focus management
    var first = STATE.popup.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (first) first.focus();

    STATE.open = true;
  }

  function closePopup() {
    if (!STATE.popup) return;
    STATE.popup.classList.remove('show');
    STATE.popup.setAttribute('hidden', '');
    document.documentElement.style.overflow = '';
    if (STATE.trigger) STATE.trigger.setAttribute('aria-expanded', 'false');
    try { STATE.trigger && STATE.trigger.focus(); } catch (e) {}
    STATE.open = false;
  }

  // ---------- Global delegated listeners (single bind) ----------
  function bindDelegatedEvents() {
    if (STATE.inited) return;
    STATE.inited = true;

    document.addEventListener('click', function (e) {
      // Open from any supported trigger
      var t = e.target.closest('.promo-trigger, .yono-promo-fab');
      if (t) { e.preventDefault(); openPopup(); return; }

      // Close via button
      if (STATE.popup && e.target.closest('.promo-close')) { e.preventDefault(); closePopup(); return; }

      // Close via backdrop click
      if (STATE.popup && STATE.open && e.target === STATE.popup) { closePopup(); return; }

      // Tabs
      var tabBtn = e.target.closest('.tab-button');
      if (STATE.popup && tabBtn && tabBtn.hasAttribute('data-period')) {
        activateTab(tabBtn.getAttribute('data-period'));
      }

      // Toast feedback for copy buttons (optional)
      var toast = $('#toast-notification');
      var cbtn = e.target.closest && e.target.closest('.promo-copy');
      if (STATE.popup && cbtn && toast) {
        toast.classList.add('show');
        setTimeout(function () { toast.classList.remove('show'); }, 1200);
      }
    }, true);

    // ESC to close
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && STATE.open) closePopup();
    });
  }

  // ---------- Boot ----------
  function boot() {
    resolveNodes();
    hydrateAllPromosInDocument();
    bindDelegatedEvents();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // ---------- Debug helpers ----------
  window.YONO_PROMOS_DEBUG = function () {
    resolveNodes();
    return {
      trigger: STATE.trigger,
      popup: STATE.popup,
      open: openPopup,
      close: closePopup,
      tabs: STATE.tabs.map(function (b) { return b.getAttribute('data-period'); }),
      isOpen: STATE.open
    };
  };
})();
