/* Shared UI enhancers: Select2 on every <select>, flatpickr date pickers and range pickers.
   Both are picked up automatically when new HTML is rendered, so pages just output plain markup. */
(() => {
  const $j = window.jQuery;
  if (!$j || !$j.fn.select2) return;

  function enhance(root) {
    $j(root).find('select:not(.nos2):not(.swal2-select):not(.flatpickr-monthDropdown-months)').addBack('select:not(.nos2):not(.swal2-select):not(.flatpickr-monthDropdown-months)').each(function () {
      if (this.dataset.s2) return;
      this.dataset.s2 = '1';
      const el = this, $s = $j(el), modal = $s.closest('.modal');
      $s.select2({
        width: el.dataset.w || '100%',
        minimumResultsForSearch: +(el.dataset.search || 8),
        dropdownParent: modal.length ? modal : $j(document.body),
        placeholder: el.dataset.ph || undefined,
        allowClear: !!el.dataset.clear,
      });
      $s.on('select2:select select2:clear', () => el.dispatchEvent(new Event('change', {bubbles: true})));
    });

    if (window.flatpickr && root.querySelectorAll) {
      // every plain date field becomes a calendar picker that shows "23 Oct 2026" and still submits Y-m-d
      const dates = root.matches && root.matches('input[type=date], input[type=datetime-local]') ? [root] : root.querySelectorAll('input[type=date], input[type=datetime-local]');
      dates.forEach((el) => {
        if (el._flatpickr) return;
        const time = el.type === 'datetime-local';
        el.type = 'text';
        window.flatpickr(el, {
          dateFormat: time ? 'Y-m-d\\TH:i' : 'Y-m-d', enableTime: time, time_24hr: false,
          altInput: true, altFormat: time ? 'd M Y, h:i K' : 'd M Y', altInputClass: 'form-control',
          allowInput: false, disableMobile: true,
          onReady: (_, __, fp) => {
            fp.altInput.placeholder = el.getAttribute('placeholder') || 'Select a date';
            // Preserve dates rendered by edit forms when the original input is
            // converted to a text field and hidden by Flatpickr.
            if (el.value) fp.setDate(el.value, false);
          },
          onClose: () => el.dispatchEvent(new Event('change', {bubbles: true})),
        });
      });

      const inputs = root.matches && root.matches('input[data-range]') ? [root] : root.querySelectorAll('input[data-range]');
      inputs.forEach((el) => {
        if (el._flatpickr) return;
        window.flatpickr(el, {
          mode: 'range', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', altInputClass: 'range-input',
          rangeSeparator: '  →  ', allowInput: false, showMonths: window.innerWidth > 700 ? 2 : 1, disableMobile: true,
          onClose: () => el.dispatchEvent(new Event('change', {bubbles: true})),
        });
      });
    }
  }

  /** Programmatic helpers other scripts can call after changing a value from code. */
  window.UI = {
    setSelect(el, value) { if (!el || el.value === String(value)) return; el.value = value; $j(el).trigger('change.select2'); },
    setRange(el, from, to) {
      if (!el || !el._flatpickr) return;
      if (from) el._flatpickr.setDate(to && to !== from ? [from, to] : [from], false); else el._flatpickr.clear(false);
    },
    getRange(el) {
      const fp = el && el._flatpickr; if (!fp || !fp.selectedDates.length) return {from: '', to: ''};
      const a = fp.selectedDates[0], b = fp.selectedDates[1] || a;
      return {from: fp.formatDate(a, 'Y-m-d'), to: fp.formatDate(b, 'Y-m-d')};
    },
  };

  new MutationObserver((muts) => muts.forEach((m) => m.addedNodes.forEach((n) => { if (n.nodeType === 1 && !n.classList.contains('select2-container') && !n.classList.contains('select2-dropdown')) enhance(n); })))
    .observe(document.body, {childList: true, subtree: true});
  enhance(document.body);
})();

