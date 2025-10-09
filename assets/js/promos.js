(function(){
  // Clipboard helper (fallback safe)
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

  // Simple countdown formatter
  function fmt(ms){
    if (ms <= 0) return '0s';
    var s = Math.floor(ms/1000);
    var d = Math.floor(s/86400); s%=86400;
    var h = Math.floor(s/3600); s%=3600;
    var m = Math.floor(s/60); s%=60;
    if (d>0) return d+'d '+h+'h '+m+'m '+s+'s';
    if (h>0) return h+'h '+m+'m '+s+'s';
    if (m>0) return m+'m '+s+'s';
    return s+'s';
  }

  function parseISO(x){ return x ? Date.parse(x) : 0; }

  function hydrate(container){
    var settings = {};
    try { settings = JSON.parse(container.getAttribute('data-settings') || '{}'); } catch(e){}
    var showCopy = !!settings.showCopy;
    var showTimer = !!settings.showTimer;

    var nowBase = parseISO(settings.now) || Date.now();

    var cards = container.querySelectorAll('.yono-promo-card');
    cards.forEach(function(card){
      var codeEl = card.querySelector('.promo-code');
      var copyBtn = card.querySelector('.promo-copy');
      var timerEl = card.querySelector('.promo-timer');

      if (showCopy && copyBtn && codeEl) {
        copyBtn.addEventListener('click', function(){
          copyText(codeEl.textContent.trim()).then(function(){
            copyBtn.textContent = 'Copied';
            copyBtn.classList.add('copied');
            setTimeout(function(){
              copyBtn.textContent = 'Copy';
              copyBtn.classList.remove('copied');
            }, 1200);
          });
        });
      } else if (copyBtn) {
        copyBtn.style.display = 'none';
      }

      if (!showTimer || !timerEl) return;

      var startISO = card.getAttribute('data-start') || '';
      var endISO   = card.getAttribute('data-end') || '';

      var start = parseISO(startISO); // UTC
      var end   = parseISO(endISO);

      function tick(){
        var now = Date.now();
        if (start && now < start) {
          timerEl.textContent = 'Starts in ' + fmt(start - now);
          return;
        }
        if (end && now <= end) {
          timerEl.textContent = 'Ends in ' + fmt(end - now);
          return;
        }
        if (end && now > end) {
          timerEl.textContent = 'Expired';
          return;
        }
        // No end: active
        timerEl.textContent = 'Active';
      }
      tick();
      setInterval(tick, 1000);
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.yono-promos').forEach(hydrate);
  });
})();
