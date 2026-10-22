@extends('admin.layout')
@section('title', 'Agencies')
@section('crumb')<b>Agencies</b>@endsection

@php
  use App\Support\Ui;
  $q = request('q', ''); $status = request('status', 'all'); $order = request('order', 'created:desc'); $usage = request('usage', 'any');
  [$sk, $sd] = array_pad(explode(':', $order), 2, 'desc');
  $filtered = $q !== '' || $status !== 'all' || request('from') || request('to') || $usage !== 'any';
  $sorts = ['created:desc' => 'Newest first', 'created:asc' => 'Oldest first', 'name:asc' => 'Name A–Z', 'name:desc' => 'Name Z–A', 'owner:asc' => 'Owner A–Z', 'workspaces:desc' => 'Most workspaces', 'members:desc' => 'Largest team', 'items:desc' => 'Most content', 'status:asc' => 'Status'];
  $usages = ['any' => 'Any usage', 'limit' => 'At a limit', 'none' => 'No workspaces yet', 'some' => 'Has workspaces'];
  $th = function ($key, $label) use ($sk, $sd) {
      $on = $sk === $key; $next = $on ? ($sd === 'asc' ? 'desc' : 'asc') : (in_array($key, ['name', 'owner', 'status']) ? 'asc' : 'desc');
      $arrow = $on ? ($sd === 'asc' ? '▲' : '▼') : '↕';
      return '<th class="sortable '.($on ? 'on' : '').'"><a href="'.e(Ui::with(['order' => "$key:$next", 'page' => null])).'">'.$label.'<span class="arr">'.$arrow.'</span></a></th>';
  };
  // numbered page window: 1 … 4 5 [6] 7 8 … 20
  $pages = []; $last = $p->lastPage(); $cur = $p->currentPage();
  foreach (range(1, $last) as $n) { if ($n === 1 || $n === $last || abs($n - $cur) <= 1) $pages[] = $n; elseif (end($pages) !== '…') $pages[] = '…'; }
@endphp

@section('content')
<div class="pagehead"><div><h1>Agencies</h1><p>Every agency on the platform. Open one to manage its settings, team and workspaces.</p></div>
  <div style="display:flex;gap:8px"><a class="btn" href="/admin/reports">{!! Ui::icon('dl') !!}Export list</a><a class="btn primary" href="/admin/agencies/create">{!! Ui::icon('plus') !!}New agency</a></div></div>

