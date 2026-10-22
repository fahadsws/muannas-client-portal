@extends('agency.layout')
@php
  use App\Support\Ui;
  $editing = (bool) $role; $sys = $editing && $role->is_system;
  $have = old('permissions', $role->permissions ?? []);
@endphp
@section('title', $editing ? 'Edit role' : 'New role')
@section('crumb')<a href="/roles" class="plain">Roles</a><span class="sep">/</span><b>{{ $editing ? $role->name : 'New role' }}</b>@endsection

@section('content')
<a class="back" href="/roles">{!! Ui::icon('arrow') !!}All roles</a>
<div class="pagehead"><div><h1>{{ $editing ? 'Edit role' : 'New role' }}</h1><p>Tick what people with this role are allowed to do.</p></div></div>

<form method="post" action="{{ $editing ? '/roles/'.$role->id : '/roles' }}" style="max-width:860px">
  @csrf @if($editing) @method('PUT') @endif
  <div class="pn" style="margin-bottom:16px"><div class="formgrid" style="max-width:none">
    <div class="two">
      <div class="fld"><label>Role name</label><input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" required maxlength="40" {{ $sys ? 'readonly' : 'autofocus' }} placeholder="e.g. Content lead">@if($sys)<span class="sub-note">Built-in role names are fixed.</span>@endif</div>
      <div class="fld"><label>Description <span style="font-weight:400">· optional</span></label><input type="text" name="description" value="{{ old('description', $role->description ?? '') }}" maxlength="160" placeholder="Who is this role for?"></div>
    </div></div></div>

  <div class="pn" style="padding:0">
    <div class="mxhead"><div><h3 style="margin:0">Permissions</h3><p style="margin:2px 0 0;font-size:12.5px;color:var(--mute)"><b id="mxcount">0</b> selected</p></div>
      <div style="display:flex;gap:8px"><button type="button" class="btn sm" id="mxall">Select all</button><button type="button" class="btn sm" id="mxnone">Clear</button></div></div>
    @foreach($groups as $group => $perms)
      <div class="mxgroup" data-group>
        <div class="mxg"><b>{{ $group }}</b><label class="opt" style="margin:0;font-size:12px"><input type="checkbox" data-gall> All</label></div>
        <div class="mxrows">
          @foreach($perms as $key => [$label, $help])
            <label class="mxrow"><input type="checkbox" name="permissions[]" value="{{ $key }}" {{ in_array($key, $have) ? 'checked' : '' }}><span><b>{{ $label }}</b><small>{{ $help }}</small></span></label>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>
  <div style="display:flex;gap:8px;margin-top:18px"><button class="btn primary">{{ $editing ? 'Save role' : 'Create role' }}</button><a class="btn" href="/roles">Cancel</a></div>
</form>

@push('scripts')
<script>
  (function () {
    var boxes = function () { return [].slice.call(document.querySelectorAll('input[name="permissions[]"]')); };
    var sync = function () {
      var all = boxes(); document.getElementById('mxcount').textContent = all.filter(function (b) { return b.checked; }).length + ' of ' + all.length;
      document.querySelectorAll('[data-group]').forEach(function (g) { var bs = [].slice.call(g.querySelectorAll('input[name="permissions[]"]')); g.querySelector('[data-gall]').checked = bs.every(function (b) { return b.checked; }); });
    };
    document.addEventListener('change', function (e) {
      if (e.target.matches('[data-gall]')) { e.target.closest('[data-group]').querySelectorAll('input[name="permissions[]"]').forEach(function (b) { b.checked = e.target.checked; }); }
      sync();
    });
    document.getElementById('mxall').onclick = function () { boxes().forEach(function (b) { b.checked = true; }); sync(); };
    document.getElementById('mxnone').onclick = function () { boxes().forEach(function (b) { b.checked = false; }); sync(); };
    sync();
  })();
</script>
@endpush
@endsection
