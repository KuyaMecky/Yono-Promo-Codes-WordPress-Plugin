<<<<<<< HEAD
/* Yono Promos – widget + modal + timers (plugin-safe, idempotent) */
(function () {
  if (window.__YONO_PROMOS_LOADED__) return;
  window.__YONO_PROMOS_LOADED__ = true;

  // ---------- Utils ----------
  function $(s, r) { return (r || document).querySelector(s); }
  function $all(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }

=======
/* =========================================================
   PROMOS: copy + countdown (works in page and in modal)
   ========================================================= */
/* =========================================================
   PROMOS: copy + countdown (works in page and in modal)
   ========================================================= */
(function(){
  // Clipboard helper
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
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
<<<<<<< HEAD
    try { settings = JSON.parse(container.getAttribute('data-settings') || '{}'); } catch (e) { settings = {}; }

    var showCopy = (settings.showCopy !== false);
    var showTimer = (settings.showTimer !== false);
    var format = (settings.format === 'compact') ? 'compact' : 'long';
    var formatFn = (format === 'compact') ? fmtCompact : fmtLong;
=======
    try { settings = JSON.parse(container.getAttribute('data-settings') || '{}'); } catch(e){}
    var showCopy  = settings.showCopy !== false;
    var showTimer = settings.showTimer !== false;
    var format    = (settings.format === 'compact') ? 'compact' : 'long';
    var formatter = (format === 'compact') ? fmtCompact : fmtLong;
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e

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

<<<<<<< HEAD
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
=======
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
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
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

(function(){
  const popup   = document.getElementById('promoPopup');
  const trigger = document.querySelector('.promo-trigger');
  if (!popup || !trigger) return;

  const closeBtn = popup.querySelector('.promo-close');
  const tabButtons = Array.from(popup.querySelectorAll('.tab-button'));
  const panes = Array.from(popup.querySelectorAll('.tab-content'));
  const hiddenWrap = popup.querySelector('.yono-promos'); // contains all cards (hidden)
  const toast = document.getElementById('toast-notification');

  // Move cards into each pane by data-period
  function distributeCards(){
    if (!hiddenWrap) return;
    const cards = Array.from(hiddenWrap.querySelectorAll('.yono-promo-card'));
    panes.forEach(p => p.querySelector('.promo-list').innerHTML = '');
    cards.forEach(card=>{
      const period = (card.getAttribute('data-period') || '').toLowerCase();
      const pane = panes.find(p => p.getAttribute('data-period') === period);
      if (pane) pane.querySelector('.promo-list').appendChild(card);
    });
    // hydrate countdown & copy on visible lists (reuse existing function if available)
    if (typeof NodeList !== 'undefined') {
      // use the hydrate() we defined earlier by calling it on each pane root
      try {
        // hydrate() is inside a closure in your file; expose minimal fallback:
        // we'll re-run core binding for copy buttons + timers here as a safety no-op
      } catch (e) {}
    }
    // Hook copy buttons for toast feedback (non-invasive)
    popup.querySelectorAll('.promo-copy').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        if (!toast) return;
        toast.classList.add('show');
        setTimeout(()=> toast.classList.remove('show'), 1200);
      }, { once:false });
    });
  }

  function open(){
    distributeCards();
    popup.hidden = false;
    popup.classList.add('show');
    document.documentElement.style.overflow = 'hidden';
    trigger.setAttribute('aria-expanded','true');
  }
  function close(){
    popup.hidden = true;
    popup.classList.remove('show');
    document.documentElement.style.overflow = '';
    trigger.setAttribute('aria-expanded','false');
  }

  // Tabs
  function activate(period){
    tabButtons.forEach(b=>{
      const on = b.getAttribute('data-period') === period;
      b.classList.toggle('active', on);
      b.setAttribute('aria-selected', on ? 'true':'false');
    });
    panes.forEach(p=>{
      const on = p.getAttribute('data-period') === period;
      p.classList.toggle('active', on);
    });
  }

  // Events
  trigger.addEventListener('click', open);
  closeBtn.addEventListener('click', close);
  popup.addEventListener('click', e=>{ if (e.target === popup) close(); });
  document.addEventListener('keydown', e=>{ if (!popup.hidden && e.key === 'Escape') close(); });

  tabButtons.forEach(b => b.addEventListener('click', ()=> activate(b.getAttribute('data-period')) ));

  // Default tab
  activate('morning');
})();


