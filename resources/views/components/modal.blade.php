@props(['id', 'title', 'wide' => false])
{{-- Open with any element that has data-modal="{{ $id }}". Behaviour: /js/portal.js --}}
<div class="mdl" id="{{ $id }}" hidden role="dialog" aria-modal="true" aria-label="{{ $title }}">
  <div class="mdl-box {{ $wide ? 'wide' : '' }}">
    <div class="mdl-head"><h3>{{ $title }}</h3><button type="button" class="ibtn" data-close aria-label="Close">{!! \App\Support\Ui::icon('x') !!}</button></div>
    <div class="mdl-body">{{ $slot }}</div>
  </div>
</div>
