@props(['name', 'value' => '', 'height' => 140])
{{-- Quill rich-text editor (bold, italic, underline, lists, link). Saves clean HTML into a hidden field. Behaviour: /js/portal2.js ([data-rte]) --}}
@php $html = \App\Support\Ui::clean($value); @endphp
<div class="rte" data-rte style="--rte-h:{{ $height }}px">
  <div class="rte-host">{!! $html !!}</div>
  <input type="hidden" name="{{ $name }}" value="{{ $html }}">
</div>
