@extends('portal.layout')
@section('title', 'Invoice settings')
@section('crumb', 'Invoice settings')
@section('content')
@php use App\Support\Ui; $v = fn ($k) => old($k, $cfg[$k]); @endphp
<div class="pagehead"><div><h1>Invoice settings</h1><p>Numbering, your business details, column names, terms and taxes. They apply to every new invoice.</p></div>
  @if($tab === 'tax')<div class="prow"><button type="button" class="btn primary" data-modal="addTax">{!! Ui::icon('plus') !!}New tax</button></div>@endif</div>

<div class="pseg" style="margin-bottom:18px">
  <a class="{{ $tab === 'general' ? 'on' : '' }}" href="/portal/invoice-settings">General</a>
  <a class="{{ $tab === 'tax' ? 'on' : '' }}" href="/portal/invoice-settings?tab=tax">Tax<em>{{ $taxes->count() }}</em></a>
</div>

@if($tab === 'general')
<form method="post" action="/portal/invoice-settings" class="grid g-main" style="align-items:start">@csrf
  <div class="stack">
    <div class="pn">
      <h3>Invoice numbering</h3>
      <div class="two"><div class="fld"><label>Prefix</label><input name="prefix" id="f-prefix" value="{{ $v('prefix') }}" maxlength="20" placeholder="INV-"><div class="hint">e.g. <code>INV-</code>, <code>MA/</code> or <code>INV-{YYYY}-</code> for the year.</div></div>
        <div class="fld"><label>Digits</label><input type="text" inputmode="numeric" name="padding" id="f-pad" value="{{ $v('padding') }}"><div class="hint">4 gives 0001, 0002, 0003…</div></div></div>
      <div class="fld" style="max-width:240px"><label>Next number</label><input type="text" inputmode="numeric" name="next_number" id="f-next" value="{{ $v('next_number') }}"><div class="hint">Goes up by one with every invoice. {{ $issued }} issued so far.</div></div>
      <div class="notice" style="margin:0">Next invoice will be <b id="numprev">{{ $preview }}</b></div>
    </div>

    <div class="pn">
      <h3>Your business on the invoice</h3>
      <div class="two"><div class="fld"><label>Business name</label><input name="business_name" value="{{ $v('business_name') }}" maxlength="120" placeholder="{{ $agency->name }}"></div>
        <div class="fld"><label>Tax / VAT number</label><input name="tax_id" value="{{ $v('tax_id') }}" maxlength="60" placeholder="TRN 100…"></div></div>
      <div class="fld"><label>Address</label><textarea name="address" maxlength="500" style="min-height:70px">{{ $v('address') }}</textarea></div>
      <div class="two"><div class="fld"><label>Billing email</label><input type="email" name="email" value="{{ $v('email') }}" maxlength="120"></div>
        <div class="fld"><label>Phone</label><input name="phone" value="{{ $v('phone') }}" maxlength="40"></div></div>
      <div class="fld"><label>Bank / payment details</label><textarea name="bank" maxlength="800" style="min-height:90px" placeholder="Account name&#10;Bank, IBAN / account number&#10;SWIFT / IFSC">{{ $v('bank') }}</textarea><div class="hint">Shown as “Pay to” at the bottom of each invoice.</div></div>
      <p class="hint" style="margin:0">The logo on invoices is your agency logo (Settings → Agency profile). Each client’s billing address, tax number and currency are set on the client.</p>
    </div>

    <div class="pn">
      <h3>Default terms and notes</h3>
      <div class="fld"><label>Payment terms</label><x-rte name="terms" :value="$v('terms')" :height="130" /></div>
      <div class="fld"><label>Notes</label><x-rte name="notes" :value="$v('notes')" :height="110" /></div>
      <div class="fld"><label>Line added to every invoice email (optional)</label><textarea name="email_message" maxlength="1000" style="min-height:60px">{{ $v('email_message') }}</textarea></div>
      <div class="hint" style="margin-bottom:14px">You can still change terms and notes on each invoice.</div>
      <button class="btn primary">Save invoice settings</button>
    </div>
  </div>

  <div class="stack">
    <div class="pn">
      <h3>Invoice template</h3>
      <p class="hint" style="margin:0 0 12px">How invoices look on screen, in print, in the PDF and in the email. Click a preview to zoom.</p>
      <div class="tplgrid">
        @foreach(['classic' => 'Classic', 'modern' => 'Modern'] as $key => $tn)
          <div class="tplcard">
            <button type="button" class="tplthumb" data-zoom="{{ $key }}" data-title="{{ $tn }}" title="Click to zoom"><iframe src="/portal/invoice-settings/preview/{{ $key }}" tabindex="-1" loading="lazy" title="{{ $tn }} preview"></iframe><span class="tplhint">{!! Ui::icon('eye') !!}Click to zoom</span></button>
            <label class="tplfoot"><input type="radio" name="template" value="{{ $key }}" @checked(old('template', $cfg['template']) === $key)><span class="tplck">{!! Ui::icon('check') !!}</span><b>{{ $tn }}</b><span class="tplset">SET</span></label>
          </div>
        @endforeach
      </div>
    </div>
    <div class="pn">
      <h3>Column names</h3>
      <p class="hint" style="margin-top:0">What each column is called on the invoice form, the page, the PDF and the email. For example “Hours” instead of “Qty”.</p>
      @foreach(['label_item' => 'Item', 'label_qty' => 'Quantity', 'label_rate' => 'Rate', 'label_discount' => 'Discount', 'label_tax' => 'Tax', 'label_amount' => 'Amount'] as $k => $l)
        <div class="fld"><label>{{ $l }} column</label><input name="{{ $k }}" value="{{ $v($k) }}" maxlength="24"></div>
      @endforeach
    </div>
    <div class="pn">
      <h3>Due date</h3>
      <div class="fld"><label>Payment due after (days)</label><input type="text" inputmode="numeric" name="due_days" value="{{ $v('due_days') }}"><div class="hint">The due date is filled in as the issue date plus this many days.</div></div>
      <p class="hint">Reminder emails are automatic: {{ config('portal.invoice_reminder_days') }} days before the due date, then every {{ config('portal.overdue_repeat_days') }} days while overdue.</p>
    </div>
  </div>
