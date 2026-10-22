@extends('agency.layout')
@section('title', 'Roles & permissions')
@section('crumb')<b>Roles &amp; permissions</b>@endsection

@section('content')
@php use App\Support\Ui; use App\Support\Permissions; @endphp
<div class="pagehead"><div><h1>Roles &amp; permissions</h1><p>A role is a set of things a team member is allowed to do. Assign roles on the Team page.</p></div>
  <div style="display:flex;gap:8px"><a class="btn" href="/team">{!! Ui::icon('user') !!}Team</a><a class="btn primary" href="/roles/create">{!! Ui::icon('plus') !!}New role</a></div></div>

<table class="tbl">
  <thead><tr><th>Role</th><th>What it allows</th><th>Permissions</th><th>People</th><th style="text-align:right">Actions</th></tr></thead>
  <tbody>
    @foreach($roles as $r)
      @php $locked = $r->isOwnerRole(); $n = count($r->permissions); @endphp
      <tr>
        <td><div class="who"><span class="av" style="width:34px;height:34px">{{ \App\Support\Ui::initials($r->name) }}</span><div><b>{{ $r->name }}</b><small>{{ $r->is_system ? ($locked ? 'Built-in · locked' : 'Built-in') : 'Custom' }}</small></div></div></td>
        <td style="max-width:360px;color:var(--ink2)">{{ $r->description ?: '—' }}</td>
        <td style="min-width:150px">{{ $n }} <span style="color:var(--faint)">/ {{ $total }}</span><div class="mini-meter" style="width:110px"><i style="width:{{ $total ? ($n / $total) * 100 : 0 }}%"></i></div></td>
        <td>{{ $locked ? $r->users_count : $r->users_count }}</td>
        <td><div class="acts">
          @if(!$locked)<a class="ibtn act" href="/roles/{{ $r->id }}/edit" title="Edit">{!! Ui::icon('edit') !!}</a>@else<span class="ibtn act" style="opacity:.35;cursor:not-allowed" title="The Owner role cannot be changed">{!! Ui::icon('lock') !!}</span>@endif
          @if(!$r->is_system)
            <form method="post" action="/roles/{{ $r->id }}" style="margin:0" data-confirm="The role &quot;{{ $r->name }}&quot; will be deleted.{{ $r->users_count ? ' '.$r->users_count.' people still use it, so you will need to reassign them first.' : '' }}" data-confirm-title="Delete this role?" data-confirm-btn="Yes, delete" data-confirm-danger="1">@csrf @method('DELETE')
              <button class="ibtn act warn" title="Delete">{!! Ui::icon('trash') !!}</button></form>
          @endif
        </div></td>
      </tr>
    @endforeach
  </tbody>
</table>
<p class="sub-note" style="margin-top:16px">Owners can always do everything. A person with no content-editing permission can still read everything and leave comments.</p>
@endsection
