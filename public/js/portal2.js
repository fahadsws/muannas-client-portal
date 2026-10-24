/* Portal extras: rich text editor, invoice bulk actions and payment modal, global file search, task modal. */

// rich text editors (Quill): <div data-rte> holds the editor and a hidden input with the clean HTML
(function () {
  if (!window.Quill) return;
  document.querySelectorAll('[data-rte]').forEach(function (box) {
    var host = box.querySelector('.rte-host'), input = box.querySelector('input[type=hidden]');
    var q = new Quill(host, {
      theme: 'snow', placeholder: 'Type here…',
      modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
    });
    var sync = function () { input.value = q.getText().trim() === '' ? '' : q.root.innerHTML; };
    q.on('text-change', sync);
    var f = box.closest('form'); if (f) f.addEventListener('submit', sync);
  });
})();
// invoices: bulk selection
(function () {
  var bar = document.getElementById('bulkbar'); if (!bar) return;
  var boxes = function () { return [].slice.call(document.querySelectorAll('input.rowck')); };
  var upd = function () {
    var n = boxes().filter(function (c) { return c.checked; }).length;
    bar.hidden = n === 0; document.getElementById('bulkn').textContent = n + ' selected';
    var all = document.getElementById('ckall'); if (all) all.checked = n > 0 && n === boxes().length;
  };
  document.addEventListener('change', function (e) {
    if (e.target.id === 'ckall') { boxes().forEach(function (c) { c.checked = e.target.checked; }); upd(); }
    else if (e.target.classList.contains('rowck')) upd();
  });
  bar.querySelectorAll('button[data-bulk]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.preventDefault();
      var f = document.getElementById('bulkForm'), kind = b.dataset.bulk;
      var go = function () { f.querySelector('[name=bulk]').value = kind; f.submit(); };
      var msg = kind === 'delete' ? 'Delete the selected invoices? Paid invoices are skipped.' : 'Mark the selected unpaid invoices as received in full, dated today?';
      if (window.Swal) Swal.fire({ title: kind === 'delete' ? 'Delete invoices?' : 'Mark as received?', text: msg, icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, continue' }).then(function (r) { if (r.isConfirmed) go(); });
      else if (confirm(msg)) go();
    });
  });
})();

// invoice list: the "Mark receipt" button fills the shared payment modal
(function () {
  document.querySelectorAll('[data-pay]').forEach(function (b) {
    b.addEventListener('click', function () {
      var m = document.getElementById('payInvoice'); if (!m) return;
      m.querySelector('form').action = '/portal/invoices/' + b.dataset.pay + '/paid';
      m.querySelector('.mdl-head h3').textContent = 'Mark receipt · ' + b.dataset.number;
      m.querySelector('[name=paid_amount]').value = b.dataset.amount;
      var cur = m.querySelector('[data-cur]'); if (cur) cur.textContent = b.dataset.cur;
    });
  });
})();

// global file search (Files & Assets)
(function () {
  var inp = document.getElementById('gsearch'); if (!inp) return;
  var out = document.getElementById('gres'), t;
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
  inp.addEventListener('input', function () {
    clearTimeout(t);
    var q = inp.value.trim();
    if (q.length < 1) { out.hidden = true; return; }
    t = setTimeout(function () {
      fetch(inp.dataset.url + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (rows) {
        out.hidden = false;
        out.innerHTML = rows.length ? rows.map(function (f) {
          return '<div class="r"><span class="b" style="background:' + f.bg + ';color:' + f.fg + '">' + esc(f.kind) + '</span>' +
            '<div class="t"><b>' + esc(f.name) + '</b><small>' + esc(f.path) + '</small></div>' +
            '<a class="btn sm icon-only" href="' + f.open + '" target="_blank" rel="noopener" title="Open file" aria-label="Open file"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg></a><a class="btn sm icon-only" href="' + f.url + '" title="Download file" aria-label="Download file"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12M7 10l5 5 5-5M4 20h16"></path></svg></a></div>';
        }).join('') : '<div class="none">No file matches “' + esc(q) + '”.</div>';
      });
    }, 200);
  });
  document.addEventListener('click', function (e) { if (!e.target.closest('.gsearch')) out.hidden = true; });
  inp.addEventListener('focus', function () { if (out.innerHTML) out.hidden = false; });
})();

// task modal: fills the add / edit form
(function () {
  var m = document.getElementById('taskModal'); if (!m) return;
  var f = m.querySelector('form');
  var set = function (n, v) {
    var e = f.querySelector('[name=' + n + ']'); e.value = v || '';
    if (window.UI && e.tagName === 'SELECT') window.UI.setSelect(e, v || '');
    if (e._flatpickr) e._flatpickr.setDate(v || null, false);
  };
  document.querySelectorAll('[data-task]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.dataset.task ? JSON.parse(b.dataset.task) : null;
      f.action = t ? '/portal/tasks/' + t.id : f.dataset.add;
      f.querySelector('[name=_method]').value = t ? 'PUT' : 'POST';
      m.querySelector('.mdl-head h3').textContent = t ? 'Edit task' : 'Add task';
      set('title', t && t.title); set('description', t && t.description); set('assignee_id', t && t.assignee_id); set('due_on', t && t.due_on); set('status', t ? t.status : 'todo');
    });
  });
})();

// invoice template zoom window
(function () {
  var frame = document.getElementById('zframe'); if (!frame) return;
  var modal = document.getElementById('tplZoom'), range = document.getElementById('zrange'), val = document.getElementById('zval'), zin = document.getElementById('zin'), box = document.getElementById('zbox');
  var set = function (p) {
    p = Math.max(40, Math.min(150, p)); range.value = p; val.textContent = p + '%';
    frame.style.zoom = p / 100;
  };
  document.querySelectorAll('[data-zoom]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.preventDefault(); e.stopPropagation();
      frame.src = '/portal/invoice-settings/preview/' + b.dataset.zoom;
      modal.querySelector('.mdl-head h3').textContent = b.dataset.title + ' template';
      modal.hidden = false; document.body.classList.add('mdl-open');
      set(Math.round(Math.min(100, (box.clientWidth - 16) / 860 * 100)) || 80);
    });
  });
  range.addEventListener('input', function () { set(+range.value); });
  modal.querySelectorAll('[data-z]').forEach(function (b) {
    b.addEventListener('click', function () {
      var z = b.dataset.z; if (z === 'fit') set(Math.round((box.clientWidth - 16) / 860 * 100)); else set(+range.value + (z === '+' ? 10 : -10));
    });
  });
})();

// mobile sidebar: dim the page behind it and close it on an outside tap or after choosing a link
(function () {
  var side = document.getElementById('side'); if (!side) return;
  var bd = document.createElement('div'); bd.className = 'sbackdrop'; document.body.appendChild(bd);
  var sync = function () { bd.classList.toggle('on', side.classList.contains('open')); };
  new MutationObserver(sync).observe(side, { attributes: true, attributeFilter: ['class'] });
  bd.addEventListener('click', function () { side.classList.remove('open'); });
  side.addEventListener('click', function (e) { if (e.target.closest('a')) side.classList.remove('open'); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') side.classList.remove('open'); });
})();
