/* Yono Games – grid UX (search, chips, sort) + countdowns */
(function () {
  if (window.__yonoGamesJS) return; window.__yonoGamesJS = true;

  function qs(el, s) { return el.querySelector(s); }
  function qsa(el, s) { return Array.from(el.querySelectorAll(s)); }

  function parseTS(iso) {
    if (!iso) return NaN;
    var t = Date.parse(iso);
    return isNaN(t) ? NaN : t;
  }

  function fmtDuration(ms) {
    if (ms <= 0) return '0d 00:00:00';
    var s = Math.floor(ms / 1000);
    var d = Math.floor(s / 86400); s -= d * 86400;
    var h = Math.floor(s / 3600); s -= h * 3600;
    var m = Math.floor(s / 60);   s -= m * 60;
    var pad = (n) => (n < 10 ? '0' + n : '' + n);
    return (d ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
  }

  function setupCountdowns(scope) {
    var cards = qsa(scope, '.yg-card.is-upcoming, .yg-card--wide.is-upcoming');
    if (!cards.length) return;

    function tick() {
      var now = Date.now();
      cards.forEach(function (card) {
        var iso = card.getAttribute('data-launch');
        var t = parseTS(iso);
        var node = qs(card, '.yg-countdown');
        if (!node) return;
        if (isNaN(t)) { node.textContent = 'TBA'; return; }
        var diff = t - now;
        if (diff <= 0) {
          node.textContent = 'Launched';
          card.classList.remove('is-upcoming');
          return;
        }
        node.textContent = fmtDuration(diff);
      });
    }
    tick();
    setInterval(tick, 1000);
  }

  function normalize(str) {
    return (str || '').toString().toLowerCase();
  }

  function matchesFilters(card, term, cat) {
    var name = card.getAttribute('data-name') || '';
    var cats = card.getAttribute('data-cats') || '';
    var badges = card.getAttribute('data-badges') || '';
    var hay = [name, cats, badges].join(' ');
    var passSearch = !term || hay.indexOf(term) !== -1;
    var passCat = !cat || ((' ' + cats + ' ').indexOf(' ' + normalize(cat).replace(/\s+/g,'-') + ' ') !== -1);
    return passSearch && passCat;
  }

  function sortCards(grid, mode) {
    var cards = qsa(grid, '.yg-card, .yg-card--wide');
    // Preserve original order for stable sorts
    cards.forEach(function (c, i) {
      if (!c.dataset.initial) c.dataset.initial = String(i);
    });

    cards.sort(function (a, b) {
      // Group upcoming first (server already does this, but keep client in sync)
      var au = a.classList.contains('is-upcoming') ? 0 : 1;
      var bu = b.classList.contains('is-upcoming') ? 0 : 1;
      if (au !== bu) return au - bu;

      if (mode === 'launch') {
        var ta = parseTS(a.getAttribute('data-launch')) || Number.MAX_SAFE_INTEGER;
        var tb = parseTS(b.getAttribute('data-launch')) || Number.MAX_SAFE_INTEGER;
        if (ta !== tb) return ta - tb;
        // tie-breaker
        return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
      }

      if (mode === 'latest') {
        // Use initial DOM order as "latest added" proxy (reverse)
        return (+b.dataset.initial) - (+a.dataset.initial);
      }

      // default: name
      var na = a.getAttribute('data-name') || '';
      var nb = b.getAttribute('data-name') || '';
      return na.localeCompare(nb);
    });

    // Re-append
    var frag = document.createDocumentFragment();
    cards.forEach(function (c) { frag.appendChild(c); });
    grid.appendChild(frag);
  }

  function initOne(container) {
    var search = qs(container, '.yg-search');
    var chips = qsa(container, '.yg-chip');
    var sortSel = qs(container, '.yg-sort-select');
    var grid = qs(container, '.yg-grid');
    if (!grid) return;

    // current filters
    var state = {
      term: '',
      cat: '',
      sort: sortSel ? sortSel.value : 'name'
    };

    // Filter function
    function applyFilters() {
      var cards = qsa(grid, '.yg-card, .yg-card--wide');
      cards.forEach(function (card) {
        var show = matchesFilters(card, state.term, state.cat);
        card.style.display = show ? '' : 'none';
      });
    }

    // Search
    if (search) {
      search.addEventListener('input', function () {
        state.term = normalize(search.value);
        applyFilters();
      });
    }

    // Chips
    if (chips.length) {
      chips.forEach(function (btn) {
        btn.addEventListener('click', function () {
          chips.forEach(function (b) { b.classList.remove('is-active'); });
          btn.classList.add('is-active');
          state.cat = (btn.getAttribute('data-cat') || '').trim();
          applyFilters();
        });
      });
    }

    // Sort (client side resort)
    if (sortSel) {
      sortSel.addEventListener('change', function () {
        state.sort = sortSel.value;
        sortCards(grid, state.sort);
        // Re-apply visibility after reorder
        applyFilters();
      });
      // run once using initial value
      sortCards(grid, state.sort);
    }

    // Initial filter & countdowns
    applyFilters();
    setupCountdowns(container);
  }

  document.addEventListener('DOMContentLoaded', function () {
    qsa(document, '.yono-games').forEach(initOne);
  });
})();
