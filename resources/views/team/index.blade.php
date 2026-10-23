@extends('agency.layout')
@section('title', 'Team')
@section('crumb')<b>Team</b>@endsection

@php
  use App\Support\Ui;
  $me = auth()->user(); $manage = $me->hasPerm('team.manage');
  $q = request('q', ''); $roleF = request('role', ''); $type = request('type', 'all'); $clientF = (int) request('client');
  $per = in_array((int) request('per_page'), [10, 25, 50]) ? (int) request('per_page') : 10;
  $order = request('order', 'role:asc'); [$sk, $sd] = array_pad(explode(':', $order), 2, 'asc');
  $sorts = ['role:asc' => 'Owner first', 'name:asc' => 'Name A–Z', 'name:desc' => 'Name Z–A', 'clients:desc' => 'Most clients', 'created:desc' => 'Newest first', 'created:asc' => 'Oldest first'];
  $filtered = $q !== '' || $roleF !== '' || $type !== 'all' || $clientF || request('from') || request('to');
  $th = function ($key, $label) use ($sk, $sd) {
      $on = $sk === $key; $next = $on ? ($sd === 'asc' ? 'desc' : 'asc') : (in_array($key, ['name', 'email', 'role']) ? 'asc' : 'desc');
      return '<th class="sortable '.($on ? 'on' : '').'"><a href="'.e(Ui::with(['order' => "$key:$next", 'page' => null])).'">'.$label.'<span class="arr">'.($on ? ($sd === 'asc' ? '▲' : '▼') : '↕').'</span></a></th>';
  };
  $last = $members->lastPage(); $cur = $members->currentPage(); $pages = [];
  foreach (range(1, max(1, $last)) as $n) { if ($n === 1 || $n === $last || abs($n - $cur) <= 1) $pages[] = $n; elseif (end($pages) !== '…') $pages[] = '…'; }
  $reach = fn ($m) => $m->seesAllClients() ? 'All clients' : $m->assigned_accounts_count.' '.\Illuminate\Support\Str::plural('client', $m->assigned_accounts_count);
@endphp

@section('content')
<div class="pagehead"><div><h1>Team</h1><p>{{ $staffCount }} of {{ $agency->max_members }} seats used. Roles decide what each person can do.</p></div>
  <div style="display:flex;gap:8px">@if($me->hasPerm('roles.manage'))<a class="btn" href="/roles">{!! Ui::icon('lock') !!}Roles &amp; permissions</a>@endif
    @if($manage)<a class="btn primary" href="/team/create">{!! Ui::icon('plus') !!}Add member</a>@endif</div></div>

