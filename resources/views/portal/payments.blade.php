@extends('portal.layout')
@section('title', 'Payment history')
@section('crumb', 'Payments & Invoices')
@section('content')
@php use App\Models\Invoice; use App\Support\Ui; $base = '/portal/c/'.$account->id; @endphp
<style>
.tl{position:relative;margin-left:8px;padding-left:26px;border-left:2px solid var(--border)}
.tl .mh{display:flex;justify-content:space-between;align-items:baseline;margin:22px 0 10px -26px;padding-left:26px;font-weight:650}
.tl .mh:first-child{margin-top:0}.tl .mh small{color:var(--mute);font-weight:500;font-size:12.5px}
.tl .ev{position:relative;display:flex;align-items:center;gap:14px;padding:12px 16px;margin-bottom:10px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm)}
.tl .ev:before{content:'';position:absolute;left:-34px;top:50%;width:12px;height:12px;margin-top:-6px;border-radius:50%;background:#16a34a;border:3px solid #fff;box-shadow:0 0 0 2px #bbf7d0}
.tl .ev .dt{width:62px;flex:none;text-align:center;line-height:1.15}.tl .ev .dt b{display:block;font-size:20px}.tl .ev .dt small{color:var(--mute);font-size:11px;text-transform:uppercase;letter-spacing:.04em}
.tl .ev .nm{flex:1;min-width:0}.tl .ev .nm b a{color:inherit;text-decoration:none}.tl .ev .nm small{display:block;color:var(--mute);font-size:12px}
.tl .ev .amt{font-weight:700;font-variant-numeric:tabular-nums;white-space:nowrap}
</style>
<a class="back" href="{{ $base }}/invoices">{!! Ui::icon('arrow') !!}All invoices</a>
<div class="pagehead"><div><h1>Payment history</h1><p>Every payment received for {{ $account->name }}, newest first.</p></div>
  <form method="get" action="{{ $base }}/payments" class="prow" id="rangeform" style="gap:8px">
    <input type="hidden" name="from" value="{{ request('from') }}"><input type="hidden" name="to" value="{{ request('to') }}">
    <div class="rangewrap">{!! Ui::icon('cal') !!}<input type="text" data-range placeholder="Paid between"></div>
    @if(request('from') || request('to'))<a class="btn grey sm" href="{{ $base }}/payments">{!! Ui::icon('x') !!}Clear</a>@endif
  </form></div>

<div class="grid g3" style="margin-bottom:20px">
  <div class="kpi"><small>Total received</small><b>{{ $currency }} {{ number_format($total, 2) }}</b></div>
  <div class="kpi"><small>Payments</small><b>{{ $count }}</b></div>
  <div class="kpi"><small>Latest payment</small><b class="sm">{{ $payments->first()?->paid_on?->format('j M Y') ?? '—' }}</b></div>
</div>

@if($payments->isEmpty())
  <div class="empty"><h3>No payments yet</h3><p>{{ request('from') || request('to') ? 'No payments in this date range.' : 'Paid invoices will appear here as a timeline.' }}</p></div>
@else
  <div class="tl">
    @foreach($months as $ym => $items)
      @php $d = \Carbon\Carbon::createFromFormat('Y-m', $ym); @endphp
      <div class="mh"><span>{{ $d->format('F Y') }}</span><small>{{ $currency }} {{ number_format($monthTotals[$ym] ?? 0, 2) }} received this month</small></div>
      @foreach($items as $i)
        <div class="ev">
          <div class="dt"><b>{{ $i->paid_on->format('j') }}</b><small>{{ $i->paid_on->format('D') }}</small></div>
          <div class="nm"><b><a href="/portal/invoices/{{ $i->id }}">{{ $i->number }}</a></b>
            <small>{{ $i->title ?: 'Invoice' }}@if($i->payment_method) · {{ Invoice::METHODS[$i->payment_method] ?? '' }}@endif @if($i->payment_ref) · Ref {{ $i->payment_ref }}@endif</small></div>
          <div class="amt">{{ $i->money() }}</div>
          <a class="btn sm" href="/portal/invoices/{{ $i->id }}">{!! Ui::icon('eye') !!}View</a>
        </div>
      @endforeach
    @endforeach
  </div>
  @include('partials.pager', ['p' => $payments])
@endif

@push('scripts')
<script>
  var rg = document.querySelector('#rangeform input[data-range]');
  if (rg) rg.addEventListener('change', function () { setTimeout(function () { document.getElementById('rangeform').requestSubmit(); }, 80); });
</script>
@endpush
@endsection