// === Promo widget open/close glue ===
document.addEventListener('DOMContentLoaded', function () {
  var trigger = document.querySelector('.promo-trigger');
  var popup   = document.getElementById('promoPopup');
  if (!trigger || !popup) return;

  var closeBtn = popup.querySelector('.promo-close');

  function openPopup() {
    // remove the hidden attribute so CSS display kicks in
    popup.hidden = false;
    // if you use .show { display:flex } also add the class
    popup.classList.add('show');
    document.documentElement.style.overflow = 'hidden';
    trigger.setAttribute('aria-expanded', 'true');
  }

  function closePopup() {
    popup.classList.remove('show');
    popup.hidden = true; // keep this so screen readers know it’s hidden
    document.documentElement.style.overflow = '';
    trigger.setAttribute('aria-expanded', 'false');
  }

  trigger.addEventListener('click', openPopup);
  closeBtn && closeBtn.addEventListener('click', closePopup);

  // Close when clicking the dark backdrop (outside the panel)
  popup.addEventListener('click', function (e) {
    if (e.target === popup) closePopup();
  });

  // ESC to close
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !popup.hidden) closePopup();
  });
});
(function(){
  // util copy
  function copyText(text){
    return new Promise(function(resolve,reject){
      if(navigator.clipboard && window.isSecureContext){
        navigator.clipboard.writeText(text).then(resolve).catch(reject);
      }else{
        try{
          const ta=document.createElement('textarea');
          ta.value=text; document.body.appendChild(ta); ta.select();
          document.execCommand('copy'); document.body.removeChild(ta);
          resolve();
        }catch(e){ reject(e); }
      }
    });
  }

  // formatters
  function pad(n){return (n<10?'0':'')+n;}
  function fmtLong(ms){
    if(ms<=0) return '0s';
    var s=Math.floor(ms/1000);
    var d=Math.floor(s/86400); s%=86400;
    var h=Math.floor(s/3600);  s%=3600;
    var m=Math.floor(s/60);    s%=60;
    if(d>0) return d+'d '+h+'h '+m+'m '+s+'s';
    if(h>0) return h+'h '+m+'m '+s+'s';
    if(m>0) return m+'m '+s+'s';
    return s+'s';
  }
  function fmtCompact(ms){
    if(ms<=0) return '00:00:00';
    var s=Math.floor(ms/1000);
    var h=Math.floor(s/3600); s%=3600;
    var m=Math.floor(s/60);   s%=60;
    if(h>99) h=99;
    return pad(h)+':'+pad(m)+':'+pad(s);
  }
  function parseISO(x){ return x ? Date.parse(x) : 0; }

  function hydrate(container){
    var settings={};
    try{ settings=JSON.parse(container.getAttribute('data-settings')||'{}'); }catch(e){}
    var showCopy=!!settings.showCopy;
    var showTimer=!!settings.showTimer;
    var format=(settings.format==='compact')?'compact':'long';
    var formatter=(format==='compact')?fmtCompact:fmtLong;

    container.querySelectorAll('.yono-promo-card').forEach(function(card){
      var codeEl=card.querySelector('.promo-code');
      var copyBtn=card.querySelector('.promo-copy');
      var timerEl=card.querySelector('.promo-timer');

      if(showCopy && copyBtn && codeEl){
        copyBtn.addEventListener('click', function(){
          copyText(codeEl.textContent.trim()).then(function(){
            var old=copyBtn.textContent;
            copyBtn.textContent='Copied';
            copyBtn.classList.add('copied');
            setTimeout(function(){
              copyBtn.textContent=old||'Copy';
              copyBtn.classList.remove('copied');
            },1000);
          });
        });
      }else if(copyBtn){ copyBtn.style.display='none'; }

      if(!showTimer || !timerEl) return;

      var start=parseISO(card.getAttribute('data-start')||'');
      var end  =parseISO(card.getAttribute('data-end')||'');

      function setStatus(cls){
        card.classList.remove('status-upcoming','status-active','status-expired');
        if(cls) card.classList.add('status-'+cls);
      }

      function tick(){
        var now=Date.now();
        if(start && now<start){
          setStatus('upcoming');
          timerEl.textContent=(format==='compact')?('Starts in '+fmtCompact(start-now)):('Starts in '+fmtLong(start-now));
          return;
        }
        if(end && now<=end){
          setStatus('active');
          timerEl.textContent=(format==='compact')?('Ends in '+fmtCompact(end-now)):('Ends in '+fmtLong(end-now));
          return;
        }
        if(end && now>end){
          setStatus('expired'); timerEl.textContent='Expired'; return;
        }
        setStatus('active'); timerEl.textContent='Active';
      }
      tick();
      var int=setInterval(tick,1000);
      var obs=new MutationObserver(function(){
        if(!document.body.contains(card)){ clearInterval(int); obs.disconnect(); }
      });
      obs.observe(document.body,{childList:true,subtree:true});
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

