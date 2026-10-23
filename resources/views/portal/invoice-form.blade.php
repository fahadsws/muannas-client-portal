@extends('portal.layout')
@php $isNew = ! $invoice->exists; @endphp
@section('title', $isNew ? 'New invoice' : 'Edit '.$invoice->number)
@section('crumb', $isNew ? 'New invoice' : 'Edit '.$invoice->number)
@section('content')
@php
  use App\Support\Ui;
  $v = fn ($k, $d = null) => old($k, $invoice->$k ?? $d);
  $L = fn ($k) => e($cfg['label_'.$k]);
  $defaults = $taxes->where('is_default', true)->map(fn ($t) => ['name' => $t->name, 'rate' => (float) $t->rate])->values();
@endphp
<style>
.iv-t{border:1px solid var(--border);border-radius:12px;background:var(--surface);overflow:hidden}
.iv-r{display:grid;grid-template-columns:44px minmax(240px,1fr) 90px 120px 110px 160px 120px 48px;align-items:stretch;border-bottom:1px solid var(--border)}
.iv-r:last-child{border-bottom:0}
.iv-r>*{padding:10px 10px;min-width:0;border-right:1px solid var(--border2)}.iv-r>*:last-child{border-right:0}.iv-h>*{border-right-color:var(--border)}
.iv-h{background:var(--surface2);border-bottom:1px solid var(--border)!important;align-items:center}
.iv-h>*{padding-top:11px;padding-bottom:11px;font-size:11.5px;font-weight:650;text-transform:uppercase;letter-spacing:.04em;color:var(--mute)}
.iv-ra{text-align:right}
.iv-n{color:var(--mute);font-weight:600;padding-top:16px;text-align:center}
.iv-in{display:block;width:100%;height:34px;padding:0 6px;border:0;border-bottom:1px solid transparent;border-radius:0;background:transparent;font:inherit;font-size:14px;color:var(--ink);outline:none;box-shadow:none;transition:border-color .15s}
.iv-in::placeholder{color:#b3b6c2}.iv-in:hover{border-bottom-color:var(--border)}.iv-in:focus{border-bottom-color:var(--accent)}
.iv-in.num{text-align:right;font-variant-numeric:tabular-nums}
textarea.iv-in.det{height:auto;min-height:64px;max-height:220px;padding:7px 6px;margin-top:4px;font-size:13px;line-height:1.5;color:var(--ink2);resize:vertical;overflow:auto}
.iv-in.bad{border-bottom-color:var(--red,#c0392b)!important;background:rgba(192,57,43,.06)}
.iv-lnk{display:block;width:100%;padding:6px 4px;border:0;background:none;font:inherit;font-size:14px;color:var(--accent);text-align:right;cursor:pointer;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.iv-lnk:hover{text-decoration:underline}.iv-lnk.set{font-weight:550}
.iv-pop .iv-in{height:40px;padding:0 12px;border:1px solid var(--border);border-radius:9px;background:var(--surface)}.iv-pop .iv-in:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft)}
.iv-err{display:none;margin:12px 0 0;padding:10px 14px;border-radius:9px;background:var(--red-soft,#fbe7e1);color:var(--red,#c0392b);font-size:13px}.iv-err.on{display:block}
.iv-perr{color:var(--red,#c0392b);font-size:12px;margin:8px 0 0}
.iv-pop .iv-tt{border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:4px}
.iv-pop .iv-tt .iv-tx{display:grid;grid-template-columns:24px 64px 1fr;gap:6px;padding:9px 10px;border-top:1px solid var(--border2);margin:0}.iv-pop .iv-tt .iv-tx:first-child{border-top:0}
.iv-pop .iv-tth{display:grid;grid-template-columns:24px 64px 1fr;gap:6px;padding:8px 10px;background:var(--surface2);font-size:11px;font-weight:650;text-transform:uppercase;letter-spacing:.04em;color:var(--mute)}
.iv-pop .iv-newt{display:inline-block;margin-top:8px;font-size:13px;color:var(--accent)}
.iv-amt{text-align:right;font-weight:650;padding-top:16px;font-variant-numeric:tabular-nums}
.iv-x{display:flex;justify-content:center;align-items:flex-start;padding-top:8px!important}.iv-x .ibtn{width:36px;height:36px}
.iv-tot{margin-left:auto;width:min(400px,100%);margin-top:18px;padding:14px 16px;background:var(--surface2);border-radius:12px}
.iv-tot>div{display:flex;justify-content:space-between;align-items:center;padding:6px 0;font-size:13.5px}.iv-tot #t-taxes{display:block;padding:0}.iv-tot #t-taxes>div{display:flex;justify-content:space-between;padding:6px 0}
.iv-tot .g{border-top:1.5px solid var(--ink);margin-top:8px;padding-top:12px;font-size:17px;font-weight:700}
.iv-tot .iv-tl{background:none;border:0;padding:0;color:var(--accent);font:inherit;cursor:pointer}.iv-tot .iv-tl:hover{text-decoration:underline}
.iv-pop{position:absolute;z-index:950;width:360px;background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:0 18px 50px rgba(0,0,0,.25)}
.iv-pop[hidden]{display:none}
.iv-pop .iv-ph{display:flex;justify-content:space-between;align-items:center;padding:10px 12px 10px 16px;border-bottom:1px solid var(--border);font-weight:650;font-size:14px}
.iv-pop .iv-pb{padding:16px}.iv-pop .iv-pf{display:flex;justify-content:flex-end;gap:10px;padding:2px 16px 16px}.iv-pop .iv-pf .btn{height:38px;padding:0 18px}
.iv-pop .iv-dr{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.iv-pop select.iv-sel{appearance:none;-webkit-appearance:none;width:100%;height:44px;padding:0 34px 0 14px;border:1px solid var(--border);border-radius:9px;background:var(--surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5l5 5 5-5' fill='none' stroke='%239aa0ae' stroke-width='1.8' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 12px center;font:inherit;font-size:14px;color:var(--ink);cursor:pointer;outline:none}
.iv-pop select.iv-sel:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft)}
.iv-pop .iv-dr .iv-in{height:44px}
.iv-pop .iv-tx{display:flex;align-items:center;gap:10px;padding:8px 2px;font-size:13.5px;cursor:pointer}.iv-pop .iv-tx input{width:16px;height:16px;accent-color:var(--ink)}.iv-pop .iv-tx small{color:var(--mute);margin-left:auto;font-size:12.5px}
.iv-t{overflow-x:auto}.iv-w{min-width:900px}
</style>
<a class="back" href="{{ $isNew ? '/portal/c/'.$account->id.'/invoices' : '/portal/invoices/'.$invoice->id }}">{!! Ui::icon('arrow') !!}Back</a>
<div class="pagehead"><div><h1>{{ $isNew ? 'New invoice' : 'Edit invoice '.$invoice->number }}</h1><p>{{ $account->name }} · billed in <b>{{ $invoice->currency }}</b>@if(! $account->billing_address) · <a href="/portal/clients/{{ $account->id }}">add the client’s billing address</a> so it prints on the invoice @endif</p></div></div>

<form method="post" enctype="multipart/form-data" action="{{ $isNew ? '/portal/c/'.$account->id.'/invoices' : '/portal/invoices/'.$invoice->id }}" id="invform">
  @csrf @unless($isNew) @method('PUT') @endunless
  <div class="pn">
    <h3>Invoice details</h3>
    <div class="two">
      <div class="fld"><label>Invoice number</label><input name="number" value="{{ old('number', $number) }}" maxlength="40"><div class="hint">{{ $isNew ? 'Filled in from your numbering sequence. Change it only if you need a specific number.' : 'Numbers stay unique within your agency.' }}</div></div>
      <div class="fld"><label>Title (optional)</label><input name="title" value="{{ $v('title') }}" maxlength="160" placeholder="October content package"></div>
    </div>
    <div class="two">
      <div class="fld"><label>Project (optional)</label>
        <select name="project_id" data-search="5"><option value="">No project</option>@foreach($projects as $p)<option value="{{ $p->id }}" @selected((string) $v('project_id') === (string) $p->id)>{{ $p->name }}</option>@endforeach</select></div>
      <div class="two" style="gap:12px">
        <div class="fld"><label>Issue date</label><input type="date" name="issued_on" value="{{ old('issued_on', $invoice->issued_on->format('Y-m-d')) }}" required></div>
        <div class="fld"><label>Due date</label><input type="date" name="due_on" value="{{ old('due_on', $invoice->due_on->format('Y-m-d')) }}" required></div>
      </div>
    </div>
  </div>

  <div class="pn">
    <h3>Line items</h3>
    <div class="iv-t" id="lt"><div class="iv-w">
      <div class="iv-r iv-h"><div>#</div><div>{{ $cfg['label_item'] }}</div><div class="iv-ra">{{ $cfg['label_qty'] }}</div><div class="iv-ra">{{ $cfg['label_rate'] }}</div><div class="iv-ra">{{ $cfg['label_discount'] }}</div><div class="iv-ra">{{ $cfg['label_tax'] }}</div><div class="iv-ra">{{ $cfg['label_amount'] }}</div><div></div></div>
      <div id="lines"></div>
    </div></div>
    <div class="iv-err" id="iv-err" role="alert"></div>
    <div class="prow" style="margin-top:12px"><button type="button" class="btn sm" id="addline">{!! Ui::icon('plus') !!}Add line</button></div>

    <div class="iv-tot">
      <div><span>Subtotal</span><span id="t-sub">0.00</span></div>
      <div><button type="button" class="iv-tl" id="od-btn">(−) Overall discount</button><span id="t-disc">0.00</span></div>
      <div id="t-taxes"></div>
      <div class="g"><span>Total ({{ $invoice->currency }})</span><span id="t-total">0.00</span></div>
    </div>
    <input type="hidden" name="discount_type" id="od-type" value="{{ $v('discount_type', 'percent') }}"><input type="hidden" name="discount_value" id="od-val" value="{{ $v('discount_value', 0) }}">
  </div>

  <div class="pn">
    <h3>Terms, notes and PDF</h3>
    <div class="fld"><label>Payment terms</label><textarea name="terms" maxlength="3000" style="min-height:80px">{{ $v('terms') }}</textarea></div>
    <div class="fld"><label>Notes</label><textarea name="notes" maxlength="3000" style="min-height:70px">{{ $v('notes') }}</textarea></div>
    <div class="fld"><x-file-input name="file" accept="application/pdf" label="Your own PDF (optional)" remove-name="remove_file"
        :current="(! $isNew && $invoice->file_path) ? ['name' => 'Invoice-'.$invoice->number.'.pdf', 'size' => 'Uploaded PDF', 'url' => '/portal/invoices/'.$invoice->id.'/pdf?view=1'] : null"
        hint="If you attach one, it replaces the generated PDF for download and email. The on-screen invoice stays as designed." /></div>
  </div>

  <div class="prow" style="margin-top:16px">
    @if($isNew)
      <button class="btn primary" name="action" value="send">{!! Ui::icon('inbox') !!}Create &amp; email to client</button>
      <button class="btn" name="action" value="draft">Save as draft</button>
    @else
      <button class="btn primary">Save changes</button>
    @endif
    <a class="btn grey" href="{{ $isNew ? '/portal/c/'.$account->id.'/invoices' : '/portal/invoices/'.$invoice->id }}">Cancel</a>
  </div>
  @if($isNew)<p class="hint">A draft is only visible to your team until you email it. {{ $account->clientUsers()->count() ? '' : 'This client has no login yet, so nothing would be emailed.' }}</p>@endif
</form>

<div class="iv-pop" id="pop" hidden></div>

@push('scripts')
<script>
(function () {
  var CUR = @json($invoice->currency), TAXES = @json($taxes->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'rate' => (float) $t->rate])->values()),
      DEF = @json($defaults), ROWS = @json(array_values($rows)), CSRF = document.querySelector('meta[name=csrf-token]').content;
  var linesEl = document.getElementById('lines'), pop = document.getElementById('pop'), state = [];
  var num = function (s) { var n = parseFloat(String(s).replace(/,/g, '')); return isNaN(n) ? 0 : n; };
  var money = function (n) { return n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); };
  var pct = function (n) { return String(Math.round(n * 100) / 100); };

  function blank() { return {description: '', details: '', qty: 1, unit_price: '', discount_type: 'percent', discount_value: 0, taxes: DEF.map(function (t) { return {name: t.name, rate: t.rate}; })}; }

  function discLabel(r) { var v = num(r.discount_value); return r.discount_type === 'fixed' ? money(v) : money(v) + '%'; }
  function taxLabel(r) { return r.taxes.length ? r.taxes.map(function (t) { return esc(t.name) + ' ' + pct(t.rate) + '%'; }).join(', ') : 'Add Taxes'; }

  function rowHtml(r, i) {
    var h = '<div class="iv-r" data-i="' + i + '"><div class="iv-n">' + (i + 1) + '</div>'
      + '<div><input class="iv-in" data-k="description" name="items[' + i + '][description]" value="' + esc(r.description) + '" placeholder="Type an item or service" maxlength="255">'
      + '<textarea class="iv-in det" data-k="details" rows="2" name="items[' + i + '][details]" placeholder="Add description to your item" maxlength="500">' + esc(r.details) + '</textarea></div>'
      + '<div><input class="iv-in num" data-k="qty" name="items[' + i + '][qty]" value="' + esc(r.qty) + '" inputmode="decimal" autocomplete="off"></div>'
      + '<div><input class="iv-in num" data-k="unit_price" name="items[' + i + '][unit_price]" value="' + esc(r.unit_price) + '" inputmode="decimal" placeholder="0.00" autocomplete="off"></div>'
      + '<div><button type="button" class="iv-lnk ' + (num(r.discount_value) ? 'set' : '') + '" data-pop="disc">' + discLabel(r) + '</button>'
      + '<input type="hidden" name="items[' + i + '][discount_type]" value="' + r.discount_type + '"><input type="hidden" name="items[' + i + '][discount_value]" value="' + esc(r.discount_value) + '"></div>'
      + '<div><button type="button" class="iv-lnk ' + (r.taxes.length ? 'set' : '') + '" data-pop="tax">' + taxLabel(r) + '</button>'
      + r.taxes.map(function (t, k) { return '<input type="hidden" name="items[' + i + '][taxes][' + k + '][name]" value="' + esc(t.name) + '"><input type="hidden" name="items[' + i + '][taxes][' + k + '][rate]" value="' + esc(t.rate) + '">'; }).join('') + '</div>'
      + '<div class="iv-amt" data-amt>0.00</div>'
      + '<div class="iv-x"><button type="button" class="ibtn" data-rm aria-label="Remove line">{!! Ui::icon('trash') !!}</button></div></div>';
    return h;
  }

  function grow(el) { el.style.height = 'auto'; el.style.height = Math.min(220, Math.max(64, el.scrollHeight)) + 'px'; }
  function render() { linesEl.innerHTML = state.map(rowHtml).join(''); linesEl.querySelectorAll('textarea.det').forEach(grow); calc(); }

  function itemNet(r) {
    var gross = num(r.qty) * num(r.unit_price), v = num(r.discount_value);
    var d = r.discount_type === 'fixed' ? Math.min(gross, v) : gross * Math.min(100, v) / 100;
    return {gross: gross, disc: d, net: gross - d};
  }

  function calc() {
    var sub = 0, nets = state.map(function (r, i) { var x = itemNet(r); sub += x.net; var el = linesEl.children[i]; if (el) el.querySelector('[data-amt]').textContent = money(x.net); return x.net; });
    var ot = document.getElementById('od-type').value, ov = num(document.getElementById('od-val').value);
    var od = ot === 'percent' ? sub * Math.min(100, ov) / 100 : Math.min(sub, ov), f = sub > 0 ? (sub - od) / sub : 0, groups = {}, tax = 0;
    state.forEach(function (r, i) { r.taxes.forEach(function (t) { var k = t.name + '|' + t.rate; groups[k] = groups[k] || {name: t.name, rate: t.rate, amount: 0}; groups[k].amount += nets[i] * f * num(t.rate) / 100; }); });
    var th = '';
    Object.keys(groups).forEach(function (k) { var g = groups[k], a = Math.round(g.amount * 100) / 100; tax += a; th += '<div><span>' + esc(g.name) + ' (' + pct(num(g.rate)) + '%)</span><span>' + money(a) + '</span></div>'; });
    document.getElementById('t-taxes').innerHTML = th; 
    document.getElementById('t-sub').textContent = money(sub); document.getElementById('t-disc').textContent = money(od);
    document.getElementById('t-total').textContent = money(sub - od + tax);
  }

  // ── popovers ──
  var cur = null;
  function open(btn, title, body, onSave) {
    var b = btn.getBoundingClientRect();
    pop.innerHTML = '<div class="iv-ph"><span>' + title + '</span><button type="button" class="ibtn" data-pc aria-label="Close">{!! Ui::icon('x') !!}</button></div><div class="iv-pb">' + body + '</div><div class="iv-pf"><button type="button" class="btn sm" data-pc>Cancel</button><button type="button" class="btn sm primary" data-ps>Save</button></div>';
    pop.hidden = false; cur = onSave;
    // a dropdown anchored to its trigger in page coordinates, so it moves with the page when you scroll
    var w = 360, sx = window.pageXOffset, sy = window.pageYOffset, left = Math.min(Math.max(8, b.right - w), document.documentElement.clientWidth - w - 8) + sx, top = b.bottom + sy + 4;
    pop.style.left = left + 'px'; pop.style.top = top + 'px';
    var inp = pop.querySelector('input[type=text]'); inp && inp.focus();
  }
  function close() { pop.hidden = true; cur = null; }
  document.body.appendChild(pop);
  window.addEventListener('resize', close);
  function applyDisc(done) {
    var ty = pop.querySelector('#pt').value, raw = pop.querySelector('#pv').value.trim().replace(/,/g, ''), v = raw === '' ? 0 : Number(raw), msg = '';
    if (isNaN(v) || v < 0) msg = 'Enter a number, 0 or more.'; else if (ty === 'percent' && v > 100) msg = 'A percentage can\u2019t be more than 100.';
    var old = pop.querySelector('.iv-perr'); if (old) old.remove();
    if (msg) { pop.querySelector('.iv-pb').insertAdjacentHTML('beforeend', '<div class="iv-perr">' + msg + '</div>'); return false; }
    done(ty, v);
  }
  function discBody(type, val) {
    return '<div class="iv-dr"><select class="nos2 iv-sel" id="pt"><option value="percent"' + (type === 'percent' ? ' selected' : '') + '>Percentage (%)</option><option value="fixed"' + (type === 'fixed' ? ' selected' : '') + '>Fixed amount</option></select>'
      + '<input type="text" class="iv-in num" inputmode="decimal" id="pv" value="' + esc(val) + '" placeholder="0.00"></div>';
  }
  pop.addEventListener('click', function (e) {
    if (e.target.closest('[data-pc]')) return close();
    if (e.target.closest('[data-ps]') && cur) { if (cur() !== false) close(); return; }
  });
  function taxRow(t, on) { return '<label class="iv-tx"><input type="checkbox" data-name="' + esc(t.name) + '" data-rate="' + t.rate + '"' + (on ? ' checked' : '') + '><span>' + pct(t.rate) + '%</span><span>' + esc(t.name) + '</span></label>'; }
  document.addEventListener('mousedown', function (e) { if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('[data-pop],#od-btn')) close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

  linesEl.addEventListener('input', function (e) {
    var row = e.target.closest('.iv-r'), k = e.target.dataset.k; if (!row || !k) return;
    if (k === 'qty' || k === 'unit_price') { var c = e.target.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1'); if (c !== e.target.value) e.target.value = c; }
    e.target.classList.remove('bad'); if (e.target.tagName === 'TEXTAREA') grow(e.target);
    state[+row.dataset.i][k] = e.target.value; calc();
  });
  linesEl.addEventListener('click', function (e) {
    var row = e.target.closest('.iv-r'); if (!row) return; var i = +row.dataset.i, r = state[i];
    if (e.target.closest('[data-rm]')) { state.splice(i, 1); if (!state.length) state.push(blank()); render(); return; }
    var p = e.target.closest('[data-pop]'); if (!p) return;
    if (p.dataset.pop === 'disc') {
      open(p, 'Item discount', discBody(r.discount_type, r.discount_value), function () { return applyDisc(function (t, v) { r.discount_type = t; r.discount_value = v; render(); }); });
    } else {
      var known = TAXES.map(function (t) { return taxRow(t, r.taxes.some(function (x) { return x.name === t.name && num(x.rate) === num(t.rate); })); }).join('');
      var extra = r.taxes.filter(function (x) { return !TAXES.some(function (t) { return t.name === x.name && num(t.rate) === num(x.rate); }); }).map(function (x) { return taxRow(x, true); }).join('');
      open(p, 'Add Taxes', (known + extra) ? '<div class="iv-tt"><div class="iv-tth"><span></span><span>Rate</span><span>Tax name</span></div><div id="txlist">' + known + extra + '</div></div><a class="iv-newt" href="/portal/invoice-settings?tab=tax" target="_blank">+ New Tax</a>' : '<p class="hint" style="margin:0">No taxes set up yet.</p><a class="iv-newt" href="/portal/invoice-settings?tab=tax" target="_blank">+ New Tax</a>',
        function () { r.taxes = [].map.call(pop.querySelectorAll('#txlist input:checked'), function (c) { return {name: c.dataset.name, rate: num(c.dataset.rate)}; }); render(); });
    }
  });
  document.getElementById('od-btn').addEventListener('click', function (e) {
    var t = document.getElementById('od-type'), v = document.getElementById('od-val');
    open(e.currentTarget, 'Overall discount', discBody(t.value, v.value), function () { return applyDisc(function (ty, val) { t.value = ty; v.value = val; calc(); }); });
  });
  document.getElementById('addline').addEventListener('click', function () { state.push(blank()); render(); var d = linesEl.lastElementChild.querySelector('[data-k=description]'); d && d.focus(); });

  document.getElementById('invform').addEventListener('submit', function (e) {
    var bad = [], used = 0;
    linesEl.querySelectorAll('.iv-r').forEach(function (row, i) {
      var d = row.querySelector('[data-k=description]'), q = row.querySelector('[data-k=qty]'), p = row.querySelector('[data-k=unit_price]');
      [d, q, p].forEach(function (x) { x.classList.remove('bad'); });
      if (!d.value.trim() && !p.value.trim()) return;
      used++;
      if (!d.value.trim()) { d.classList.add('bad'); bad.push('Line ' + (i + 1) + ': enter an item or service.'); }
      if (!(num(q.value) > 0)) { q.classList.add('bad'); bad.push('Line ' + (i + 1) + ': quantity must be more than 0.'); }
      if (p.value.trim() === '' || isNaN(Number(p.value.replace(/,/g, '')))) { p.classList.add('bad'); bad.push('Line ' + (i + 1) + ': enter a rate (0 or more).'); }
    });
    if (!used) bad.push('Add at least one line item.');
    var box = document.getElementById('iv-err');
    if (bad.length) { e.preventDefault(); box.innerHTML = bad.map(function (m) { return '<div>' + m + '</div>'; }).join(''); box.classList.add('on'); box.scrollIntoView({block: 'center', behavior: 'smooth'}); } else box.classList.remove('on');
  });
  state = ROWS.length ? ROWS.map(function (r) { return Object.assign(blank(), r, {taxes: (r.taxes || []).map(function (t) { return {name: t.name, rate: num(t.rate)}; })}); }) : [blank()];
  render();
})();
</script>
@endpush
@endsection
