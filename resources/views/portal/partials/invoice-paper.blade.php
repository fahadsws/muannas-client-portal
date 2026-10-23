{{-- The invoice sheet. Table layout and plain CSS on purpose: the same markup is used on screen, for print and by the PDF engine. --}}
<style>
.ip{background:#fff;color:#1a2238;font-family:DejaVu Sans,Inter,Arial,sans-serif;font-size:13px;line-height:1.55;border-top:7px solid #1a2238;padding:40px 44px 36px}
.ip table{width:100%;border-collapse:collapse}
.ip td,.ip th{padding:0}
.ip-mut{color:#6b7385}.ip-s{font-size:11.5px;line-height:1.55}.ip-r{text-align:right}
.ip-brand{font-size:20px;font-weight:700;letter-spacing:-.2px;margin:0 0 4px}
.ip-kind{font-size:30px;font-weight:700;letter-spacing:1px;line-height:1.05;text-transform:uppercase;color:#1a2238}
.ip-no{font-size:14px;font-weight:600;margin-top:6px;color:#3d4660}
.ip-chip{display:inline-block;margin-top:8px;font-size:11px;font-weight:700;padding:3px 12px;border-radius:12px;background:#e4e7ed;color:#4a5568;text-transform:uppercase;letter-spacing:.5px}
.ip-chip.paid{background:#ddf1ec;color:#0f6b5c}.ip-chip.pending,.ip-chip.due_soon{background:#fbebcb;color:#9a5b00}.ip-chip.overdue{background:#fbe3df;color:#b42318}
.ip-info{margin:26px 0 24px;border-top:1px solid #e3e6ec;border-bottom:1px solid #e3e6ec}
.ip-info td{vertical-align:top;padding:16px 14px 16px 0}
.ip-lbl{font-size:10.5px;font-weight:700;color:#8a91a3;text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px}
.ip-val{font-size:13.5px;font-weight:600}
.ip-lines th{text-align:left;font-size:10.5px;font-weight:700;color:#6b7385;text-transform:uppercase;letter-spacing:.7px;padding:10px 10px;border-bottom:2px solid #1a2238;background:#f6f7fa}
.ip-lines td{padding:13px 10px;border-bottom:1px solid #e9ebf0;vertical-align:top}
.ip-lines th.ip-r,.ip-lines td.ip-r{text-align:right;white-space:nowrap}
.ip-lines td small{display:block;color:#6b7385;font-size:11.5px;margin-top:2px}
.ip-tot td{padding:7px 12px;font-size:13px}
.ip-tot .ip-g td{background:#1a2238;color:#fff;font-weight:700;font-size:15px;padding:12px}
.ip-paid{margin-top:16px;padding:10px 14px;background:#ddf1ec;color:#0f6b5c;font-weight:600;border-radius:4px;font-size:12.5px}
.ip-foot{margin-top:30px;border-top:1px solid #e3e6ec}
.ip-foot td{vertical-align:top;padding:16px 18px 0 0;font-size:11.5px;width:33%}
.ip-foot h4{margin:0 0 4px;font-size:10.5px;text-transform:uppercase;letter-spacing:.7px;color:#8a91a3}.ip-foot p{margin:0;color:#3d4660}
</style>
@php
  $pay = $i->paid_on && $i->payment_method ? (\App\Models\Invoice::METHODS[$i->payment_method] ?? '') : '';
  $hasDisc = $i->lines()->contains(fn ($l) => (float) $l->discount > 0);
  $hasTax = $i->lines()->contains(fn ($l) => ! empty($l->taxes));
  $n = fn ($x) => rtrim(rtrim(number_format((float) $x, 2), '0'), '.');
@endphp
<div class="ip">
  <table><tr>
    <td style="vertical-align:top;width:58%">
      @if($logo)<img src="{{ $logo }}" alt="" style="max-height:56px;max-width:190px;margin-bottom:10px"><br>@endif
      <div class="ip-brand">{{ $seller }}</div>
      <div class="ip-s ip-mut">
        @if($cfg['address']){!! nl2br(e($cfg['address'])) !!}<br>@endif
        @if($cfg['tax_id']){{ $cfg['tax_id'] }}<br>@endif
        {{ $cfg['email'] }}@if($cfg['email'] && $cfg['phone']) · @endif{{ $cfg['phone'] }}
      </div>
    </td>
    <td class="ip-r" style="vertical-align:top"><div class="ip-kind">{{ $cfg['tax_id'] && ! empty($i->tax_breakdown) ? 'Tax invoice' : 'Invoice' }}</div><div class="ip-no">{{ $i->number }}</div><span class="ip-chip {{ $i->statusKey() }}">{{ $i->statusLabel() }}</span></td>
  </tr></table>

  <table class="ip-info"><tr>
    <td style="width:36%"><div class="ip-lbl">Billed to</div><div class="ip-val">{{ $client->name }}</div>
      <div class="ip-s ip-mut">@if($client->billing_address){!! nl2br(e($client->billing_address)) !!}<br>@endif @if($client->tax_id){{ $client->tax_id }}@endif</div></td>
    <td style="width:20%"><div class="ip-lbl">Invoice date</div><div class="ip-val">{{ $i->issued_on->format('j M Y') }}</div></td>
    <td style="width:20%"><div class="ip-lbl">Due date</div><div class="ip-val">{{ $i->due_on->format('j M Y') }}</div></td>
    <td><div class="ip-lbl">For</div><div class="ip-val">{{ $i->title ?: ($i->project?->name ?? '—') }}</div>@if($i->title && $i->project)<div class="ip-s ip-mut">{{ $i->project->name }}</div>@endif</td>
  </tr></table>

  <table class="ip-lines">
    <thead><tr><th>{{ $cfg['label_item'] }}</th><th class="ip-r">{{ $cfg['label_qty'] }}</th><th class="ip-r">{{ $cfg['label_rate'] }}</th>@if($hasDisc)<th class="ip-r">{{ $cfg['label_discount'] }}</th>@endif @if($hasTax)<th class="ip-r">{{ $cfg['label_tax'] }}</th>@endif<th class="ip-r">{{ $cfg['label_amount'] }}</th></tr></thead>
    <tbody>
      @foreach($i->lines() as $l)
        <tr><td style="width:40%">{{ $l->description }}@if($l->details)<small>{!! nl2br(e($l->details)) !!}</small>@endif</td>
          <td class="ip-r">{{ $n($l->qty) }}</td><td class="ip-r">{{ number_format((float) $l->unit_price, 2) }}</td>
          @if($hasDisc)<td class="ip-r">@if((float) $l->discount > 0){{ number_format((float) $l->discount, 2) }}@if($l->discount_type === 'percent')<small>{{ $n($l->discount_value) }}%</small>@endif @else—@endif</td>@endif
          @if($hasTax)<td class="ip-r">@forelse($l->taxes ?? [] as $t){{ $t['name'] }} {{ $n($t['rate']) }}%@if(! $loop->last)<br>@endif @empty—@endforelse</td>@endif
          <td class="ip-r">{{ number_format($l->total(), 2) }}</td></tr>
      @endforeach
    </tbody>
  </table>

  <table style="margin-top:18px"><tr><td style="width:50%"></td><td>
    <table class="ip-tot">
      <tr><td>Subtotal</td><td class="ip-r">{{ number_format((float) ($i->items->isEmpty() ? $i->amount : $i->subtotal), 2) }}</td></tr>
      @if((float) $i->discount > 0)<tr><td>Discount{{ $i->discount_type === 'percent' ? ' ('.$n($i->discount_value).'%)' : '' }}</td><td class="ip-r">−{{ number_format((float) $i->discount, 2) }}</td></tr>@endif
      @foreach($i->tax_breakdown ?? [] as $t)<tr><td>{{ $t['name'] }} {{ $n($t['rate']) }}%</td><td class="ip-r">{{ number_format((float) $t['amount'], 2) }}</td></tr>@endforeach
      <tr class="ip-g"><td>{{ $i->paid_on ? 'Total' : 'Total due' }}</td><td class="ip-r">{{ $i->money() }}</td></tr>
    </table>
  </td></tr></table>
  @if($i->paid_on)<div class="ip-paid">Paid on {{ $i->paid_on->format('j M Y') }}@if($pay) · {{ $pay }}@endif @if($i->payment_ref) · Ref {{ $i->payment_ref }}@endif</div>@endif

  @if($cfg['bank'] || $i->terms || $i->notes)
  <table class="ip-foot"><tr>
    @if($cfg['bank'])<td><h4>Pay to</h4><p>{!! nl2br(e($cfg['bank'])) !!}</p></td>@endif
    @if($i->terms)<td><h4>Terms</h4><p>{!! nl2br(e($i->terms)) !!}</p></td>@endif
    @if($i->notes)<td><h4>Notes</h4><p>{!! nl2br(e($i->notes)) !!}</p></td>@endif
  </tr></table>
  @endif
</div>
