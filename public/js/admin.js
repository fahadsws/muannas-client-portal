/* Server-rendered admin: only light behaviour lives here (no API calls). */
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  // top progress bar on any navigation / form submit
  const bar = $('#nprog');
  const start = () => { if (!bar) return; bar.classList.add('on'); bar.style.width = '35%'; setTimeout(() => (bar.style.width = '80%'), 250); };
  document.addEventListener('click', (e) => { const a = e.target.closest('a[href]'); if (a && !a.target && !a.hasAttribute('download') && a.getAttribute('href')[0] !== '#' && !e.ctrlKey && !e.metaKey && !a.dataset.noload) start(); });
  document.addEventListener('submit', (e) => { if (!e.defaultPrevented) start(); });
  window.addEventListener('pageshow', () => { if (bar) { bar.classList.remove('on'); bar.style.width = '0'; } });

  // SweetAlert2 confirmations: <form data-confirm="text" data-confirm-title data-confirm-btn data-confirm-danger>
  document.addEventListener('submit', (e) => {
    const f = e.target; const text = f.dataset.confirm;
    if (!text || f.dataset.confirmed) return;
    e.preventDefault(); e.stopImmediatePropagation();
    if (bar) { bar.classList.remove('on'); bar.style.width = '0'; }
    const danger = f.dataset.confirmDanger !== undefined;
    const go = () => { f.dataset.confirmed = '1'; start(); f.submit(); };
    if (!window.Swal) { if (confirm(text)) go(); return; }
    Swal.fire({
      title: f.dataset.confirmTitle || 'Are you sure?', text, icon: danger ? 'warning' : 'question',
      showCancelButton: true, confirmButtonText: f.dataset.confirmBtn || 'Yes, continue', cancelButtonText: 'Cancel',
      reverseButtons: true, focusCancel: danger, buttonsStyling: false,
      customClass: { popup: 'swal-flat', confirmButton: danger ? 'swal-btn swal-danger' : 'swal-btn swal-primary', cancelButton: 'swal-btn swal-cancel', title: 'swal-title', htmlContainer: 'swal-text' },
    }).then((r) => { if (r.isConfirmed) go(); });
  }, true);

  // spans that act like links (links nested inside a clickable card)
  document.addEventListener('click', (e) => {
    const l = e.target.closest('[data-href]'); if (!l) return;
    e.preventDefault(); e.stopPropagation(); start(); location.href = l.dataset.href;
  }, true);

  // copy credentials
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-copy]'); if (!b) return;
    navigator.clipboard && navigator.clipboard.writeText(b.dataset.copy); b.textContent = 'Copied ✓';
  });

  // flash toast fades out
  const flash = $('#flash'); if (flash) setTimeout(() => flash.classList.remove('on'), 2800);

  // date range → hidden from/to inputs inside the same form
  $$('input[data-range]').forEach((el) => {
    const form = el.form; if (!form) return;
    const from = form.querySelector('[name=from]'), to = form.querySelector('[name=to]');
    const apply = () => { const fp = el._flatpickr; if (!fp) return; const a = fp.selectedDates[0], b = fp.selectedDates[1] || a; from.value = a ? fp.formatDate(a, 'Y-m-d') : ''; to.value = a ? fp.formatDate(b, 'Y-m-d') : ''; };
    const wait = setInterval(() => { // ui.js builds the picker asynchronously via MutationObserver
      if (!el._flatpickr) return; clearInterval(wait);
      if (from.value) el._flatpickr.setDate(to.value && to.value !== from.value ? [from.value, to.value] : [from.value], false);
      el._flatpickr.config.onChange.push(apply); el._flatpickr.config.onClose.push(apply);
    }, 50);
    form.addEventListener('submit', apply);
  });

  // reports: rebuild preview + export links when any control changes
  const rf = $('#reportform');
  if (rf) {
    const qs = () => {
      const fd = new FormData(rf); const p = new URLSearchParams();
      fd.forEach((v, k) => { if (k === 'sections') return; if (v !== '') p.set(k, v); });
      p.set('sections', fd.getAll('sections').join(','));
      if (p.get('type') !== 'agency') p.delete('agency');
      return p.toString();
    };
    const frame = $('#rframe'), pl = $('#rpl');
    const toggleFields = () => {
      const t = rf.querySelector('[name=type]:checked').value;
      $$('[data-for]').forEach((n) => { n.style.display = n.dataset.for.split(' ').includes(t) ? '' : 'none'; });
    };
    const refresh = () => {
      const q = qs(); if (pl) pl.classList.remove('hide'); frame.src = '/admin/report/preview?' + q;
      $$('[data-fmt]').forEach((a) => (a.href = `/admin/report/export/${a.dataset.fmt}?` + q)); toggleFields();
    };
    let t; const later = () => { clearTimeout(t); t = setTimeout(refresh, 200); };
    rf.addEventListener('change', later); toggleFields(); refresh();
    frame.addEventListener('load', () => pl && pl.classList.add('hide'));
  }
})();