<form method="get" action="/team" class="fcard" id="filters">
  <input type="hidden" name="type" value="{{ $type === 'all' ? '' : $type }}"><input type="hidden" name="view" value="{{ $view }}"><input type="hidden" name="per_page" value="{{ $per }}">
  <input type="hidden" name="from" value="{{ request('from') }}"><input type="hidden" name="to" value="{{ request('to') }}">
  <div class="frow">
    <div class="qwrap grow">{!! Ui::icon('search') !!}<input class="q" type="text" name="q" value="{{ $q }}" placeholder="Search by name or email…"></div>
    <div class="seg tabs3">
      @foreach(['all' => 'Everyone', 'owner' => 'Owners', 'member' => 'Members'] as $k => $l)
        <a class="{{ $type === $k ? 'on' : '' }}" href="{{ Ui::with(['type' => $k === 'all' ? null : $k, 'page' => null]) }}">{{ $l }}<span class="c">{{ $tabs[$k] }}</span></a>
      @endforeach
    </div>
    <div class="seg v">
      <a class="{{ $view === 'table' ? 'on' : '' }}" href="{{ Ui::with(['view' => 'table']) }}" title="Table view">{!! Ui::icon('list') !!}</a>
      <a class="{{ $view === 'cards' ? 'on' : '' }}" href="{{ Ui::with(['view' => 'cards']) }}" title="Card view">{!! Ui::icon('grid') !!}</a>
    </div>
  </div>
  <div class="frow">
    <div class="rangewrap grow2">{!! Ui::icon('cal') !!}<input type="text" data-range placeholder="Added: pick a date range"></div>
    <div class="grow"><select name="role" data-search="99"><option value="">All roles</option><option value="owner" {{ $roleF === 'owner' ? 'selected' : '' }}>Owner</option>
      @foreach($roles->where('name', '!=', 'Owner') as $r)<option value="{{ $r->id }}" {{ (string) $roleF === (string) $r->id ? 'selected' : '' }}>{{ $r->name }}</option>@endforeach</select></div>
    <div class="grow"><select name="client" data-search="99"><option value="">Any client</option>@foreach($clients as $c)<option value="{{ $c->id }}" {{ $clientF === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
    <div class="grow"><select name="order" data-search="99">@foreach($sorts as $k => $l)<option value="{{ $k }}" {{ $order === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    <button class="btn primary" type="submit">{!! Ui::icon('filter') !!}Filter</button>
    @if($filtered)<a class="btn grey" href="/team?view={{ $view }}">{!! Ui::icon('x') !!}Clear</a>@endif
  </div>
</form>

@if($members->isEmpty())
  <div class="empty"><div class="eic">{!! Ui::icon('user') !!}</div><h3>No one matches</h3><p>Try changing or clearing the filters.</p><a class="btn" href="/team">Clear filters</a></div>
@elseif($view === 'cards')
  <div class="agcards">
    @foreach($members as $m)
      @php $isOwner = $m->role === 'owner'; $self = $m->id === $me->id; @endphp
      <div class="agc" @if($manage || $self) data-row="/team/{{ $m->id }}/edit" @endif><div class="top">{!! Ui::avatar($m->name) !!}<div><b>{{ $m->name }}@if($self) <span style="color:var(--mute);font-weight:400">(you)</span>@endif</b><small>{{ $m->email }}</small></div>
        <span style="margin-left:auto" class="stag {{ $isOwner ? 'edit' : 'grey' }}">{{ $isOwner ? 'Owner' : ($m->agencyRole?->name ?? 'No role') }}</span></div>
        <div class="stats" style="grid-template-columns:1fr 1fr"><div><b style="font-size:15px">{{ $reach($m) }}</b><small>Access</small></div><div><b style="font-size:15px">{{ $m->created_at->format('d M Y') }}</b><small>Added</small></div></div>
        <div class="foot"><span>@if($manage || $self)<a class="plain" href="/team/{{ $m->id }}/edit">Edit</a>@endif</span>
          @if($manage && !$isOwner && !$self)
            <form method="post" action="/team/{{ $m->id }}" style="margin:0" data-confirm="{{ $m->name }} will lose access to {{ $agency->name }} straight away." data-confirm-title="Remove this person?" data-confirm-btn="Yes, remove" data-confirm-danger="1">@csrf @method('DELETE')<button class="ibtn act warn" title="Remove">{!! Ui::icon('trash') !!}</button></form>
          @endif</div></div>
    @endforeach
  </div>
@else
<table class="tbl">
  <thead><tr>{!! $th('name', 'Member') !!}{!! $th('email', 'Email') !!}{!! $th('role', 'Role') !!}{!! $th('clients', 'Clients') !!}{!! $th('created', 'Added') !!}<th style="text-align:right">Actions</th></tr></thead>
  <tbody>
    @foreach($members as $m)
      @php $isOwner = $m->role === 'owner'; $self = $m->id === $me->id; @endphp
      <tr>
        <td><div class="who">{!! Ui::avatar($m->name) !!}<div><b>{{ $m->name }}@if($self) <span style="color:var(--mute);font-weight:400">(you)</span>@endif</b></div></div></td>
        <td>{{ $m->email }}</td>
        <td><span class="stag {{ $isOwner ? 'edit' : 'grey' }}">{{ $isOwner ? 'Owner' : ($m->agencyRole?->name ?? 'No role') }}</span></td>
        <td style="color:var(--mute)">{{ $reach($m) }}</td>
        <td style="color:var(--mute);white-space:nowrap">{{ $m->created_at->format('d M Y') }}</td>
        <td><div class="acts">
          @if($manage || $self)<a class="ibtn act" href="/team/{{ $m->id }}/edit" title="Edit">{!! Ui::icon('edit') !!}</a>@endif
          @if($manage && !$isOwner && !$self)
            <form method="post" action="/team/{{ $m->id }}" style="margin:0" data-confirm="{{ $m->name }} will lose access to {{ $agency->name }} straight away." data-confirm-title="Remove this person?" data-confirm-btn="Yes, remove" data-confirm-danger="1">@csrf @method('DELETE')
              <button class="ibtn act warn" title="Remove">{!! Ui::icon('trash') !!}</button></form>
          @endif
        </div></td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

@if($members->isNotEmpty())
<div class="pager">
  <div class="pinfo">Showing <b>{{ $members->firstItem() }}–{{ $members->lastItem() }}</b> of <b>{{ $members->total() }}</b></div>
  @if($last > 1)<nav class="pnums">
    @if($members->onFirstPage())<span class="pg off">{!! Ui::icon('chev-l') !!}</span>@else<a class="pg" href="{{ $members->previousPageUrl() }}">{!! Ui::icon('chev-l') !!}</a>@endif
    @foreach($pages as $n)@if($n === '…')<span class="pg gap">…</span>@elseif($n === $cur)<span class="pg on">{{ $n }}</span>@else<a class="pg" href="{{ $members->url($n) }}">{{ $n }}</a>@endif @endforeach
    @if($members->hasMorePages())<a class="pg" href="{{ $members->nextPageUrl() }}">{!! Ui::icon('chev-r') !!}</a>@else<span class="pg off">{!! Ui::icon('chev-r') !!}</span>@endif
  </nav>@endif
  <form method="get" action="/team" class="pper" id="perform">@foreach(request()->except(['per_page', 'page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <span>Rows per page</span><select name="per_page" data-search="99" data-w="84px">@foreach([10, 25, 50] as $n)<option value="{{ $n }}" {{ $per === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></form>
</div>
@endif

<p class="sub-note" style="margin-top:18px">To give a <b>client</b> access to a single workspace, open the workspace and use <b>Share → People</b>.</p>

@push('scripts')
<script>
  document.querySelectorAll('.agc[data-row]').forEach(function (c) { c.addEventListener('click', function (e) { if (e.target.closest('a,button,form')) return; location.href = c.dataset.row; }); });
  document.querySelectorAll('#filters select').forEach(function (s) { s.addEventListener('change', function () { document.getElementById('filters').requestSubmit(); }); });
  var per = document.querySelector('#perform select'); if (per) per.addEventListener('change', function () { document.getElementById('perform').requestSubmit(); });
</script>
@endpush
@endsection
