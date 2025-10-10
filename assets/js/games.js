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
  });
})();
