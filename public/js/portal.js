/* Client portal extras. Confirm dialogs, copy buttons and the progress bar come from admin.js. */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  // share a review link on WhatsApp
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-wa]');
    if (b) window.open('https://wa.me/?text=' + encodeURIComponent(b.dataset.wa), '_blank', 'noopener');
  });

  // image carousels
  $$('.car').forEach((c) => {
    const t = $('.track', c), n = t.children.length, cnt = $('.count', c);
    const upd = () => { if (cnt) cnt.textContent = Math.round(t.scrollLeft / t.clientWidth) + 1 + ' / ' + n; };
    t.addEventListener('scroll', upd, { passive: true }); upd();
    $$('.cnav', c).forEach((b) => b.addEventListener('click', () => t.scrollBy({ left: (b.classList.contains('r') ? 1 : -1) * t.clientWidth, behavior: 'smooth' })));
  });

  // previews for chosen files
  $$('input[type=file][data-preview]').forEach((inp) => {
    const out = $(inp.dataset.preview);
    inp.addEventListener('change', () => {
      out.innerHTML = '';
      [...inp.files].forEach((f) => {
        const d = document.createElement('div'); d.className = 'th';
        if (f.type.startsWith('image/')) { const im = document.createElement('img'); im.src = URL.createObjectURL(f); d.appendChild(im); }
        else d.textContent = f.name;
        out.appendChild(d);
      });
    });
  });

  // select every draft for "Send for review"
  const all = $('[data-pick-all]');
  all && all.addEventListener('change', () => $$('input[name="ids[]"]').forEach((c) => { c.checked = all.checked; }));
})();

// "add" modals: <button data-modal="id"> opens <div class="mdl" id="id">
(function () {
  const open = (m) => { m.hidden = false; document.body.classList.add('mdl-open'); const f = m.querySelector('input:not([type=hidden]):not([type=file]),textarea,select'); f && setTimeout(() => f.focus(), 30); };
  const close = (m) => { m.hidden = true; if (!document.querySelector('.mdl:not([hidden])')) document.body.classList.remove('mdl-open'); };
  document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-modal]'); if (t) { e.preventDefault(); const m = document.getElementById(t.dataset.modal); m && open(m); return; }
    const c = e.target.closest('[data-close]'); if (c) { close(c.closest('.mdl')); return; }
    if (e.target.classList && e.target.classList.contains('mdl')) close(e.target);
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') document.querySelectorAll('.mdl:not([hidden])').forEach(close); });
})();