/** Image input: drag & drop, live preview, remove. Markup: resources/views/components/image-input.blade.php */
(() => {
  const setup = (box) => {
    if (box._imgin) return; box._imgin = true;
    const input = box.querySelector('input[type=file]'), prev = box.querySelector('.imgin-prev'), list = box.querySelector('.imgin-list');
    const clear = box.querySelector('.imgin-clear'), rm = box.querySelector('[data-imgin-remove]'), multi = input.multiple;
    const orig = box.dataset.orig || '';
    const mkImg = (src) => { const i = document.createElement('img'); i.src = src; i.alt = ''; return i; };
    const paint = () => {
      const files = [...input.files];
      if (multi) {
        list.innerHTML = '';
        files.forEach((f) => { const d = document.createElement('div'); d.className = 'imgin-th'; d.appendChild(mkImg(URL.createObjectURL(f))); list.appendChild(d); });
        box.classList.toggle('has', files.length > 0); clear.hidden = !files.length; return;
      }
      prev.querySelector('img')?.remove();
      const f = files[0];
      if (f) { prev.prepend(mkImg(URL.createObjectURL(f))); if (rm) rm.value = '0'; }
      else if (orig && rm && rm.value !== '1') prev.prepend(mkImg(orig));
      box.classList.toggle('has', !!f || (!!orig && !(rm && rm.value === '1')));
      clear.hidden = !box.classList.contains('has');
    };
    input.addEventListener('change', paint);
    clear.addEventListener('click', () => { input.value = ''; if (rm && !multi) rm.value = '1'; paint(); });
    ['dragenter', 'dragover'].forEach((ev) => box.addEventListener(ev, (e) => { e.preventDefault(); box.classList.add('drag'); }));
    ['dragleave', 'drop'].forEach((ev) => box.addEventListener(ev, (e) => { e.preventDefault(); box.classList.remove('drag'); }));
    box.addEventListener('drop', (e) => {
      const dropped = [...e.dataTransfer.files].filter((f) => f.type.startsWith('image/')); if (!dropped.length) return;
      const dt = new DataTransfer(); (multi ? dropped : dropped.slice(0, 1)).forEach((f) => dt.items.add(f));
      input.files = dt.files; paint();
    });
    paint();
  };
  const scan = (root) => (root.matches && root.matches('[data-imgin]') ? [root] : [...root.querySelectorAll('[data-imgin]')]).forEach(setup);
  new MutationObserver((m) => m.forEach((r) => r.addedNodes.forEach((n) => n.nodeType === 1 && scan(n)))).observe(document.body, {childList: true, subtree: true});
  scan(document.body);
})();

/** File input: "N File(s) Attached" with a chip per file (thumbnail for images, name, size, ✕). Markup: resources/views/components/file-input.blade.php */
(() => {
  const size = (b) => (b < 1024 ? b + ' B' : b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(1) + ' MB');
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
  const setup = (box) => {
    if (box._fin) return; box._fin = true;
    const input = box.querySelector('input[type=file]'), list = box.querySelector('.fin-list'), title = box.querySelector('.fin-title'), add = box.querySelector('.fin-add');
    const rm = box.querySelector('[data-fin-remove]'), multi = input.multiple, label = box.dataset.label;
    let current = null; try { current = box.dataset.current ? JSON.parse(box.dataset.current) : null; } catch (e) {}
    let files = [];
    const sync = () => { const dt = new DataTransfer(); files.forEach((f) => dt.items.add(f)); input.files = dt.files; };
    const chip = (name, sz, thumb, key) => `<div class="fin-chip"${key} >${thumb ? `<img src="${esc(thumb)}" alt="">` : ''}<div class="fin-nm"><b>${esc(name)}</b><small>${esc(sz)}</small></div><button type="button" class="fin-x" aria-label="Remove">&times;</button></div>`;
    const paint = () => {
      const showCur = current && !files.length && !(rm && rm.value === '1');
      const n = files.length + (showCur ? 1 : 0);
      title.textContent = n ? `${n} File(s) Attached` : label;
      list.innerHTML = (showCur ? chip(current.name, current.size || '', /\.(png|jpe?g|webp|gif)$/i.test(current.name) ? current.url : '', ' data-cur="1"') : '')
        + files.map((f, i) => chip(f.name, size(f.size), f.type.startsWith('image/') ? URL.createObjectURL(f) : '', ` data-i="${i}"`)).join('');
      add.textContent = n && !multi ? 'Replace file' : '+ File';
      box.dispatchEvent(new CustomEvent('fin', {bubbles: true}));
    };
    const take = (incoming) => { incoming = [...incoming]; if (!incoming.length) return; files = multi ? files.concat(incoming) : incoming.slice(0, 1); if (rm) rm.value = '0'; sync(); paint(); };
    add.addEventListener('click', () => input.click());
    input.addEventListener('change', () => { const picked = [...input.files]; files = multi ? files.concat(picked.filter((p) => !files.some((f) => f.name === p.name && f.size === p.size))) : picked.slice(0, 1); if (rm && files.length) rm.value = '0'; sync(); paint(); });
    list.addEventListener('click', (e) => {
      const x = e.target.closest('.fin-x'); if (!x) return; const c = x.closest('.fin-chip');
      if (c.dataset.cur) { if (rm) rm.value = '1'; current = current; box.dataset.removed = '1'; } else { files.splice(+c.dataset.i, 1); sync(); }
      paint();
    });
    ['dragenter', 'dragover'].forEach((ev) => box.addEventListener(ev, (e) => { e.preventDefault(); box.classList.add('drag'); }));
    ['dragleave', 'drop'].forEach((ev) => box.addEventListener(ev, (e) => { e.preventDefault(); box.classList.remove('drag'); }));
    box.addEventListener('drop', (e) => take(e.dataTransfer.files));
    paint();
  };
  const scan = (root) => (root.matches && root.matches('[data-fin]') ? [root] : [...root.querySelectorAll('[data-fin]')]).forEach(setup);
  new MutationObserver((m) => m.forEach((r) => r.addedNodes.forEach((n) => n.nodeType === 1 && scan(n)))).observe(document.body, {childList: true, subtree: true});
  scan(document.body);
})();

