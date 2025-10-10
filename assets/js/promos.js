/* =========================================================
   PROMOS: copy + countdown (works in page and in modal)
   ========================================================= */
(function(){
  // Clipboard helper
  function copyText(text) {
    return new Promise(function(resolve, reject){
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(resolve).catch(reject);
      } else {
        try {
          const ta = document.createElement('textarea');
          ta.value = text; document.body.appendChild(ta); ta.select();
          document.execCommand('copy'); document.body.removeChild(ta);
          resolve();
        } catch(e){ reject(e); }
      }
    });
  }

  // Formatters
  function fmtLong(ms){
    if (ms <= 0) return '0s';
    var s = Math.floor(ms/1000);
    var d = Math.floor(s/86400); s%=86400;
    var h = Math.floor(s/3600);  s%=3600;
    var m = Math.floor(s/60);    s%=60;
    if (d>0) return d+'d '+h+'h '+m+'m '+s+'s';
    if (h>0) return h+'h '+m+'m '+s+'s';
    if (m>0) return m+'m '+s+'s';
    return s+'s';
  }
  function pad(n){ return (n<10?'0':'')+n; }
  function fmtCompact(ms){
    if (ms <= 0) return '00:00:00';
    var s = Math.floor(ms/1000);
    var h = Math.floor(s/3600);  s%=3600;
    var m = Math.floor(s/60);    s%=60;
    if (h > 99) h = 99; // cap
    return pad(h)+':'+pad(m)+':'+pad(s);
  }
  function parseISO(x){ return x ? Date.parse(x) : 0; }

  function hydrate(container){
    var settings = {};
    try { settings = JSON.parse(container.getAttribute('data-settings') || '{}'); } catch(e){}
    var showCopy  = settings.showCopy !== false;
    var showTimer = settings.showTimer !== false;
    var format    = (settings.format === 'compact') ? 'compact' : 'long';
    var formatter = (format === 'compact') ? fmtCompact : fmtLong;

    container.querySelectorAll('.yono-promo-card').forEach(function(card){
      var codeEl  = card.querySelector('.promo-code');
      var copyBtn = card.querySelector('.promo-copy');
      var timerEl = card.querySelector('.promo-timer');

      // Copy
      if (showCopy && copyBtn && codeEl) {
        copyBtn.addEventListener('click', function(){
          var txt = codeEl.textContent.trim();
          copyText(txt).then(function(){
            var old = copyBtn.textContent;
            copyBtn.textContent = 'Copied';
            copyBtn.classList.add('copied');
            setTimeout(function(){
              copyBtn.textContent = old || 'Copy';
              copyBtn.classList.remove('copied');
            }, 1200);
          });
        });
      } else if (copyBtn) {
        copyBtn.style.display = 'none';
      }

      if (!showTimer || !timerEl) return;

      var startISO = card.getAttribute('data-start') || '';
      var endISO   = card.getAttribute('data-end')   || '';
      var start    = parseISO(startISO);
      var end      = parseISO(endISO);

      function setStatus(cls) {
        card.classList.remove('status-upcoming','status-active','status-expired');
        if (cls) card.classList.add('status-'+cls);
      }

      function tick(){
        var now = Date.now();

        if (start && now < start) {
          setStatus('upcoming');
          var ms = start - now;
          timerEl.textContent = (format === 'compact')
            ? ('Starts in ' + fmtCompact(ms))
            : ('Starts in ' + fmtLong(ms));
          timerEl.setAttribute('aria-label', timerEl.textContent);
          return;
        }
        if (end && now <= end) {
          setStatus('active');
          var ms2 = end - now;
          timerEl.textContent = (format === 'compact')
            ? ('Ends in ' + fmtCompact(ms2))
            : ('Ends in ' + fmtLong(ms2));
          timerEl.setAttribute('aria-label', timerEl.textContent);
          return;
        }
        if (end && now > end) {
          setStatus('expired');
          timerEl.textContent = 'Expired';
          timerEl.setAttribute('aria-label', 'Expired');
          return;
        }
        // No end => active
        setStatus('active');
        timerEl.textContent = 'Active';
        timerEl.setAttribute('aria-label', 'Active');
      }

      tick();
      // Smooth 1s ticking
      var int = setInterval(tick, 1000);
      // If this node is removed, stop the timer
      var obs = new MutationObserver(function(){
        if (!document.body.contains(card)) { clearInterval(int); obs.disconnect(); }
      });
      obs.observe(document.body, { childList: true, subtree: true });
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.yono-promos').forEach(hydrate);
  });

  /* ---------------------------------
     PROMO MODAL + TABS (period filter)
     --------------------------------- */
  function initPromoModal(){
    var trigger = document.querySelector('.yono-float-trigger');
    var modal   = document.getElementById('yonoPromoModal');
    if (!trigger || !modal) return;

    var closeEls = modal.querySelectorAll('[data-close-modal]');
    var prevActive = null;

    function trapFocus(e){
      if (e.key !== 'Tab') return;
      var focusables = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      focusables = Array.prototype.slice.call(focusables).filter(function(el){ return !el.hasAttribute('disabled') && el.offsetParent !== null; });
      if (!focusables.length) return;
      var first = focusables[0], last = focusables[focusables.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }

    function open(){
      prevActive = document.activeElement;
      modal.hidden = false;
      document.documentElement.style.overflow = 'hidden';
      if (!modal.__inited){
        var promosContainer = modal.querySelector('.yono-promos');
        if (promosContainer) hydrate(promosContainer);
        initTabs(modal);
        modal.__inited = true;
      }
      // focus first interactive
      var firstBtn = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      if (firstBtn) firstBtn.focus();
      modal.addEventListener('keydown', trapFocus);
    }

    function close(){
      modal.hidden = true;
      document.documentElement.style.overflow = '';
      modal.removeEventListener('keydown', trapFocus);
      if (prevActive && typeof prevActive.focus === 'function') prevActive.focus();
    }

    trigger.addEventListener('click', open);
    closeEls.forEach(function(el){ el.addEventListener('click', close); });
    modal.addEventListener('click', function(e){ if (e.target.classList.contains('yono-modal__backdrop')) close(); });
    document.addEventListener('keydown', function(e){ if (!modal.hidden && e.key === 'Escape') close(); });
  }

  function initTabs(scope){
    var tabs = Array.prototype.slice.call(scope.querySelectorAll('.yono-tab'));
    var list = scope.querySelector('.yono-promos');
    if (!tabs.length || !list) return;
    var cards = Array.prototype.slice.call(list.querySelectorAll('.yono-promo-card'));

    function activate(period){
      tabs.forEach(function(t){
        var on = t.getAttribute('data-period') === period;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      cards.forEach(function(c){
        var p = c.getAttribute('data-period') || '';
        c.style.display = (p === period) ? '' : 'none';
      });
    }

    tabs.forEach(function(t){ t.addEventListener('click', function(){ activate(t.getAttribute('data-period')); }); });
    // Default to first tab
    activate(tabs[0].getAttribute('data-period'));
  }

  document.addEventListener('DOMContentLoaded', initPromoModal);
})();

/* =========================================================
   GAMES: countdown + search/filter/sort
   ========================================================= */
(function(){
  function qs(s,root=document){ return root.querySelector(s); }
  function qsa(s,root=document){ return Array.from(root.querySelectorAll(s)); }

  // Countdown for upcoming cards
  function initCountdowns(scope){
    qsa('.yg-card.is-upcoming', scope).forEach(card=>{
      const el = qs('.yg-countdown', card);
      const iso = card.getAttribute('data-launch');
      if (!el || !iso) return;
      const target = Date.parse(iso);

      function fmt(ms){
        const s = Math.max(0, Math.floor(ms/1000));
        const d = Math.floor(s/86400);
        const h = Math.floor((s%86400)/3600);
        const m = Math.floor((s%3600)/60);
        const ss = s%60;
        let parts=[];
        if (d) parts.push(d+'d'); if (h) parts.push(h+'h'); if (m) parts.push(m+'m'); parts.push(ss+'s');
        return parts.join(' ');
      }
      function tick(){
        const now = Date.now();
        if (now >= target){ el.textContent = 'Live'; card.classList.remove('is-upcoming'); return; }
        el.textContent = fmt(target - now);
      }
      tick();
      const int = setInterval(tick, 1000);
      card.__int = int;
    });
  }

  // Filters & search
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

      // Sort (client)
      const arr = qsa('.yg-card', grid);
      let cmp;
      switch ((sort?.value || 'name')){
        case 'latest':
          // assume DOM order is latest first already – do nothing
          return;
        case 'launch':
          cmp = (a,b)=> (Date.parse(a.getAttribute('data-launch')||0) || 0) - (Date.parse(b.getAttribute('data-launch')||0) || 0);
          break;
        default:
          cmp = (a,b)=> (a.getAttribute('data-name')||'').localeCompare(b.getAttribute('data-name')||'');
      }
      arr.sort(cmp).forEach(el=>grid.appendChild(el));
    }

    if (search) search.addEventListener('input', apply);
    if (sort) sort.addEventListener('change', apply);
    chips.forEach(ch=>{
      ch.addEventListener('click', ()=>{
        chips.forEach(x=>x.classList.remove('is-active'));
        ch.classList.add('is-active');
        activeCat = ch.getAttribute('data-cat') || '';
        apply();
      })
    });

    apply();
  }

  document.addEventListener('DOMContentLoaded', function(){
    qsa('.yono-games').forEach(section=>{
      initFilters(section);
      initCountdowns(section);
    });
  });
})();
