@extends('agency.layout')
@php
  use App\Support\Ui;
  $me = auth()->user(); $editing = (bool) $u; $manage = $me->hasPerm('team.manage');
  $isOwner = $editing && $u->role === 'owner'; $self = $editing && $u->id === $me->id;
  $canRole = $manage && !$isOwner && !$self;
@endphp
@section('title', $editing ? 'Edit '.$u->name : 'Add member')
@section('crumb')<a href="/team" class="plain">Team</a><span class="sep">/</span><b>{{ $editing ? 'Edit '.$u->name : 'Add member' }}</b>@endsection

@section('content')
<a class="back" href="/team">{!! Ui::icon('arrow') !!}All team members</a>
<div class="pagehead"><div><h1>{{ $editing ? 'Edit team member' : 'Add team member' }}</h1><p>{{ $editing ? 'Change their details or role.' : 'They get their own login. Choose a role to control what they can do.' }}</p></div></div>

<form method="post" action="{{ $editing ? '/team/'.$u->id : '/team' }}" class="pn" style="max-width:760px">
  @csrf @if($editing) @method('PUT') @endif
  <div class="formgrid" style="max-width:none">
    <div class="two">
      <div class="fld"><label>Name</label><input type="text" name="name" value="{{ old('name', $u->name ?? '') }}" required autofocus></div>
      <div class="fld"><label>Email (login)</label><input type="text" name="email" value="{{ old('email', $u->email ?? '') }}" required></div>
    </div>
    <div class="fld"><label>{{ $editing ? 'New password' : 'Password' }} <span style="font-weight:400">· {{ $editing ? 'leave blank to keep the current one' : 'leave blank to generate one' }}</span></label>
      <div class="copyrow"><input type="text" name="password" id="pw" placeholder="At least 8 characters" autocomplete="new-password"><button type="button" class="btn" id="regen">Generate</button></div></div>

    <div class="fld"><label>Role</label>
      @if($isOwner)
        <div class="roleinfo"><b>Owner</b><span>Full control of the agency. The owner role cannot be changed.</span></div>
      @elseif($editing && !$canRole)
        <div class="roleinfo"><b>{{ $u->agencyRole?->name ?? 'No role' }}</b><span>{{ $self ? 'You cannot change your own role.' : 'You do not have permission to change roles.' }}</span></div>
      @else
        <select name="role_id" id="roleSel" data-search="99">
          @foreach($roles as $r)<option value="{{ $r->id }}" data-desc="{{ $r->description }}" data-perms="{{ count($r->permissions) }}" {{ (int) old('role_id', $u->role_id ?? $roles->firstWhere('name', 'Editor')?->id) === $r->id ? 'selected' : '' }}>{{ $r->name }}</option>@endforeach
        </select>
        <div class="roleinfo" id="roleInfo" style="margin-top:8px"></div>
        @if(auth()->user()->hasPerm('roles.manage'))<p class="sub-note" style="margin:6px 0 0">Need something different? <a href="/roles/create">Create a custom role</a>.</p>@endif
      @endif
    </div>
    <div style="display:flex;gap:8px"><button class="btn primary">{{ $editing ? 'Save changes' : 'Add member' }}</button><a class="btn" href="/team">Cancel</a></div>
  </div>
</form>

@push('scripts')
<script>
  document.getElementById('regen').addEventListener('click', function () { var c = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789', s = ''; for (var i = 0; i < 11; i++) s += c[Math.floor(Math.random() * c.length)]; document.getElementById('pw').value = s; });
  var sel = document.getElementById('roleSel'), info = document.getElementById('roleInfo');
  if (sel && info) { var show = function () { var o = sel.options[sel.selectedIndex]; info.innerHTML = '<b>' + o.text + '</b><span>' + (o.dataset.desc || '') + ' · ' + o.dataset.perms + ' permission' + (o.dataset.perms === '1' ? '' : 's') + '</span>'; }; sel.addEventListener('change', show); show(); }
</script>
@endpush
@endsection
