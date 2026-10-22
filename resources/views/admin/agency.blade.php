@extends('admin.layout')
@php
  use App\Support\Ui;
  $a = $d['agency']; $c = $a['counts']; $tot = array_sum($c);
  $col = ['todo' => '#d4d4d4', 'ready' => '#7c3aed', 'done' => '#0a0a0a', 'live' => '#15a05f'];
  $label = ['todo' => 'To do', 'ready' => 'Ready', 'done' => 'Done', 'live' => 'Live'];
  $C = 2 * M_PI * 52; $off = 0;
  $tabs = ['overview' => 'Overview', 'workspaces' => 'Workspaces <span class="n">'.count($d['workspaces']).'</span>', 'team' => 'Team <span class="n">'.count($d['team']).'</span>', 'settings' => 'Settings'];
  $stag = '<span class="stag '.($a['status'] === 'active' ? '' : 'off').'">'.($a['status'] === 'active' ? 'Active' : 'Suspended').'</span>';
@endphp
@section('title', $a['name'])
@section('crumb')<a href="/admin/agencies" class="plain">Agencies</a><span class="sep">/</span><b>{{ $a['name'] }}</b>@endsection

@section('content')
<a class="back" href="/admin/agencies">{!! Ui::icon('arrow') !!}All agencies</a>
<div class="dhead"><div class="t">{!! Ui::avatar($a['name']) !!}<div><h1>{{ $a['name'] }} {!! $stag !!}</h1><p>{{ $a['owner'] }} · {{ $a['email'] }} · since {{ $a['created'] }}</p></div></div>
  <div class="dacts"><a class="btn" href="/admin/reports?type=agency&agency={{ $a['id'] }}">{!! Ui::icon('dl') !!}Report</a>
    <form method="post" action="/admin/agencies/{{ $a['id'] }}/impersonate" style="margin:0">@csrf<button class="btn primary">{!! Ui::icon('eye') !!}Open as agency</button></form></div></div>

<div class="tabs" style="margin-bottom:22px">
  @foreach($tabs as $k => $l)<a class="tab {{ $tab === $k ? 'on' : '' }}" href="/admin/agencies/{{ $a['id'] }}?tab={{ $k }}">{!! $l !!}</a>@endforeach
</div>

@if($tab === 'overview')
  <div class="kpis">
    <div class="kpi"><small>Workspaces</small><b>{{ $a['workspaces'] }}</b><div class="sub">limit {{ $a['max_workspaces'] }}</div><div class="meter"><i style="width:{{ min(100, ($a['workspaces'] / max(1, $a['max_workspaces'])) * 100) }}%"></i></div></div>
    <div class="kpi"><small>Team</small><b>{{ $a['members'] }}</b><div class="sub">limit {{ $a['max_members'] }}</div><div class="meter"><i style="width:{{ min(100, ($a['members'] / max(1, $a['max_members'])) * 100) }}%"></i></div></div>
    <div class="kpi"><small>Content items</small><b>{{ $a['items'] }}</b><div class="sub">{{ $a['pct'] }}% complete</div><div class="meter"><i class="g" style="width:{{ $a['pct'] }}%"></i></div></div>
    <div class="kpi"><small>Live now</small><b>{{ $c['live'] }}</b><div class="sub">{{ $c['ready'] }} ready for approval</div><div class="meter"><i class="p" style="width:{{ $a['items'] ? ($c['ready'] / $a['items']) * 100 : 0 }}%"></i></div></div>
  </div>
  <div class="grid2">
    <div class="pn"><h3>Content status</h3><p>Everything this agency is managing.</p>
      <div class="donutwrap"><div class="donut"><svg viewBox="0 0 132 132">
        @if($tot) @foreach($col as $k => $cc) @php $len = ($c[$k] / $tot) * $C; @endphp<circle cx="66" cy="66" r="52" stroke="{{ $cc }}" stroke-dasharray="{{ $len }} {{ $C - $len }}" stroke-dashoffset="{{ -$off }}"/>@php $off += $len; @endphp @endforeach
        @else<circle cx="66" cy="66" r="52" stroke="#eee"/>@endif</svg><div class="c"><div><b>{{ $tot }}</b><small>items</small></div></div></div>
        <div class="lg2">@foreach($label as $k => $l)<div><i style="background:{{ $col[$k] }}"></i>{{ $l }}<b>{{ $c[$k] }}</b></div>@endforeach</div></div></div>
    <div class="pn"><h3>Account</h3><p>Owner login and limits.</p>
      <div class="kv"><span>Owner</span><b>{{ $a['owner'] }}</b><span>Email</span><b>{{ $a['email'] }}</b><span>Status</span><b>{{ $a['status'] === 'active' ? 'Active' : 'Suspended' }}</b><span>Workspace limit</span><b>{{ $a['max_workspaces'] }}</b><span>Team limit</span><b>{{ $a['max_members'] }}</b><span>Created</span><b>{{ $a['created'] }}</b></div>
      @if($a['notes'])<hr class="hr"><span class="sub-note">Internal notes</span><p style="margin:6px 0 0;font-size:13.5px">{{ $a['notes'] }}</p>@endif</div>
  </div>

