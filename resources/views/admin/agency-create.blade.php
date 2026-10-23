@extends('admin.layout')
@section('title', 'New agency')
@section('crumb')<a href="/admin/agencies" class="plain">Agencies</a><span class="sep">/</span><b>New agency</b>@endsection

@section('content')
@php use App\Support\Ui; @endphp
<a class="back" href="/admin/agencies">{!! Ui::icon('arrow') !!}All agencies</a>
<div class="pagehead"><div><h1>New agency</h1><p>Create the agency and its owner login. The owner can then add clients (each with their own login) and team members.</p></div></div>
<form method="post" action="/admin/agencies" class="pn" style="max-width:720px">
  @csrf
  <div class="formgrid" style="max-width:none">
    <div class="fld"><label>Agency name</label><input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Bright Social Studio" required autofocus></div>
    <div class="two"><div class="fld"><label>Owner name</label><input type="text" name="owner_name" value="{{ old('owner_name') }}" placeholder="Full name" required></div>
      <div class="fld"><label>Owner email (login)</label><input type="text" name="email" value="{{ old('email') }}" placeholder="owner@agency.com" required></div></div>
    <div class="fld"><label>Password</label><div class="copyrow"><input type="text" name="password" id="pw" value="{{ old('password', \Illuminate\Support\Str::password(12, symbols: false)) }}" required><button type="button" class="btn" id="regen">Generate</button></div></div>
    <div class="two"><div class="fld"><label>Client limit</label><input type="text" name="max_workspaces" value="{{ old('max_workspaces', $def['default_max_workspaces']) }}"></div>
      <div class="fld"><label>Team member limit</label><input type="text" name="max_members" value="{{ old('max_members', $def['default_max_members']) }}"></div></div>
    <div style="display:flex;gap:8px"><button class="btn primary">Create agency</button><a class="btn" href="/admin/agencies">Cancel</a></div>
  </div>
</form>
@push('scripts')
<script>document.getElementById('regen').addEventListener('click', function () { var c = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789', s = ''; for (var i = 0; i < 12; i++) s += c[Math.floor(Math.random() * c.length)]; document.getElementById('pw').value = s; });</script>
@endpush
@endsection
