@props([
  'name', 'label' => 'Upload image', 'hint' => 'PNG, JPG or WEBP · up to 10 MB', 'current' => null,
  'multiple' => false, 'removeName' => null, 'accept' => 'image/jpeg,image/png,image/webp', 'shape' => 'square', 'required' => false,
])
{{-- Drag & drop image picker with live preview. Behaviour lives in /js/ui.js ([data-imgin]). --}}
<div class="imgin {{ $shape }} {{ $multiple ? 'multi' : '' }} {{ $current ? 'has' : '' }}" data-imgin @if($current) data-orig="{{ $current }}" @endif>
  <label class="imgin-drop">
    <input type="file" name="{{ $name }}{{ $multiple ? '[]' : '' }}" accept="{{ $accept }}" @if($multiple) multiple @endif @required($required)>
    <span class="imgin-prev">@if($current && ! $multiple)<img src="{{ $current }}" alt="">@endif<span class="imgin-ph">{!! \App\Support\Ui::icon('photo') !!}</span></span>
    <span class="imgin-txt"><b>{{ $label }}</b><small>Drag &amp; drop or <u>browse</u> · {{ $hint }}</small></span>
  </label>
  @if($multiple)<div class="imgin-list"></div>@endif
  @if($removeName && $current && ! $multiple)
    <input type="hidden" name="{{ $removeName }}" value="0" data-imgin-remove>
  @endif
  <button type="button" class="imgin-clear" hidden>{!! \App\Support\Ui::icon('x') !!}<span>{{ $multiple ? 'Clear' : 'Remove' }}</span></button>
</div>
