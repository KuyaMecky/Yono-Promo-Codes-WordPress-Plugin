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
    var showCopy  = !!settings.showCopy;
    var showTimer = !!settings.showTimer;
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
})();
