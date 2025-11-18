<<<<<<< HEAD
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
=======
(function(){
  function qs(s,root=document){ return root.querySelector(s); }
  function qsa(s,root=document){ return Array.from(root.querySelectorAll(s)); }

  function initCountdowns(scope){
    qsa('.yg-card.is-upcoming', scope).forEach(card=>{
      const el = qs('.yg-countdown', card);
      const iso = card.getAttribute('data-launch');
      if (!el || !iso) return;
      const target = Date.parse(iso);

      function fmt(ms){
        const s = Math.max(0, Math.floor(ms/1000));
        const d = Math.floor(s/86400), h=Math.floor((s%86400)/3600), m=Math.floor((s%3600)/60), ss=s%60;
        const parts = []; if (d) parts.push(d+'d'); if (h) parts.push(h+'h'); if (m) parts.push(m+'m'); parts.push(ss+'s');
        return parts.join(' ');
      }
      function tick(){
        const now = Date.now();
        if (now >= target){ el.textContent='Live'; card.classList.remove('is-upcoming'); return; }
        el.textContent = fmt(target - now);
      }
      tick(); card.__int = setInterval(tick, 1000);
    });
  }

  function initFilters(section){
    const grid   = qs('.yg-grid', section);
    const search = qs('.yg-search', section);
    const chips  = qsa('.yg-chip', section);
    const sort   = qs('.yg-sort-select', section);
    let activeCat = '';

    function apply(){
      const term = (search?.value || '').trim().toLowerCase();
      const cards = qsa('.yg-card', grid);
      cards.forEach(c=>{
        const name = c.getAttribute('data-name') || '';
        const cats = c.getAttribute('data-cats') || '';
        const badges = c.getAttribute('data-badges') || '';
        const matchSearch = !term || name.includes(term) || cats.includes(term) || badges.includes(term);
        const matchCat = !activeCat || cats.includes(activeCat.toLowerCase());
        c.style.display = (matchSearch && matchCat) ? '' : 'none';
      });

      const arr = qsa('.yg-card', grid);
      let cmp;
      switch ((sort?.value || 'name')){
        case 'launch':
          cmp = (a,b)=> (Date.parse(a.getAttribute('data-launch')||0) || 0) - (Date.parse(b.getAttribute('data-launch')||0) || 0);
          break;
        case 'latest':
          // DOM insertion order already newest first when using server-side 'date DESC'
          return;
        default:
          cmp = (a,b)=> (a.getAttribute('data-name')||'').localeCompare(b.getAttribute('data-name')||'');
      }
      arr.sort(cmp).forEach(el=>grid.appendChild(el));
    }

    if (search) search.addEventListener('input', apply);
    if (sort)   sort.addEventListener('change', apply);
    chips.forEach(ch=>{
      ch.addEventListener('click', ()=>{
        chips.forEach(x=>x.classList.remove('is-active'));
        ch.classList.add('is-active');
        activeCat = ch.getAttribute('data-cat') || '';
        apply();
      });
    });

    apply();
  }

  document.addEventListener('DOMContentLoaded', function(){
    qsa('.yono-games').forEach(section=>{
      initFilters(section);
      initCountdowns(section);
    });
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
  });
})();