@elseif($tab === 'workspaces')
  @if(count($d['workspaces']))
    <table class="tbl"><thead><tr><th>Workspace</th><th>Items</th><th>Complete</th><th>Client link</th><th>Created</th></tr></thead><tbody>
      @foreach($d['workspaces'] as $w)
        <tr><td><div class="who">{!! Ui::avatar($w['name']) !!}<div><b>{{ $w['name'] }}</b></div></div></td><td>{{ $w['items'] }}</td>
          <td style="min-width:140px">{{ $w['pct'] }}%<div class="mini-meter" style="width:110px"><i style="width:{{ $w['pct'] }}%"></i></div></td>
          <td><span class="stag {{ $w['shared'] ? '' : 'grey' }}">{{ $w['shared'] ? 'On' : 'Off' }}</span></td><td style="color:var(--mute)">{{ $w['created'] }}</td></tr>
      @endforeach</tbody></table>
  @else
    <div class="empty"><div class="eic">{!! Ui::icon('doc') !!}</div><h3>No workspaces yet</h3><p>This agency hasn't created a workspace.</p></div>
  @endif

@elseif($tab === 'team')
  <table class="tbl"><thead><tr><th>Person</th><th>Email</th><th>Role</th><th>Added</th></tr></thead><tbody>
    @foreach($d['team'] as $u)
      <tr><td><div class="who">{!! Ui::avatar($u['name']) !!}<div><b>{{ $u['name'] }}</b></div></div></td><td>{{ $u['email'] }}</td>
        <td><span class="stag {{ $u['role'] === 'owner' ? 'edit' : 'grey' }}">{{ ucfirst($u['role']) }}</span></td><td style="color:var(--mute)">{{ $u['created'] }}</td></tr>
    @endforeach</tbody></table>

@else
  <div class="grid2" style="grid-template-columns:1.2fr 1fr;align-items:start">
    <div class="pn"><h3>Agency settings</h3><p>Name, limits and internal notes for this agency only.</p>
      <form method="post" action="/admin/agencies/{{ $a['id'] }}" class="formgrid">@csrf @method('PATCH')
        <div class="fld"><label>Agency name</label><input type="text" name="name" value="{{ old('name', $a['name']) }}" required></div>
        <div class="two"><div class="fld"><label>Workspace limit</label><input type="text" name="max_workspaces" value="{{ old('max_workspaces', $a['max_workspaces']) }}"></div>
          <div class="fld"><label>Team member limit</label><input type="text" name="max_members" value="{{ old('max_members', $a['max_members']) }}"></div></div>
        <div class="fld"><label>Status</label><select name="status" data-search="99"><option value="active" {{ $a['status'] === 'active' ? 'selected' : '' }}>Active</option><option value="suspended" {{ $a['status'] === 'suspended' ? 'selected' : '' }}>Suspended — members can't sign in</option></select></div>
        <div class="fld"><label>Internal notes</label><textarea name="notes" placeholder="Plan, contract, anything the team should know…">{{ old('notes', $a['notes']) }}</textarea></div>
        <div><button class="btn primary">Save settings</button></div></form></div>
    <div style="display:grid;gap:16px">
      <div class="pn"><h3>Owner login</h3><p>The person who runs this agency.</p>
        <form method="post" action="/admin/agencies/{{ $a['id'] }}/owner" class="formgrid">@csrf @method('PUT')
          <div class="fld"><label>Owner name</label><input type="text" name="owner_name" value="{{ $a['owner'] }}"></div>
          <div class="fld"><label>Owner email</label><input type="text" name="email" value="{{ $a['email'] }}"></div>
          <div style="display:flex;gap:8px"><button class="btn primary">Save owner</button></div></form>
        <form method="post" action="/admin/agencies/{{ $a['id'] }}/password" data-confirm="A new password will be generated for {{ $a['owner'] }}. The old one stops working." data-confirm-title="Reset password?" data-confirm-btn="Yes, reset" style="margin-top:10px">@csrf<button class="btn">Reset password</button></form></div>
      <div class="dangerzone"><div><b>Delete agency</b><small>Permanently removes the agency, workspaces, content and logins.</small></div>
        <form method="post" action="/admin/agencies/{{ $a['id'] }}" data-confirm="{{ $a['name'] }} will be permanently deleted with all its workspaces, content and logins. This can't be undone." data-confirm-title="Delete this agency?" data-confirm-btn="Yes, delete it" data-confirm-danger="1" style="margin:0">@csrf @method('DELETE')<button class="btn solid-danger">Delete</button></form></div>
    </div>
  </div>
@endif
@endsection
