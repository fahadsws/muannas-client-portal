@props([
  'name', 'label' => 'Attach file', 'hint' => null, 'accept' => null, 'multiple' => false, 'required' => false,
  'current' => null,        {{-- existing file for single mode: ['name' => ..., 'size' => '7.4 KB', 'url' => ...] --}}
  'removeName' => null,     {{-- hidden field set to 1 when the existing file is removed --}}
])
{{-- Files and documents: attached files show as chips with name, size, thumbnail for images and a ✕ to remove. Behaviour: /js/ui.js ([data-fin]). --}}
<div class="fin {{ $multiple ? 'multi' : '' }}" data-fin data-label="{{ $label }}" @if($current) data-current="{{ json_encode($current) }}" @endif>
  <div class="fin-title">{{ $label }}</div>
  <div class="fin-list"></div>
  <input type="file" name="{{ $name }}{{ $multiple ? '[]' : '' }}" @if($accept) accept="{{ $accept }}" @endif @if($multiple) multiple @endif hidden>
  @if($removeName)<input type="hidden" name="{{ $removeName }}" value="0" data-fin-remove>@endif
  <button type="button" class="fin-add">+ File</button>
  @if($hint)<div class="hint">{{ $hint }}</div>@endif
</div>
