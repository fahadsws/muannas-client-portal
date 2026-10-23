@extends('portal.layout')
@section('title', 'Content Calendar')
@section('content')
@php use App\Models\Post; $base = '/portal/c/'.$account->id.'/calendar'; $manage = auth()->user()->isStaff(); @endphp
<div class="pagehead">
  <div><h1>Content Calendar</h1><p>{{ $month->format('F Y') }} · all posts, colour-coded by status.</p></div>
  <div class="prow">
    <a class="btn sm" href="{{ $base }}?month={{ $prev }}">← {{ \Carbon\Carbon::createFromFormat('Y-m', $prev)->format('M') }}</a>
    <a class="btn sm" href="{{ $base }}">Today</a>
    <a class="btn sm" href="{{ $base }}?month={{ $next }}">{{ \Carbon\Carbon::createFromFormat('Y-m', $next)->format('M') }} →</a>
    @if($manage)<a class="btn primary sm" href="/portal/c/{{ $account->id }}/content/new">{!! \App\Support\Ui::icon('plus') !!}New post</a>@endif
  </div>
</div>
<div class="legend" style="margin-bottom:14px">
  @foreach(Post::STATUSES as $k => $s)@continue(! $manage && $k === 'draft')<span class="stag s-{{ $k }}" title="{{ $s[1] }}">{{ $s[0] }}</span>@endforeach
</div>

<div class="cal">
  @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)<div class="dh">{{ $d }}</div>@endforeach
  @foreach($weeks as $week)@foreach($week as $day)
    @php $list = $posts[$day->toDateString()] ?? collect(); @endphp
    <div class="cday {{ $day->month !== $month->month ? 'out' : '' }} {{ $day->isToday() ? 'today' : '' }}">
      <span class="num">{{ $day->day }}</span>
      @foreach($list->take(4) as $p)<a class="cchip {{ $p->status }}" href="/portal/posts/{{ $p->id }}" title="{{ $p->title }} — {{ $p->statusLabel() }}">{{ $p->title }}</a>@endforeach
      @if($list->count() > 4)<small>+{{ $list->count() - 4 }} more</small>@endif
    </div>
  @endforeach @endforeach
</div>

<div class="agenda pn">
  @php $any = false; @endphp
  @foreach($weeks as $week)@foreach($week as $day)
    @php $list = $posts[$day->toDateString()] ?? collect(); @endphp
    @if($list->isNotEmpty() && $day->month === $month->month)
      @php $any = true; @endphp
      <div class="ad"><b>{{ $day->format('D j M') }}</b>@foreach($list as $p)<a class="cchip {{ $p->status }}" href="/portal/posts/{{ $p->id }}" style="white-space:normal">{{ $p->title }} · {{ $p->statusLabel() }}</a>@endforeach</div>
    @endif
  @endforeach @endforeach
  @unless($any)<p class="mute" style="margin:0">Nothing planned this month.</p>@endunless
</div>
@if($unscheduled)<p class="mute" style="margin-top:14px">{{ $unscheduled }} {{ \Illuminate\Support\Str::plural('post', $unscheduled) }} without a planned date are not shown here. <a href="/portal/c/{{ $account->id }}/content">See all content</a></p>@endif
@endsection