</form>
@else
<div class="pn">
  <h3>Taxes</h3>
  <p class="hint" style="margin-top:0">Add the taxes you charge. On an invoice you can apply one or several to each line. Taxes marked <b>default</b> are added automatically to new lines.</p>
  @if($taxes->isEmpty())
    <div class="empty"><h3>No taxes yet</h3><p>Add VAT, service tax or anything else you charge. Without any, invoices have no tax.</p><button type="button" class="btn primary" data-modal="addTax">{!! Ui::icon('plus') !!}New tax</button></div>
  @else
  <div class="tblwrap"><table class="tbl">
    <thead><tr><th>Name</th><th class="r">Rate</th><th>Default</th><th></th></tr></thead>
    <tbody>
    @foreach($taxes as $t)
      <tr><td><b>{{ $t->name }}</b></td><td class="r">{{ rtrim(rtrim(number_format((float) $t->rate, 2), '0'), '.') }}%</td>
        <td>@if($t->is_default)<span class="stag s-approved">Default</span>@else<span class="mute">—</span>@endif</td>
        <td class="r"><div class="prow" style="justify-content:flex-end;gap:6px">
          <button type="button" class="ibtn" title="Edit" data-edit-tax data-id="{{ $t->id }}" data-name="{{ $t->name }}" data-rate="{{ (float) $t->rate }}" data-default="{{ $t->is_default ? 1 : 0 }}">{!! Ui::icon('edit') !!}</button>
          <form method="post" action="/portal/invoice-taxes/{{ $t->id }}" data-confirm="Remove {{ $t->name }}? Existing invoices keep it." style="margin:0">@csrf @method('DELETE')<button class="ibtn" title="Remove">{!! Ui::icon('trash') !!}</button></form>
        </div></td></tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>

<x-modal id="addTax" title="New tax">
  <form method="post" action="/portal/invoice-taxes">@csrf
    <div class="two"><div class="fld"><label>Tax name</label><input name="name" required maxlength="60" placeholder="VAT"></div>
      <div class="fld"><label>Rate %</label><input name="rate" type="text" inputmode="decimal" required placeholder="5"></div></div>
    <label class="check" style="margin-bottom:14px"><input type="checkbox" name="is_default" value="1"> Add to new invoice lines by default</label>
    <button class="btn primary">Add tax</button>
  </form>
</x-modal>
<x-modal id="editTax" title="Edit tax">
  <form method="post" action="" id="editTaxForm">@csrf @method('PUT')
    <div class="two"><div class="fld"><label>Tax name</label><input name="name" id="et-name" required maxlength="60"></div>
      <div class="fld"><label>Rate %</label><input name="rate" id="et-rate" type="text" inputmode="decimal" required></div></div>
    <label class="check" style="margin-bottom:6px"><input type="checkbox" name="is_default" id="et-def" value="1"> Add to new invoice lines by default</label>
    <p class="hint">Invoices already issued keep the rate they were issued with.</p>
    <button class="btn primary">Save tax</button>
  </form>
</x-modal>
@endif

<x-modal id="tplZoom" title="Template preview" :wide="true">
  <div class="zbar"><button type="button" class="btn sm" data-z="-">−</button><input type="range" id="zrange" min="40" max="150" value="80"><button type="button" class="btn sm" data-z="+">+</button><b id="zval">80%</b><button type="button" class="btn sm" data-z="fit">Fit</button></div>
  <div class="zbox" id="zbox"><div class="zin" id="zin"><iframe id="zframe" src="about:blank" title="Preview"></iframe></div></div>
</x-modal>
@push('scripts')
<script>
(function () {
  var p = document.getElementById('f-prefix'), d = document.getElementById('f-pad'), n = document.getElementById('f-next'), out = document.getElementById('numprev');
  if (p) { var show = function () { out.textContent = (p.value || '').replace('{YYYY}', new Date().getFullYear()) + String(n.value || 1).padStart(parseInt(d.value) || 1, '0'); }; [p, d, n].forEach(function (e) { e.addEventListener('input', show); }); }
  document.querySelectorAll('[data-edit-tax]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.getElementById('editTaxForm').action = '/portal/invoice-taxes/' + b.dataset.id;
      document.getElementById('et-name').value = b.dataset.name; document.getElementById('et-rate').value = b.dataset.rate; document.getElementById('et-def').checked = b.dataset.default === '1';
      document.querySelector('#editTax').hidden = false; document.body.classList.add('mdl-open');
    });
  });
})();
</script>
@endpush
@endsection
