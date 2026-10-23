@extends('admin.layout')
@section('title', 'Reports & export')
@section('crumb')<b>Reports & export</b>@endsection

@php
  use App\Support\Ui;
  $type = request('type', 'summary'); if (!isset($types[$type])) $type = 'summary';
  $desc = ['summary' => 'KPIs, charts and top agencies', 'directory' => 'Every agency with owner and status', 'usage' => 'Clients and team against limits', 'agency' => 'One agency in full detail'];
@endphp

@section('content')
<div class="pagehead"><div><h1>Reports &amp; export</h1><p>Create documents from platform data — preview first, then download as PDF, Word or CSV.</p></div></div>
<form id="reportform" class="docgrid" onsubmit="return false">
  <div class="sidepanel">
    <div class="fld"><label>Report</label><div class="chk" style="max-height:none">
      @foreach($types as $k => $l)
        <label style="align-items:flex-start"><input type="radio" name="type" value="{{ $k }}" {{ $type === $k ? 'checked' : '' }} style="margin-top:3px"><span>{{ $l }}<small style="display:block;color:var(--mute);font-weight:400;font-size:12px">{{ $desc[$k] }}</small></span></label>
      @endforeach</div></div>
    <div class="fld" data-for="agency"><label>Agency</label><select name="agency" data-search="8">@foreach($agencies as $a)<option value="{{ $a->id }}" {{ (int) request('agency') === $a->id ? 'selected' : '' }}>{{ $a->name }}</option>@endforeach @if(!count($agencies))<option value="">No agencies</option>@endif</select></div>
    <div class="fld" data-for="directory usage"><label>Agencies</label><select name="status" data-search="99"><option value="all">All agencies</option><option value="active">Active only</option><option value="suspended">Suspended only</option></select></div>
    <div class="fld"><label>Include</label><div style="display:grid;gap:9px">
      <label class="opt"><input type="checkbox" name="sections" value="kpis" checked> Summary figures</label>
      <label class="opt" data-for="summary agency"><input type="checkbox" name="sections" value="charts" checked> Charts</label>
      <label class="opt"><input type="checkbox" name="sections" value="table" checked> Tables</label>
      <label class="opt" data-for="agency"><input type="checkbox" name="sections" value="notes" checked> Internal notes</label></div></div>
    <div class="two"><div class="fld"><label>Orientation</label><select name="orientation" data-search="99"><option value="portrait">Portrait</option><option value="landscape">Landscape</option></select></div>
      <div class="fld"><label>Paper</label><select name="paper" data-search="99"><option value="a4" {{ $st['report_paper'] === 'a4' ? 'selected' : '' }}>A4</option><option value="letter" {{ $st['report_paper'] === 'letter' ? 'selected' : '' }}>Letter</option></select></div></div>
    <div><h3 style="margin-bottom:8px">Export</h3>
      <div class="exp"><a class="btn" data-fmt="pdf" data-noload="1" href="#">{!! Ui::icon('dl') !!}PDF</a><a class="btn" data-fmt="doc" data-noload="1" href="#">{!! Ui::icon('dl') !!}Word</a><a class="btn" data-fmt="csv" data-noload="1" href="#">{!! Ui::icon('dl') !!}CSV</a></div>
      <p class="sub-note" style="margin:10px 0 0">Header, footer and default paper are set in Settings → Reports.</p></div>
  </div>
  <div><div class="pbar"><span>Live preview — exactly what you'll export</span></div>
    <div class="previewbox"><div class="pl" id="rpl"><div style="text-align:center;color:var(--mute)"><i class="spin" style="border-color:rgba(0,0,0,.15);border-top-color:var(--ink);margin:0 auto 10px;display:block;width:22px;height:22px"></i>Rendering preview…</div></div>
      <iframe id="rframe" src="about:blank"></iframe></div></div>
</form>
@endsection