<form method="get" action="/admin/agencies" class="fcard" id="filters">
  <input type="hidden" name="status" value="{{ $status }}"><input type="hidden" name="view" value="{{ $view }}"><input type="hidden" name="per_page" value="{{ $per }}">
  <input type="hidden" name="from" value="{{ request('from') }}"><input type="hidden" name="to" value="{{ request('to') }}">
  <div class="frow">
    <div class="qwrap grow">{!! Ui::icon('search') !!}<input class="q" type="text" name="q" value="{{ $q }}" placeholder="Search agency, owner or email…"></div>
    <div class="seg tabs3">
      @foreach(['all' => 'All', 'active' => 'Active', 'suspended' => 'Suspended'] as $k => $l)
        <a class="{{ $status === $k ? 'on' : '' }}" href="{{ Ui::with(['status' => $k === 'all' ? null : $k, 'page' => null]) }}">{{ $l }}<span class="c">{{ $tabs[$k] }}</span></a>
      @endforeach
    </div>
    <div class="seg v">
      <a class="{{ $view === 'table' ? 'on' : '' }}" href="{{ Ui::with(['view' => 'table']) }}" title="Table view">{!! Ui::icon('list') !!}</a>
      <a class="{{ $view === 'cards' ? 'on' : '' }}" href="{{ Ui::with(['view' => 'cards']) }}" title="Card view">{!! Ui::icon('grid') !!}</a>
    </div>
  </div>
  <div class="frow">
    <div class="rangewrap grow2">{!! Ui::icon('cal') !!}<input type="text" data-range placeholder="Created: pick a date range"></div>
    <div class="grow"><select name="usage" data-search="99">@foreach($usages as $k => $l)<option value="{{ $k }}" {{ $usage === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    <div class="grow"><select name="order" data-search="99">@foreach($sorts as $k => $l)<option value="{{ $k }}" {{ $order === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    <button class="btn primary" type="submit">{!! Ui::icon('filter') !!}Filter</button>
    @if($filtered)<a class="btn grey" href="/admin/agencies?view={{ $view }}">{!! Ui::icon('x') !!}Clear</a>@endif
  </div>
</form>

@if(!count($rows))
  <div class="empty"><div class="eic">{!! Ui::icon('building') !!}</div><h3>{{ $filtered ? 'No agencies match' : 'No agencies yet' }}</h3>
    <p>{{ $filtered ? 'Try changing or clearing the filters.' : 'Create the first agency to give someone a login.' }}</p>
    @if($filtered)<a class="btn" href="/admin/agencies">Clear filters</a>@else<a class="btn primary" href="/admin/agencies/create">{!! Ui::icon('plus') !!}New agency</a>@endif</div>
@elseif($view === 'cards')
  <div class="agcards">
    @foreach($rows as $a)
      <a class="agc" href="/admin/agencies/{{ $a['id'] }}"><div class="top">{!! Ui::avatar($a['name']) !!}<div><b>{{ $a['name'] }}</b><small>{{ $a['email'] }}</small></div>
        <span style="margin-left:auto" class="stag {{ $a['status'] === 'active' ? '' : 'off' }}">{{ $a['status'] === 'active' ? 'Active' : 'Suspended' }}</span></div>
        <div class="stats"><div><b>{{ $a['workspaces'] }}</b><small>Workspaces</small></div><div><b>{{ $a['members'] }}</b><small>Team</small></div><div><b>{{ $a['items'] }}</b><small>Items</small></div></div>
        <div class="foot"><span>{{ $a['pct'] }}% complete</span><span>Since {{ $a['created'] }}</span></div></a>
    @endforeach
  </div>
@else
  <table class="tbl">
    <thead><tr>{!! $th('name', 'Agency') !!}{!! $th('owner', 'Owner') !!}{!! $th('workspaces', 'Workspaces') !!}{!! $th('members', 'Team') !!}{!! $th('items', 'Items') !!}{!! $th('status', 'Status') !!}{!! $th('created', 'Created') !!}<th style="text-align:right">Actions</th></tr></thead>
    <tbody>
      @foreach($rows as $a)
        @php $active = $a['status'] === 'active'; @endphp
        <tr class="rowlink" data-row="/admin/agencies/{{ $a['id'] }}">
          <td><div class="who">{!! Ui::avatar($a['name']) !!}<div><a class="plain" href="/admin/agencies/{{ $a['id'] }}"><b>{{ $a['name'] }}</b></a></div></div></td>
          <td class="cell2">{{ $a['owner'] }}<small>{{ $a['email'] }}</small></td>
          <td>{{ $a['workspaces'] }} <span style="color:var(--faint)">/ {{ $a['max_workspaces'] }}</span><div class="mini-meter"><i class="{{ $a['workspaces'] >= $a['max_workspaces'] ? 'full' : '' }}" style="width:{{ min(100, $a['max_workspaces'] ? ($a['workspaces'] / $a['max_workspaces']) * 100 : 0) }}%"></i></div></td>
          <td>{{ $a['members'] }} <span style="color:var(--faint)">/ {{ $a['max_members'] }}</span><div class="mini-meter"><i class="{{ $a['members'] >= $a['max_members'] ? 'full' : '' }}" style="width:{{ min(100, $a['max_members'] ? ($a['members'] / $a['max_members']) * 100 : 0) }}%"></i></div></td>
          <td class="cell2">{{ $a['items'] }}<small>{{ $a['pct'] }}% done</small></td>
          <td><span class="stag {{ $active ? '' : 'off' }}">{{ $active ? 'Active' : 'Suspended' }}</span></td>
          <td style="color:var(--mute);white-space:nowrap">{{ $a['created'] }}</td>
          <td><div class="acts">
            <a class="ibtn act" href="/admin/agencies/{{ $a['id'] }}" title="View">{!! Ui::icon('eye') !!}</a>
            <a class="ibtn act" href="/admin/agencies/{{ $a['id'] }}?tab=settings" title="Edit">{!! Ui::icon('edit') !!}</a>
            <form method="post" action="/admin/agencies/{{ $a['id'] }}" style="margin:0"
                  data-confirm="{{ $active ? $a['name'].' will be suspended — its owner and team will not be able to sign in.' : $a['name'].' will be activated and its team can sign in again.' }}"
                  data-confirm-title="{{ $active ? 'Suspend this agency?' : 'Activate this agency?' }}" data-confirm-btn="{{ $active ? 'Yes, suspend' : 'Yes, activate' }}" @if($active) data-confirm-danger="1" @endif>
              @csrf @method('PATCH')<input type="hidden" name="status" value="{{ $active ? 'suspended' : 'active' }}">
              <button class="ibtn act {{ $active ? 'warn' : 'good' }}" title="{{ $active ? 'Suspend' : 'Activate' }}">{!! Ui::icon($active ? 'ban' : 'play') !!}</button></form>
          </div></td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endif

@if(count($rows))
  <div class="pager">
    <div class="pinfo">Showing <b>{{ $p->firstItem() }}–{{ $p->lastItem() }}</b> of <b>{{ $p->total() }}</b></div>
    @if($last > 1)
      <nav class="pnums" aria-label="Pagination">
        @if($p->onFirstPage())<span class="pg off">{!! Ui::icon('chev-l') !!}</span>@else<a class="pg" href="{{ $p->previousPageUrl() }}" title="Previous">{!! Ui::icon('chev-l') !!}</a>@endif
        @foreach($pages as $n)
          @if($n === '…')<span class="pg gap">…</span>@elseif($n === $cur)<span class="pg on">{{ $n }}</span>@else<a class="pg" href="{{ $p->url($n) }}">{{ $n }}</a>@endif
        @endforeach
        @if($p->hasMorePages())<a class="pg" href="{{ $p->nextPageUrl() }}" title="Next">{!! Ui::icon('chev-r') !!}</a>@else<span class="pg off">{!! Ui::icon('chev-r') !!}</span>@endif
      </nav>
    @endif
    <form method="get" action="/admin/agencies" class="pper" id="perform">
      @foreach(request()->except(['per_page', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
      <span>Rows per page</span>
      <select name="per_page" data-search="99" data-w="84px">@foreach([10, 25, 50, 100] as $n)<option value="{{ $n }}" {{ $per === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select>
    </form>
  </div>
@endif

@push('scripts')
<script>
  document.querySelectorAll('tr.rowlink').forEach(function (tr) { tr.addEventListener('click', function (e) { if (e.target.closest('a,button,form')) return; location.href = tr.dataset.row; }); });
  document.querySelectorAll('#filters select').forEach(function (s) { s.addEventListener('change', function () { document.getElementById('filters').requestSubmit(); }); });
  var per = document.querySelector('#perform select'); if (per) per.addEventListener('change', function () { document.getElementById('perform').requestSubmit(); });
</script>
@endpush
@endsection
