@extends('portal.layout')
@section('title', 'Invoice '.$i->number)
@section('crumb', 'Invoice '.$i->number)
@section('content')
@php use App\Models\Invoice; use App\Support\Ui; $locked = $i->isPaid(); @endphp
<style>
@media print{.side,.bar,.noprint,.notice,.toast,.impbar,.mdl{display:none!important}.app{display:block}.main{margin:0}.content{padding:0}body{background:#fff}.ip{padding:8px 0 0}@page{size:A4;margin:12mm}.sheetwrap{box-shadow:none!important;border:0!important;max-width:none}}
.sheetwrap{max-width:880px;margin:0 auto;background:#fff;border:1px solid var(--border);border-radius:6px;box-shadow:0 4px 24px rgba(20,25,50,.08);overflow-x:auto}
.sheetwrap .ip{min-width:640px}
.istate{display:flex;gap:18px;flex-wrap:wrap;margin:0 0 16px;font-size:12.5px;color:var(--mute)}.istate b{color:var(--ink);font-weight:600}
</style>

<a class="back noprint" href="/portal/c/{{ $account->id }}/invoices">{!! Ui::icon('arrow') !!}All invoices</a>
<div class="pagehead noprint"><div><h1>Invoice {{ $i->number }}</h1><p>{{ $account->name }} · {{ $i->money() }} · <span class="stag s-{{ $i->statusKey() }}">{{ $i->statusLabel() }}</span></p></div>
  <div class="prow">
    <a class="btn" href="/portal/invoices/{{ $i->id }}/pdf?view=1" target="_blank">{!! Ui::icon('eye') !!}PDF</a>
    <a class="btn" href="/portal/invoices/{{ $i->id }}/pdf">{!! Ui::icon('dl') !!}Download</a>
    <a class="btn" href="/portal/invoices/{{ $i->id }}/pdf?view=1&print=1" target="_blank" rel="noopener">{!! Ui::icon('doc') !!}Print</a>
    @if($manage)
      <button type="button" class="btn" data-modal="sendInvoice">{!! Ui::icon('inbox') !!}Email invoice</button>
      @if($i->editable())<a class="btn" href="/portal/invoices/{{ $i->id }}/edit">{!! Ui::icon('edit') !!}Edit</a>@endif
      @if($locked)
        <form method="post" action="/portal/invoices/{{ $i->id }}/unpaid" data-confirm="Mark this invoice as unpaid? You can then edit or delete it." style="margin:0">@csrf<button class="btn">Mark unpaid</button></form>
      @else
        <button type="button" class="btn primary" data-modal="payInvoice">{!! Ui::icon('check') !!}Mark receipt</button>
      @endif
    @endif
  </div></div>

<div class="istate noprint">
  <span>Created <b>{{ $i->created_at->format('j M Y') }}</b></span>
  <span>{{ $i->sent_at ? 'Last emailed' : 'Not emailed yet' }} @if($i->sent_at)<b>{{ $i->sent_at->format('j M Y, H:i') }}</b>@endif</span>
  @if($i->draft)<span style="color:var(--amber,#b45309)"><b>Draft</b> — the client cannot see this yet</span>@endif
  @if($i->file_path)<span>Using the uploaded PDF for download and email</span>@endif
</div>

<div class="sheetwrap">@include('portal.partials.invoice-paper')</div>

@if($manage)
  <div class="noprint" style="margin-top:18px">
    @if($i->editable())
      <form method="post" action="/portal/invoices/{{ $i->id }}" data-confirm="Delete invoice {{ $i->number }}? This cannot be undone." data-confirm-title="Delete this invoice?" data-confirm-btn="Yes, delete" data-confirm-danger="1" style="margin:0">@csrf @method('DELETE')
        <button class="btn danger sm">{!! Ui::icon('trash') !!}Delete invoice</button></form>
    @else
      <p class="hint">Paid invoices are locked. Mark it unpaid to edit or delete it.</p>
    @endif
  </div>

  <x-modal id="sendInvoice" title="Email invoice {{ $i->number }}">
    <form method="post" action="/portal/invoices/{{ $i->id }}/send">@csrf
      <div class="fld"><label>Send to</label>
        @forelse($recipients as $c)<label class="check"><input type="checkbox" name="to[]" value="{{ $c->email }}" checked> {{ $c->name }} <small>{{ $c->email }}</small></label>@empty<p class="hint" style="margin:0">This client has no login email yet. Add one below.</p>@endforelse
      </div>
      <div class="fld"><label>Other email addresses</label><input name="extra" placeholder="accounts@client.com, boss@client.com" maxlength="300"><div class="hint">Separate several addresses with commas.</div></div>
      <div class="fld"><label>Message (optional)</label><textarea name="message" maxlength="1500" style="min-height:90px" placeholder="Hi, please find this month’s invoice attached…"></textarea></div>
      <p class="hint" style="margin-top:0">The PDF is attached. @if($i->draft)Sending also makes this invoice visible to the client.@endif</p>
      <button class="btn primary">Send email</button>
    </form>
  </x-modal>

  @unless($locked)
  <x-modal id="payInvoice" title="Mark receipt · {{ $i->number }}">
    <form method="post" action="/portal/invoices/{{ $i->id }}/paid">@csrf
      <div class="two"><div class="fld"><label>Payment date</label><input type="date" name="paid_on" value="{{ now()->toDateString() }}" required></div>
        <div class="fld"><label>Amount received ({{ $i->currency }})</label><input type="text" inputmode="decimal" name="paid_amount" value="{{ number_format((float) $i->amount, 2, '.', '') }}" required></div></div>
      <div class="fld"><label>Method</label><select name="payment_method"><option value="">—</option>@foreach(Invoice::METHODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
      <div class="fld"><label>Reference (optional)</label><input name="payment_ref" maxlength="120" placeholder="Transfer ID, cheque number…"></div>
      <button class="btn primary">Mark receipt</button>
    </form>
  </x-modal>
  @endunless
@endif

@if($print)
@push('scripts')
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endpush
@endif
@endsection