/** Sidebar client list: loads 10 at a time as you scroll, with an underline search. Markup: resources/views/partials/side-clients.blade.php */
(() => {
  const setup = (box) => {
    if (box._cl) return; box._cl = true;
    const PAGE = +(box.dataset.per || 10), scroll = box.querySelector('.cl-scroll'), sbar = box.querySelector('.cl-sbar'), input = sbar.querySelector('input'), none = box.querySelector('.cl-none');
    const items = () => [...scroll.querySelectorAll(':scope > .cl-item')];
    let shown = PAGE, q = '';
    q = window._clq || ''; // kept across sidebar re-renders, cleared on a full page load
    const apply = () => {
      let hits = 0;
      items().forEach((it, i) => {
        const hit = !q || it.dataset.name.includes(q);
        if (hit) hits++;
        it.hidden = !(hit && (q || i < shown || it.dataset.active));
      });
      none.hidden = !(q && !hits);
    };
    const fill = () => { // keep loading until the list can scroll, or everything is shown
      apply();
      while (!q && shown < items().length && scroll.scrollHeight <= scroll.clientHeight + 2) { shown += PAGE; apply(); }
    };
    const save = () => { window._clq = q; };
    box.querySelector('[data-cl-search]').addEventListener('click', () => { sbar.hidden = false; input.focus(); });
    input.addEventListener('input', () => { q = input.value.trim().toLowerCase(); save(); fill(); });
    const clear = () => { input.value = ''; q = ''; save(); sbar.hidden = true; scroll.scrollTop = 0; fill(); };
    sbar.querySelector('[data-cl-x]').addEventListener('click', clear);
    input.addEventListener('keydown', (e) => { if (e.key === 'Escape') clear(); });
    scroll.addEventListener('scroll', () => {
      if (!q && shown < items().length && scroll.scrollTop + scroll.clientHeight >= scroll.scrollHeight - 30) { shown += PAGE; apply(); }
    }, {passive: true});
    if (q) { sbar.hidden = false; input.value = q; }
    fill();
    if (scroll.querySelector('.subnav')) box.classList.add('has-sub');
    const on = scroll.querySelector('.cl-item[data-active]');
    if (on && !q) scroll.scrollTop = Math.max(0, on.offsetTop - 8);
    window.addEventListener('resize', fill);
  };
  const scan = (root) => (root.matches && root.matches('[data-cl]') ? [root] : [...root.querySelectorAll('[data-cl]')]).forEach(setup);
  new MutationObserver((m) => m.forEach((r) => r.addedNodes.forEach((n) => n.nodeType === 1 && scan(n)))).observe(document.body, {childList: true, subtree: true});
  scan(document.body);
})();
