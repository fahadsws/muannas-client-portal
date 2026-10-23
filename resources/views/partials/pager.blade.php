@php
  use App\Support\Ui;
  $last = $p->lastPage(); $cur = $p->currentPage(); $pages = [];
  foreach (range(1, max(1, $last)) as $n) { if ($n === 1 || $n === $last || abs($n - $cur) <= 1) $pages[] = $n; elseif (end($pages) !== '…') $pages[] = '…'; }
@endphp
@if($p->total() > 0)
<div class="pager">
  <div class="pinfo">Showing <b>{{ $p->firstItem() }}–{{ $p->lastItem() }}</b> of <b>{{ $p->total() }}</b></div>
  @if($last > 1)<nav class="pnums">
    @if($p->onFirstPage())<span class="pg off">{!! Ui::icon('chev-l') !!}</span>@else<a class="pg" href="{{ $p->previousPageUrl() }}">{!! Ui::icon('chev-l') !!}</a>@endif
    @foreach($pages as $n)@if($n === '…')<span class="pg gap">…</span>@elseif($n === $cur)<span class="pg on">{{ $n }}</span>@else<a class="pg" href="{{ $p->url($n) }}">{{ $n }}</a>@endif @endforeach
    @if($p->hasMorePages())<a class="pg" href="{{ $p->nextPageUrl() }}">{!! Ui::icon('chev-r') !!}</a>@else<span class="pg off">{!! Ui::icon('chev-r') !!}</span>@endif
  </nav>@endif
</div>
@endif
