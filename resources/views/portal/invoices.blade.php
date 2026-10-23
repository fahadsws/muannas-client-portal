@extends('portal.layout')
@section('title', 'Payments & Invoices')
@section('content')
@php use App\Models\Invoice; use App\Support\Ui; $base = '/portal/c/'.$account->id.'/invoices'; $overdue = $live->filter(fn ($i) => $i->status() === 'overdue' && ! $i->paid_on); @endphp
<div class="pagehead"><div><h1>Payments &amp; Invoices</h1><p>What you owe and what you have paid.</p></div>
  <div class="prow">
    @if($manage)<a class="btn" href="/portal/invoice-settings">{!! Ui::icon('cog') !!}Invoice settings</a><a class="btn primary" href="{{ $base }}/new">{!! Ui::icon('plus') !!}New invoice</a>@endif
    <a class="btn" href="/portal/c/{{ $account->id }}/payments">{!! Ui::icon('receipt') !!}Payment history</a>
  </div></div>

<div class="grid g3" style="margin-bottom:20px">
  <div class="kpi"><small>Outstanding</small><b>{{ $currency }} {{ number_format($outstanding, 2) }}</b></div>
  <div class="kpi"><small>Overdue invoices</small><b style="color:{{ $overdue->count() ? 'var(--red)' : 'var(--ink)' }}">{{ $overdue->count() }}</b></div>
  <div class="kpi"><small>Paid to date</small><b>{{ $currency }} {{ number_format($paid->sum('amount'), 2) }}</b></div>
</div>

<div class="pseg" style="margin-bottom:16px">
  <a class="{{ ! $filter ? 'on' : '' }}" href="{{ $base }}">All<em>{{ $all->count() }}</em></a>
  @foreach(Invoice::STATUSES as $k => $s)<a class="{{ $filter === $k ? 'on' : '' }}" href="{{ $base }}?status={{ $k }}" title="{{ $s[1] }}">{{ $s[0] }}<em>{{ $live->filter(fn ($i) => $i->status() === $k)->count() }}</em></a>@endforeach
  @if($manage)<a class="{{ $filter === 'draft' ? 'on' : '' }}" href="{{ $base }}?status=draft" title="Not sent to the client yet">Draft<em>{{ $all->where('draft', true)->count() }}</em></a>@endif
</div>

<div class="pn">
  @if($invoices->isEmpty())
    <div class="empty"><h3>No invoices</h3><p>{{ $filter ? 'None with this status.' : ($manage ? 'Create the first invoice for this client.' : 'Invoices will appear here when they are issued.') }}</p>
      @if($manage && ! $filter)<a class="btn primary" href="{{ $base }}/new">{!! Ui::icon('plus') !!}New invoice</a>@endif</div>
  @else
  <div class="tblwrap"><table class="tbl stack-m">
    <thead><tr><th>Invoice</th><th>Issued</th><th>Due</th><th class="r">Amount</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($invoices as $i)
      <tr>
        <td data-l="Invoice"><a href="/portal/invoices/{{ $i->id }}" style="text-decoration:none;color:inherit"><b>{{ $i->number }}</b></a>@if($i->title)<br><small>{{ $i->title }}</small>@endif</td>
        <td data-l="Issued">{{ $i->issued_on->format('j M Y') }}</td>
        <td data-l="Due">{{ $i->due_on->format('j M Y') }}@if($i->paid_on)<br><small>Paid {{ $i->paid_on->format('j M Y') }}</small>@endif</td>
        <td data-l="Amount" class="r"><b>{{ $i->money() }}</b></td>
        <td data-l="Status"><span class="stag s-{{ $i->statusKey() }}">{{ $i->statusLabel() }}</span></td>
        <td class="r">
          <div class="prow" style="justify-content:flex-end;gap:6px">
            <a class="btn sm" href="/portal/invoices/{{ $i->id }}">{!! Ui::icon('eye') !!}View</a>
            <a class="btn sm" href="/portal/invoices/{{ $i->id }}/pdf">{!! Ui::icon('dl') !!}PDF</a>
            @if($manage && $i->editable())<a class="ibtn" href="/portal/invoices/{{ $i->id }}/edit" title="Edit" aria-label="Edit">{!! Ui::icon('edit') !!}</a>@endif
            @if($manage)
              @unless($i->paid_on)<form method="post" action="/portal/invoices/{{ $i->id }}/paid" style="margin:0">@csrf<button class="btn sm ok">Mark paid</button></form>@endunless
              @if($i->editable())<form method="post" action="/portal/invoices/{{ $i->id }}" data-confirm="Delete invoice {{ $i->number }}?" style="margin:0">@csrf @method('DELETE')<button class="ibtn" aria-label="Delete">{!! Ui::icon('trash') !!}</button></form>@endif
            @endif
          </div>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>

@include('partials.pager', ['p' => $invoices])

@if($recentPaid->isNotEmpty() && ! $filter)
  <div class="pn" style="margin-top:18px">
    <div class="prow between" style="margin-bottom:6px"><h3 style="margin:0">Payment history</h3><a class="btn sm" href="/portal/c/{{ $account->id }}/payments">View full history</a></div>
    @foreach($recentPaid as $i)
      <div class="file"><div class="nm"><b>{{ $i->number }}</b><small>Paid {{ $i->paid_on->format('j M Y') }}@if($i->payment_method) · {{ Invoice::METHODS[$i->payment_method] ?? '' }}@endif</small></div><b>{{ $i->money() }}</b></div>
    @endforeach
    @if($paid->count() > $recentPaid->count())<p class="hint" style="margin:10px 0 0">Showing the latest {{ $recentPaid->count() }} of {{ $paid->count() }} payments.</p>@endif
  </div>
@endif
@endsection
