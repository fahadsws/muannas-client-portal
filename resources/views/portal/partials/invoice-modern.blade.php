{{-- Modern: colour header band in the agency's brand colour, clean sans-serif, soft panels. Tables only, so screen, print and PDF match. --}}
<style>
.ip.modern,.ip.modern *{font-family:'DejaVu Sans',Inter,Arial,sans-serif}
.ip.modern{background:#fff;color:#1f2433;font-size:12.5px;line-height:1.55;padding:0;border-top:0}
.ip.modern table{width:100%;border-collapse:collapse}.ip.modern td,.ip.modern th{padding:0}
.ip.modern .mut{color:#6b7385}.ip.modern .r{text-align:right}.ip.modern .s{font-size:11px}
.ip.modern .band{background:{{ $accent }};color:#fff;padding:30px 40px}
.ip.modern .band *{color:#fff}
.ip.modern .brand{font-size:19px;font-weight:700;margin:0 0 3px}
.ip.modern .band .s{opacity:.85}
.ip.modern .kind{font-size:30px;font-weight:700;line-height:1.05}
.ip.modern .no{font-size:13px;margin-top:5px;opacity:.9}
.ip.modern .body{padding:26px 40px 32px}
.ip.modern .pill{display:inline-block;margin-top:8px;padding:3px 12px;border-radius:12px;background:#ffffff;font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase}
.ip.modern .band .pill{color:{{ $accent }}}
.ip.modern .cards td{vertical-align:top;background:#f5f6fa;padding:13px 15px}
.ip.modern .cards td.gap{background:#fff;width:10px;padding:0}
.ip.modern .lbl{font-size:9.5px;font-weight:700;color:{{ $accent }};text-transform:uppercase;letter-spacing:1px;margin-bottom:4px}
.ip.modern .val{font-size:13px;font-weight:700}
.ip.modern .lines{margin-top:24px}
.ip.modern .lines th{text-align:left;font-size:9.5px;font-weight:700;color:{{ $accent }};text-transform:uppercase;letter-spacing:1px;padding:8px 10px;border-bottom:2px solid {{ $accent }}}
.ip.modern .lines td{padding:12px 10px;border-bottom:1px solid #e8eaf0;vertical-align:top}
.ip.modern .lines th.r,.ip.modern .lines td.r{text-align:right;white-space:nowrap}
.ip.modern .lines small{display:block;color:#6b7385;font-size:10.5px;margin-top:2px}
.ip.modern .tot td{padding:6px 12px;font-size:12.5px}
.ip.modern .tot .g td{background:{{ $accent }};color:#fff;font-weight:700;font-size:14.5px;padding:12px}
.ip.modern .paid{margin-top:16px;padding:10px 14px;background:#e6f4ef;color:#0f6b5c;font-weight:700;font-size:12px}
.ip.modern .fb{margin-top:12px;padding:12px 15px;background:#f5f6fa;width:100%;font-size:11px;border-left:3px solid {{ $accent }}}
.ip.modern .fb h4{margin:0 0 3px;font-size:9.5px;letter-spacing:1px;text-transform:uppercase;color:{{ $accent }}}
.ip.modern .fb p{margin:0}.ip.modern .rt p{margin:0 0 4px}.ip.modern .rt ul,.ip.modern .rt ol{margin:2px 0 4px 16px;padding:0}
</style>
<div class="ip modern">
  <div class="band"><table><tr>
    <td style="vertical-align:middle;width:58%">
      @if($logo)<img src="{{ $logo }}" alt="" style="max-height:48px;max-width:180px;margin-bottom:8px;background:#fff;padding:4px"><br>@endif
      <div class="brand">{{ $seller }}</div>
      <div class="s">
        @if($cfg['address']){!! nl2br(e($cfg['address'])) !!}<br>@endif
        @if($cfg['tax_id']){{ $cfg['tax_id'] }}<br>@endif
        {{ $cfg['email'] }}@if($cfg['email'] && $cfg['phone']) · @endif{{ $cfg['phone'] }}
      </div>
    </td>
    <td class="r" style="vertical-align:middle"><div class="kind">{{ $kind }}</div><div class="no">{{ $i->number }}</div><span class="pill">{{ $i->statusLabel() }}</span></td>
  </tr></table></div>

  <div class="body">
    <table class="cards"><tr>
      <td style="width:34%"><div class="lbl">Billed to</div><div class="val">{{ $client->name }}</div>
        <div class="s mut">@if($client->billing_address){!! nl2br(e($client->billing_address)) !!}<br>@endif @if($client->tax_id){{ $client->tax_id }}@endif</div></td>
      <td class="gap"></td>
      <td style="width:17%"><div class="lbl">Invoice date</div><div class="val">{{ $i->issued_on->format('j M Y') }}</div></td>
      <td class="gap"></td>
      <td style="width:17%"><div class="lbl">Due date</div><div class="val">{{ $i->due_on->format('j M Y') }}</div></td>
      <td class="gap"></td>
      <td><div class="lbl">For</div><div class="val">{{ $i->title ?: ($i->project?->name ?? '—') }}</div>@if($i->title && $i->project)<div class="s mut">{{ $i->project->name }}</div>@endif</td>
    </tr></table>

    <table class="lines">
      <thead><tr><th>{{ $cfg['label_item'] }}</th><th class="r">{{ $cfg['label_qty'] }}</th><th class="r">{{ $cfg['label_rate'] }}</th>@if($hasDisc)<th class="r">{{ $cfg['label_discount'] }}</th>@endif @if($hasTax)<th class="r">{{ $cfg['label_tax'] }}</th>@endif<th class="r">{{ $cfg['label_amount'] }}</th></tr></thead>
      <tbody>
        @foreach($i->lines() as $l)
          <tr><td style="width:40%"><b>{{ $l->description }}</b>@if($l->details)<small>{!! nl2br(e($l->details)) !!}</small>@endif</td>
            <td class="r">{{ $n($l->qty) }}</td><td class="r">{{ number_format((float) $l->unit_price, 2) }}</td>
            @if($hasDisc)<td class="r">@if((float) $l->discount > 0){{ number_format((float) $l->discount, 2) }}@if($l->discount_type === 'percent')<small>{{ $n($l->discount_value) }}%</small>@endif @else—@endif</td>@endif
            @if($hasTax)<td class="r">@forelse($l->taxes ?? [] as $t){{ $t['name'] }} {{ $n($t['rate']) }}%@if(! $loop->last)<br>@endif @empty—@endforelse</td>@endif
            <td class="r"><b>{{ number_format($l->total(), 2) }}</b></td></tr>
        @endforeach
      </tbody>
    </table>

    <table style="margin-top:16px"><tr><td style="width:48%"></td><td>
      <table class="tot">
        <tr><td>Subtotal</td><td class="r">{{ number_format((float) ($i->items->isEmpty() ? $i->amount : $i->subtotal), 2) }}</td></tr>
        @if((float) $i->discount > 0)<tr><td>Discount{{ $i->discount_type === 'percent' ? ' ('.$n($i->discount_value).'%)' : '' }}</td><td class="r">−{{ number_format((float) $i->discount, 2) }}</td></tr>@endif
        @foreach($i->tax_breakdown ?? [] as $t)<tr><td>{{ $t['name'] }} {{ $n($t['rate']) }}%</td><td class="r">{{ number_format((float) $t['amount'], 2) }}</td></tr>@endforeach
        <tr class="g"><td>{{ $i->paid_on ? 'Total' : 'Total due' }}</td><td class="r">{{ $i->money() }}</td></tr>
      </table>
    </td></tr></table>
    @if($i->paid_on)<div class="paid">Paid on {{ $i->paid_on->format('j M Y') }}@if($pay) · {{ $pay }}@endif @if($i->payment_ref) · Ref {{ $i->payment_ref }}@endif @if($i->paid_amount !== null)· Received {{ $i->money((float) $i->paid_amount) }}@endif</div>@endif

    <div style="margin-top:14px">
      @if($cfg['bank'])<div class="fb"><h4>Pay to</h4><p>{!! nl2br(e($cfg['bank'])) !!}</p></div>@endif
      @if($i->terms)<div class="fb"><h4>Terms</h4><div class="rt">{!! \App\Support\Ui::clean($i->terms) !!}</div></div>@endif
      @if($i->notes)<div class="fb"><h4>Notes</h4><div class="rt">{!! \App\Support\Ui::clean($i->notes) !!}</div></div>@endif
    </div>
  </div>
</div>
