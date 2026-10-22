@extends('admin.layout')
@section('title', 'Settings')
@section('crumb')<b>Settings</b>@endsection

@section('content')
@php use App\Support\Ui; $me = auth()->user(); @endphp
<div class="pagehead"><div><h1>Settings</h1><p>Platform defaults, report branding and your admin account.</p></div></div>
<div class="setgrid">
  <div class="setnav">
    <a class="{{ $sec === 'platform' ? 'on' : '' }}" href="/admin/settings?section=platform">{!! Ui::icon('building') !!}Platform</a>
    <a class="{{ $sec === 'reports' ? 'on' : '' }}" href="/admin/settings?section=reports">{!! Ui::icon('doc') !!}Reports</a>
    <a class="{{ $sec === 'account' ? 'on' : '' }}" href="/admin/settings?section=account">{!! Ui::icon('user') !!}My account</a>
  </div>
  <div>
    @if($sec === 'platform')
      <div class="card-p"><h3>Platform</h3><p>Name shown on the login page, sidebar and documents, plus defaults for new agencies.</p>
        <form method="post" action="/admin/settings" class="formgrid">@csrf <input type="hidden" name="group" value="platform">
          <div class="two"><div class="fld"><label>Platform name</label><input type="text" name="brand" value="{{ old('brand', $st['brand']) }}" required></div><div class="fld"><label>Tagline</label><input type="text" name="tagline" value="{{ old('tagline', $st['tagline']) }}" required></div></div>
          <div class="two"><div class="fld"><label>Default workspace limit</label><input type="text" name="default_max_workspaces" value="{{ old('default_max_workspaces', $st['default_max_workspaces']) }}"></div><div class="fld"><label>Default team limit</label><input type="text" name="default_max_members" value="{{ old('default_max_members', $st['default_max_members']) }}"></div></div>
          <div><button class="btn primary">Save platform settings</button></div></form></div>
    @elseif($sec === 'reports')
      <div class="card-p"><h3>Reports</h3><p>Applied to every platform report you export.</p>
        <form method="post" action="/admin/settings" class="formgrid">@csrf <input type="hidden" name="group" value="reports">
          <div class="fld"><label>Header text</label><input type="text" name="report_header" value="{{ old('report_header', $st['report_header']) }}" placeholder="e.g. Confidential — internal use"></div>
          <div class="fld"><label>Footer text <span style="font-weight:400">· {brand} = platform name</span></label><input type="text" name="report_footer" value="{{ old('report_footer', $st['report_footer']) }}"></div>
          <div class="fld"><label>Default paper</label><select name="report_paper" data-search="99"><option value="a4" {{ $st['report_paper'] === 'a4' ? 'selected' : '' }}>A4</option><option value="letter" {{ $st['report_paper'] === 'letter' ? 'selected' : '' }}>Letter</option></select></div>
          <div><button class="btn primary">Save report settings</button></div></form></div>
    @else
      <div class="card-p"><h3>My account</h3><p>Your platform admin login.</p>
        <form method="post" action="/admin/settings/account" class="formgrid">@csrf
          <div class="two"><div class="fld"><label>Name</label><input type="text" name="name" value="{{ old('name', $me->name) }}" required></div><div class="fld"><label>Email</label><input type="text" name="email" value="{{ old('email', $me->email) }}" required></div></div>
          <div class="fld"><label>New password <span style="font-weight:400">· leave blank to keep the current one</span></label><input type="password" name="password" autocomplete="new-password"></div>
          <div><button class="btn primary">Save account</button></div></form></div>
    @endif
  </div>
</div>
@endsection
