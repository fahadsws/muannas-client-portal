{{-- The invoice sheet: picks the agency's chosen template (classic or modern). $tpl can force one, e.g. for the settings preview. --}}
@php
  $tpl = in_array($tpl ?? ($cfg['template'] ?? 'classic'), ['classic', 'modern'], true) ? ($tpl ?? ($cfg['template'] ?? 'classic')) : 'classic';
  $pay = $i->paid_on && $i->payment_method ? (\App\Models\Invoice::METHODS[$i->payment_method] ?? '') : '';
  $hasDisc = $i->lines()->contains(fn ($l) => (float) $l->discount > 0);
  $hasTax = $i->lines()->contains(fn ($l) => ! empty($l->taxes));
  $n = fn ($x) => rtrim(rtrim(number_format((float) $x, 2), '0'), '.');
  $accent = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($agency->brand_color ?? '')) ? $agency->brand_color : '#4f46e5';
  $kind = $cfg['tax_id'] && ! empty($i->tax_breakdown) ? 'Tax invoice' : 'Invoice';
@endphp
@include('portal.partials.invoice-'.$tpl)
