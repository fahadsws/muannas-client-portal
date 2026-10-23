@extends('admin.layout')
@section('title', 'Dashboard')
@section('crumb')<b>Dashboard</b>@endsection

@php
  use App\Support\Ui;
  $t = $d['totals']; $st = $d['status']; $tot = array_sum($st);
  $mx = max(1, collect($d['months'])->max('n'));
  $col = ['todo' => '#cbd0ee', 'ready' => '#f59e0b', 'done' => '#4f46e5', 'live' => '#10b981'];
  $label = ['todo' => 'To do', 'ready' => 'Ready', 'done' => 'Done', 'live' => 'Live'];
  $C = 2 * M_PI * 52; $off = 0;
@endphp

@section('content')
<div class="pagehead"><div><h1>Dashboard</h1><p>How the platform is doing right now. Click any card or figure to dig in.</p></div>
  <a class="btn" href="/admin/reports">{!! Ui::icon('dl') !!}Export summary</a></div>

<div class="kpis">
  <a class="kpi click" href="/admin/agencies"><small>Agencies</small><b>{{ $t['agencies'] }}</b>
    <div class="sub"><span class="lnk" data-href="/admin/agencies?status=active">{{ $t['active'] }} active</span> · <span class="lnk" data-href="/admin/agencies?status=suspended">{{ $t['suspended'] }} suspended</span></div>
    <div class="meter"><i style="width:{{ $t['agencies'] ? ($t['active'] / $t['agencies']) * 100 : 0 }}%"></i></div></a>
  <a class="kpi click" href="/admin/agencies?order=workspaces:desc"><small>Clients</small><b>{{ $t['workspaces'] }}</b><div class="sub">across all agencies · most first</div><div class="meter"><i style="width:100%"></i></div></a>
  <a class="kpi click" href="/admin/agencies?order=members:desc"><small>People</small><b>{{ $t['users'] }}</b><div class="sub">{{ $t['guests'] }} guest{{ $t['guests'] == 1 ? '' : 's' }} with shared access</div><div class="meter"><i style="width:100%"></i></div></a>
  <a class="kpi click" href="/admin/agencies?order=items:desc"><small>Content items</small><b>{{ $t['items'] }}</b><div class="sub">{{ $t['completion'] }}% complete · most first</div><div class="meter"><i class="g" style="width:{{ $t['completion'] }}%"></i></div></a>
</div>

<div class="grid2">
  <div class="pn"><div class="phead"><div><h3>New agencies</h3><p style="margin:0">Last 6 months · click a bar to see them</p></div></div>
    <div class="vbars">
      @foreach($d['months'] as $m)
        <a class="vb {{ $m['n'] ? '' : 'off' }}" @if($m['n']) href="/admin/agencies?from={{ $m['from'] }}&to={{ $m['to'] }}" @endif><b>{{ $m['n'] }}</b><i class="{{ $m['n'] ? '' : 'z' }}" style="height:{{ $m['n'] ? max(8, ($m['n'] / $mx) * 100) : 3 }}%"></i><span>{{ $m['label'] }}</span></a>
      @endforeach
    </div></div>
  <div class="pn"><div class="phead"><div><h3>Content status</h3><p style="margin:0">All agencies combined</p></div><a class="btn sm ghost" href="/admin/agencies?order=items:desc">By agency</a></div>
    <div class="donutwrap"><div class="donut"><svg viewBox="0 0 132 132">
      @if($tot) @foreach($col as $k => $c) @php $len = ($st[$k] / $tot) * $C; @endphp<circle cx="66" cy="66" r="52" stroke="{{ $c }}" stroke-dasharray="{{ $len }} {{ $C - $len }}" stroke-dashoffset="{{ -$off }}"/>@php $off += $len; @endphp @endforeach
      @else<circle cx="66" cy="66" r="52" stroke="#eee"/>@endif
      </svg><div class="c"><div><b>{{ $tot }}</b><small>items</small></div></div></div>
      <div class="lg2">@foreach($label as $k => $l)<div><i style="background:{{ $col[$k] }}"></i>{{ $l }}<b>{{ $st[$k] }}</b></div>@endforeach</div></div></div>
</div>

<div class="grid3x">
  @php $list = fn ($rows, $fn) => $rows; @endphp
  <div class="pn"><div class="phead"><div><h3>Recent agencies</h3></div><a class="btn sm ghost" href="/admin/agencies">View all</a></div>
    <div class="rowlist">@forelse($d['recent'] as $a)<a class="rl" href="/admin/agencies/{{ $a['id'] }}">{!! Ui::avatar($a['name']) !!}<div><b>{{ $a['name'] }}</b><small>{{ $a['owner'] }}</small></div><div class="r">{{ $a['created'] }}</div></a>@empty<p class="sub-note" style="margin:0">Nothing here yet.</p>@endforelse</div></div>
  <div class="pn"><div class="phead"><div><h3>Most content</h3></div><a class="btn sm ghost" href="/admin/agencies?order=items:desc">View all</a></div>
    <div class="rowlist">@forelse($d['top'] as $a)<a class="rl" href="/admin/agencies/{{ $a['id'] }}">{!! Ui::avatar($a['name']) !!}<div><b>{{ $a['name'] }}</b><small>{{ $a['owner'] }}</small></div><div class="r">{{ $a['items'] }} items</div></a>@empty<p class="sub-note" style="margin:0">Nothing here yet.</p>@endforelse</div></div>
  <div class="pn"><div class="phead"><div><h3>Needs attention</h3></div><a class="btn sm ghost" href="/admin/agencies?usage=limit">View all</a></div>
    <div class="rowlist">@forelse($d['limits'] as $a)<a class="rl" href="/admin/agencies/{{ $a['id'] }}">{!! Ui::avatar($a['name']) !!}<div><b>{{ $a['name'] }}</b><small>{{ $a['workspaces'] }}/{{ $a['max_workspaces'] }} workspaces · {{ $a['members'] }}/{{ $a['max_members'] }} team</small></div><div class="r"><span class="warn">At limit</span></div></a>@empty<p class="sub-note" style="margin:0">No agency is at its limits.</p>@endforelse</div></div>
</div>
@endsection
