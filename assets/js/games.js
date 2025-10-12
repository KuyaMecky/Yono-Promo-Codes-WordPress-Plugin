(function(){
  function qs(s,root=document){ return root.querySelector(s); }
  function qsa(s,root=document){ return Array.from(root.querySelectorAll(s)); }

  // Countdown for upcoming cards (compact + wide)
  function initCountdowns(scope){
    qsa('.yg-card.is-upcoming', scope).forEach(function(card){
      var el = qs('.yg-countdown', card);
      var iso = card.getAttribute('data-launch');
      if(!el || !iso) return;
      var target = Date.parse(iso);

      function fmt(ms){
        var s = Math.max(0, Math.floor(ms/1000));
        var d = Math.floor(s/86400);
        var h = Math.floor((s%86400)/3600);
        var m = Math.floor((s%3600)/60);
        var ss= s%60;
        var parts=[];
        if(d) parts.push(d+'d'); if(h) parts.push(h+'h'); if(m) parts.push(m+'m'); parts.push(ss+'s');
        return parts.join(' ');
      }
      function tick(){
        var now = Date.now();
        if(now >= target){ el.textContent = 'Live'; card.classList.remove('is-upcoming'); return; }
        el.textContent = fmt(target - now);
      }
      tick();
      var int = setInterval(tick, 1000);
      card.__int = int;
    });
  }

  // Filters / search / sort
  function initFilters(section){
    var grid   = qs('.yg-grid', section);
    var search = qs('.yg-search', section);
    var chips  = qsa('.yg-chip', section);
    var sort   = qs('.yg-sort-select', section);
    var activeCat = '';

    function apply(){
      var term = (search && search.value || '').trim().toLowerCase();
      var cards = qsa('.yg-card', grid);
      cards.forEach(function(c){
        var name = c.getAttribute('data-name') || '';
        var cats = c.getAttribute('data-cats') || '';
        var badges = c.getAttribute('data-badges') || '';
        var matchSearch = !term || name.includes(term) || cats.includes(term) || badges.includes(term);
        var matchCat = !activeCat || cats.includes(activeCat.toLowerCase());
        c.style.display = (matchSearch && matchCat) ? '' : 'none';
      });

      // Sort (client)
      var arr = qsa('.yg-card', grid).filter(function(x){ return x.style.display !== 'none'; });
      var cmp;
      switch ((sort && sort.value) || 'name'){
        case 'latest': /* keep as DOM order – newest first from WP query */ return;
        case 'launch':
          cmp = function(a,b){
            return (Date.parse(a.getAttribute('data-launch')||0)||0) - (Date.parse(b.getAttribute('data-launch')||0)||0);
          }; break;
        default:
          cmp = function(a,b){ return (a.getAttribute('data-name')||'').localeCompare(b.getAttribute('data-name')||''); };
      }
      arr.sort(cmp).forEach(function(el){ grid.appendChild(el); });
    }

    if (search) search.addEventListener('input', apply);
    if (sort) sort.addEventListener('change', apply);
    chips.forEach(function(ch){
      ch.addEventListener('click', function(){
        chips.forEach(function(x){ x.classList.remove('is-active'); });
        ch.classList.add('is-active');
        activeCat = ch.getAttribute('data-cat') || '';
        apply();
      });
    });
    apply();
  }

  document.addEventListener('DOMContentLoaded', function(){
    qsa('.yono-games').forEach(function(section){
      initFilters(section);
      initCountdowns(section);
    });
  });
})();
