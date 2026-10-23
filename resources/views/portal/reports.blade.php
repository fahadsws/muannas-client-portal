@extends('portal.layout')
@section('title', 'Performance Reports')
@section('content')
@php use App\Support\Ui; @endphp
<div class="pagehead"><div><h1>Performance Reports</h1><p>Monthly results, archived by month.</p></div>
  @if($manage)<div class="prow"><button type="button" class="btn primary" data-modal="addReport">{!! Ui::icon('plus') !!}Add report</button></div>@endif</div>

@if($manage)
<x-modal id="addReport" title="Add or update a monthly report" wide>
<form method="post" enctype="multipart/form-data" action="/portal/c/{{ $account->id }}/reports">@csrf
  <div class="two"><div class="fld"><label>Month</label><input type="month" name="month" value="{{ now()->subMonth()->format('Y-m') }}" required></div>
    <div class="fld"><label>Reach</label><input type="number" min="0" name="reach" required></div></div>
  <div class="two"><div class="fld"><label>Engagement rate (%)</label><input type="number" step="0.01" min="0" max="100" name="engagement" required></div>
    <div class="fld"><label>Follower growth</label><input type="number" name="follower_growth" required placeholder="e.g. 240 or -15"></div></div>
  <div class="fld"><label>Top post</label><input name="top_post" maxlength="200" placeholder="Name of the best performing post"></div>
  <div class="fld"><label>Notes (optional)</label><textarea name="notes" maxlength="2000" style="min-height:70px"></textarea></div>
  <div class="fld"><x-file-input name="file" accept="application/pdf" label="Report PDF" hint="PDF only. Optional: the numbers above are shown on the dashboard either way." /></div>
  <label class="check" style="margin-bottom:14px"><input type="checkbox" name="notify" value="1"> Email the client again if this month already exists</label>
  <button class="btn primary sm">Save report</button>
  <p class="hint mute">Saving a new month emails the client automatically. Numbers are entered by hand — there is no platform integration yet.</p>
</form>
</x-modal>
@endif

@forelse($reports as $r)
  <div class="pn" style="margin-bottom:16px">
    <div class="prow between"><h3 style="margin:0">{{ $r->month->format('F Y') }}</h3>
      <div class="prow">
        @if($r->file_path)<a class="btn sm" href="/portal/reports/{{ $r->id }}/pdf?view=1" target="_blank">{!! Ui::icon('eye') !!}View PDF</a><a class="btn sm" href="/portal/reports/{{ $r->id }}/pdf">{!! Ui::icon('dl') !!}Download</a>@endif
        @if($manage)<form method="post" action="/portal/reports/{{ $r->id }}" data-confirm="Delete the {{ $r->month->format('F Y') }} report?" style="margin:0">@csrf @method('DELETE')<button class="ibtn" aria-label="Delete report">{!! Ui::icon('trash') !!}</button></form>@endif
      </div>
    </div>
    <div class="grid g4" style="margin-top:14px;gap:10px">
      <div class="kpi"><small>Reach</small><b style="font-size:30px">{{ number_format($r->reach) }}</b></div>
      <div class="kpi"><small>Engagement</small><b style="font-size:30px">{{ rtrim(rtrim(number_format($r->engagement, 2), '0'), '.') }}%</b></div>
      <div class="kpi"><small>Follower growth</small><b style="font-size:30px;color:{{ $r->follower_growth < 0 ? 'var(--red)' : 'var(--green)' }}">{{ $r->follower_growth > 0 ? '+' : '' }}{{ number_format($r->follower_growth) }}</b></div>
      <div class="kpi"><small>Top post</small><div style="font-weight:600;color:var(--ink);margin-top:6px">{{ $r->top_post ?: '—' }}</div></div>
    </div>
    @if($r->notes)<p class="caption mute" style="margin-bottom:0">{{ $r->notes }}</p>@endif
  </div>
@empty
  <div class="empty"><div class="eic">{!! \App\Support\Ui::icon('photo') !!}</div><h3>No reports yet</h3><p>Your first monthly performance report will appear here.</p></div>
@endforelse
@endsection
