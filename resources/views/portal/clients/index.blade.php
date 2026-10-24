@extends('portal.layout')
@section('title', 'Clients')
@section('crumb', 'Clients')

@php
  use App\Support\Ui;
  $q = request('q', ''); $per = in_array((int) request('per_page'), [10, 25, 50]) ? (int) request('per_page') : 10;
  $status = request('status', 'all'); $teamF = (int) request('team'); $order = request('order', 'activity:desc');
  [$sk, $sd] = array_pad(explode(':', $order), 2, 'desc');
  $filtered = $q !== '' || $status !== 'all' || $teamF || request('from') || request('to');
  $sorts = ['activity:desc' => 'Recently updated', 'activity:asc' => 'Least recently updated', 'name:asc' => 'Name A–Z', 'name:desc' => 'Name Z–A', 'pending:desc' => 'Most pending approvals', 'posts:desc' => 'Most posts', 'created:desc' => 'Newest first', 'created:asc' => 'Oldest first'];
  $th = function ($key, $label) use ($sk, $sd) {
      $on = $sk === $key; $next = $on ? ($sd === 'asc' ? 'desc' : 'asc') : ($key === 'name' ? 'asc' : 'desc');
      return '<th class="sortable '.($on ? 'on' : '').'"><a href="'.e(Ui::with(['order' => "$key:$next", 'page' => null])).'">'.$label.'<span class="arr">'.($on ? ($sd === 'asc' ? '▲' : '▼') : '↕').'</span></a></th>';
  };
  $last = $accounts->lastPage(); $cur = $accounts->currentPage(); $pages = [];
  foreach (range(1, max(1, $last)) as $n) { if ($n === 1 || $n === $last || abs($n - $cur) <= 1) $pages[] = $n; elseif (end($pages) !== '…') $pages[] = '…'; }
  $ago = fn ($a) => $a->activity_at ? $a->activity_at->diffForHumans() : '—';
@endphp

@section('content')
<div class="pagehead"><div><h1>Clients</h1><p>{{ $total }} {{ \Illuminate\Support\Str::plural('client', $total) }} of {{ $agency->max_workspaces }} allowed. Each client has their own login and portal.</p></div>
  <div style="display:flex;gap:8px">@if($canCreate)<a class="btn primary" href="/portal/clients/create">{!! Ui::icon('plus') !!}Add client</a>@endif</div></div>

<form method="get" action="/portal/clients" class="fcard" id="filters">
  <input type="hidden" name="status" value="{{ $status }}"><input type="hidden" name="view" value="{{ $view }}"><input type="hidden" name="per_page" value="{{ $per }}">
  <input type="hidden" name="from" value="{{ request('from') }}"><input type="hidden" name="to" value="{{ request('to') }}">
  <div class="frow">
    <div class="qwrap grow">{!! Ui::icon('search') !!}<input class="q" type="text" name="q" value="{{ $q }}" placeholder="Search by client, contact name or email…"></div>
    <div class="seg tabs3">
      @foreach(['all' => 'All', 'pending' => 'Needs approval', 'nologin' => 'No login'] as $k => $l)
        <a class="{{ $status === $k ? 'on' : '' }}" href="{{ Ui::with(['status' => $k === 'all' ? null : $k, 'page' => null]) }}">{{ $l }}<span class="c">{{ $tabs[$k] }}</span></a>
      @endforeach
    </div>
    <div class="seg v">
      <a class="{{ $view === 'table' ? 'on' : '' }}" href="{{ Ui::with(['view' => 'table']) }}" title="Table view">{!! Ui::icon('list') !!}</a>
      <a class="{{ $view === 'cards' ? 'on' : '' }}" href="{{ Ui::with(['view' => 'cards']) }}" title="Card view">{!! Ui::icon('grid') !!}</a>
    </div>
  </div>
  <div class="frow">
    <div class="rangewrap grow2">{!! Ui::icon('cal') !!}<input type="text" data-range placeholder="Added: pick a date range"></div>
    <div class="grow"><select name="team" data-search="99"><option value="">Any team member</option>@foreach($staff as $s)<option value="{{ $s->id }}" {{ $teamF === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach</select></div>
    <div class="grow"><select name="order" data-search="99">@foreach($sorts as $k => $l)<option value="{{ $k }}" {{ $order === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    <button class="btn primary" type="submit">{!! Ui::icon('filter') !!}Filter</button>
    @if($filtered)<a class="btn grey" href="/portal/clients?view={{ $view }}">{!! Ui::icon('x') !!}Clear</a>@endif
  </div>
</form>

@if($accounts->isEmpty())
  <div class="empty"><div class="eic">{!! Ui::icon('folder') !!}</div><h3>{{ $filtered ? 'No client matches' : 'No clients yet' }}</h3>
    <p>{{ $filtered ? 'Try changing or clearing the filters.' : ($canCreate ? 'Add your first client and give them a login. They will see their own portal.' : 'Ask an admin to assign you to a client.') }}</p>
    @if($filtered)<a class="btn" href="/portal/clients">Clear filters</a>@elseif($canCreate)<a class="btn primary" href="/portal/clients/create">{!! Ui::icon('plus') !!}Add client</a>@endif</div>
@elseif($view === 'cards')
  <div class="agcards">
    @foreach($accounts as $a)
      <a class="agc" href="/board?ws={{ $a->id }}" style="text-decoration:none;color:inherit"><div class="top">{!! Ui::mark($a->name, $a->logoUrl()) !!}<div><b>{{ $a->name }}</b><small>{{ $a->clientUsers->first()?->email ?? 'No client login' }}</small>@if($a->phone)<small>&#128222; {{ $a->phone }}</small>@endif</div>
        @if($a->pending_count)<span style="margin-left:auto" class="stag s-pending">{{ $a->pending_count }} pending</span>@endif</div>
        <div class="stats"><div><b>{{ $a->posts_count }}</b><small>Posts</small></div><div><b>{{ $a->team->count() }}</b><small>Team</small></div><div><b>{{ $a->clientUsers->count() }}</b><small>Logins</small></div></div>
        <div class="foot"><span>Updated {{ $ago($a) }}</span><span>Since {{ $a->created_at->format('M Y') }}</span></div></a>
    @endforeach
  </div>
@else
<table class="tbl stack-m">
  <thead><tr>{!! $th('name', 'Client') !!}<th>Client login</th><th>Mobile</th><th>Muannas team</th>{!! $th('pending', 'Pending') !!}{!! $th('posts', 'Posts') !!}{!! $th('activity', 'Updated') !!}{!! $th('created', 'Added') !!}<th style="text-align:right">Actions</th></tr></thead>
  <tbody>
    @foreach($accounts as $a)
      <tr class="rowlink" data-row="/board?ws={{ $a->id }}">
        <td data-l="Client"><div class="who">{!! Ui::mark($a->name, $a->logoUrl()) !!}<div><b><a href="/board?ws={{ $a->id }}" style="text-decoration:none;color:inherit">{{ $a->name }}</a></b></div></div></td>
        <td data-l="Client login" class="cell2">@forelse($a->clientUsers->take(2) as $cu){{ $cu->name }}<small>{{ $cu->email }}</small>@empty<span style="color:var(--mute)">—</span>@endforelse</td>
        <td data-l="Mobile">@if($a->phone)<a class="tel" href="tel:{{ preg_replace('/[^0-9+]/', '', $a->phone) }}" title="Call">&#128222; {{ $a->phone }}</a>@else<span style="color:var(--mute)">—</span>@endif</td>
        <td data-l="Muannas team">{{ $a->team->pluck('name')->take(3)->join(', ') ?: '—' }}@if($a->team->count() > 3) <small style="color:var(--mute)">+{{ $a->team->count() - 3 }}</small>@endif</td>
        <td data-l="Pending">@if($a->pending_count)<span class="stag s-pending">{{ $a->pending_count }}</span>@else<span style="color:var(--mute)">—</span>@endif</td>
        <td data-l="Posts">{{ $a->posts_count }}</td>
        <td data-l="Updated" style="color:var(--mute);white-space:nowrap">{{ $ago($a) }}</td>
        <td data-l="Added" style="color:var(--mute);white-space:nowrap">{{ $a->created_at->format('d M Y') }}</td>
        <td><div class="acts">
          <a class="ibtn act" href="/board?ws={{ $a->id }}" title="Open dashboard">{!! Ui::icon('eye') !!}</a>
          @if($canEdit)<a class="ibtn act" href="/portal/clients/{{ $a->id }}" title="Edit">{!! Ui::icon('edit') !!}</a>@endif
          @if($canDelete)
            <form method="post" action="/portal/clients/{{ $a->id }}" style="margin:0" data-confirm="{{ $a->name }} and everything in their portal will be permanently deleted." data-confirm-title="Delete this client?" data-confirm-btn="Yes, delete" data-confirm-danger="1">@csrf @method('DELETE')
              <button class="ibtn act warn" title="Delete">{!! Ui::icon('trash') !!}</button></form>
          @endif
        </div></td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

@if($accounts->isNotEmpty())
<div class="pager">
  <div class="pinfo">Showing <b>{{ $accounts->firstItem() }}–{{ $accounts->lastItem() }}</b> of <b>{{ $accounts->total() }}</b></div>
  @if($last > 1)<nav class="pnums">
    @if($accounts->onFirstPage())<span class="pg off">{!! Ui::icon('chev-l') !!}</span>@else<a class="pg" href="{{ $accounts->previousPageUrl() }}">{!! Ui::icon('chev-l') !!}</a>@endif
    @foreach($pages as $n)@if($n === '…')<span class="pg gap">…</span>@elseif($n === $cur)<span class="pg on">{{ $n }}</span>@else<a class="pg" href="{{ $accounts->url($n) }}">{{ $n }}</a>@endif @endforeach
    @if($accounts->hasMorePages())<a class="pg" href="{{ $accounts->nextPageUrl() }}">{!! Ui::icon('chev-r') !!}</a>@else<span class="pg off">{!! Ui::icon('chev-r') !!}</span>@endif
  </nav>@endif
  <form method="get" action="/portal/clients" class="pper" id="perform">@foreach(request()->except(['per_page', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <span>Rows per page</span><select name="per_page" data-search="99" data-w="84px">@foreach([10, 25, 50] as $n)<option value="{{ $n }}" {{ $per === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></form>
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
