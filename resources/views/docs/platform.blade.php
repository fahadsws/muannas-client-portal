@php
  $sec = $sections;
  $S = ['todo' => ['To do', '#cbd0ee'], 'ready' => ['Ready', '#f59e0b'], 'done' => ['Done', '#4f46e5'], 'live' => ['Live', '#15a05f']];
  $bar = function ($vals, $total) use ($S) {
      $h = '<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse"><tr>';
      foreach ($vals as $k => $v) { if ($v > 0 && $total > 0) { $h .= '<td style="width:'.round($v / $total * 100, 2).'%;background:'.$S[$k][1].';height:9px;font-size:1px;line-height:1px">&nbsp;</td>'; } }
      if ($total == 0) { $h .= '<td style="background:#eee;height:9px;font-size:1px">&nbsp;</td>'; }
      return $h.'</tr></table>';
  };
  $meter = fn ($n, $max) => '<table width="100%" cellspacing="0" cellpadding="0"><tr><td style="width:'.max(2, min(100, $max ? round($n / $max * 100) : 0)).'%;background:'.(($max && $n >= $max) ? '#dc4a46' : '#0a0a0a').';height:6px;font-size:1px;border:0;padding:0">&nbsp;</td><td style="border:0;padding:0;background:#eee"></td></tr></table>';
@endphp
<!doctype html>
<html lang="en" @if(!empty($word)) xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" @endif>
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
  @page { margin: 34px 38px; }
  * { box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 10px; color: #171a3d; margin: 0; line-height: 1.45; @if($preview) background:#ececec; @endif }
  .paper { @if($preview) max-width: {{ $orientation === 'landscape' ? '1050px' : '800px' }}; margin: 24px auto; background:#fff; padding: 36px 40px; border:1px solid #d6d6d6; @endif }
  h1 { font-size: 22px; margin: 0 0 3px; letter-spacing: -.3px; }
  h2 { font-size: 13px; margin: 22px 0 8px; padding-bottom: 5px; border-bottom: 1.5px solid #4f46e5; }
  h3 { font-size: 11px; margin: 14px 0 6px; }
  .muted { color: #767676; } .small { font-size: 8.5px; }
  .tag { font-size: 8px; letter-spacing: 1px; text-transform: uppercase; color: #767676; }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; font-size: 8px; letter-spacing: .8px; text-transform: uppercase; color: #767676; padding: 6px 7px; border-bottom: 1px solid #cfcfcf; background: #f6f6f6; }
  td { padding: 6px 7px; border-bottom: 1px solid #ececec; vertical-align: top; }
  .pill { display: inline-block; padding: 1px 7px; border-radius: 9px; font-size: 8px; color: #fff; }
  .kpi td { border: 1px solid #dcdcdc; padding: 10px 12px; width: 25%; }
  .kpi b { font-size: 20px; display: block; }
  .box { border: 1px solid #dcdcdc; padding: 9px 11px; margin-bottom: 7px; }
  .foot { margin-top: 26px; padding-top: 8px; border-top: 1px solid #dcdcdc; font-size: 8px; color: #8d8d8d; }
</style>
</head>
<body>
<div class="paper">
  <table style="margin-bottom:6px"><tr>
    <td style="border:0;padding:0;width:6px;background:#4f46e5">&nbsp;</td>
    <td style="border:0;padding:0 0 0 12px"><div class="tag">{{ $brand }} · Platform admin</div><h1>{{ $title }}</h1><div class="muted">{{ $date }}@if($type === 'directory' || $type === 'usage') &nbsp;·&nbsp; {{ $filter }}@endif</div></td>
    <td style="border:0;padding:0;text-align:right;width:160px" class="muted small">{{ $header }}</td>
  </tr></table>

  {{-- ───────── Platform summary ───────── --}}
  @if($type === 'summary')
    @php $t = $dash['totals']; $st = $dash['status']; $tot = array_sum($st); @endphp
    @if(in_array('kpis', $sec))
      <h2>Overview</h2>
      <table class="kpi"><tr>
        <td><span class="tag">Agencies</span><b>{{ $t['agencies'] }}</b><span class="muted small">{{ $t['active'] }} active · {{ $t['suspended'] }} suspended</span></td>
        <td><span class="tag">Workspaces</span><b>{{ $t['workspaces'] }}</b></td>
        <td><span class="tag">Users</span><b>{{ $t['users'] }}</b><span class="muted small">{{ $t['guests'] }} guests</span></td>
        <td><span class="tag">Content items</span><b>{{ $t['items'] }}</b><span class="muted small">{{ $t['completion'] }}% complete</span></td>
      </tr></table>
    @endif
    @if(in_array('charts', $sec))
      <h2>Charts</h2>
      <h3 style="margin-top:4px">Content status across all agencies</h3>
      {!! $bar($st, $tot) !!}
      <table style="margin-top:4px"><tr>@foreach($S as $k => $v)<td style="border:0;padding:3px 0;width:25%"><span style="display:inline-block;width:8px;height:8px;background:{{ $v[1] }}"></span> {{ $v[0] }} <b>{{ $st[$k] }}</b></td>@endforeach</tr></table>
      <h3>New agencies per month</h3>
      @php $mx = max(1, collect($dash['months'])->max('n')); @endphp
      <table>@foreach($dash['months'] as $m)
        <tr><td style="width:9%;border:0;padding:3px 7px" class="muted">{{ $m['label'] }}</td><td style="border:0;padding:3px 7px"><table width="100%"><tr><td style="width:{{ $m['n'] ? max(2, round($m['n'] / $mx * 100)) : 0 }}%;background:#0a0a0a;height:9px;font-size:1px;border:0;padding:0">&nbsp;</td><td style="border:0;padding:0"></td></tr></table></td><td style="width:8%;border:0;padding:3px 7px;text-align:right" class="muted">{{ $m['n'] }}</td></tr>
      @endforeach</table>
    @endif
    @if(in_array('table', $sec))
      <h2>Top agencies by content</h2>
      <table><tr><th>Agency</th><th>Status</th><th>Workspaces</th><th>Team</th><th>Items</th><th>Complete</th></tr>
        @foreach($dash['top'] as $a)<tr><td><b>{{ $a['name'] }}</b></td><td>{{ ucfirst($a['status']) }}</td><td>{{ $a['workspaces'] }}</td><td>{{ $a['members'] }}</td><td>{{ $a['items'] }}</td><td>{{ $a['pct'] }}%</td></tr>@endforeach
        @if(!count($dash['top']))<tr><td colspan="6" class="muted">No agencies yet.</td></tr>@endif
      </table>
    @endif

  {{-- ───────── Directory / usage ───────── --}}
  @elseif($type === 'directory' || $type === 'usage')
    @if(in_array('kpis', $sec))
      @php $rows = collect($rows); @endphp
      <h2>Summary</h2>
      <table class="kpi"><tr>
        <td><span class="tag">Agencies</span><b>{{ $rows->count() }}</b></td>
        <td><span class="tag">Workspaces</span><b>{{ $rows->sum('workspaces') }}</b></td>
        <td><span class="tag">Team members</span><b>{{ $rows->sum('members') }}</b></td>
        <td><span class="tag">Content items</span><b>{{ $rows->sum('items') }}</b></td>
      </tr></table>
    @endif
    @if(in_array('table', $sec))
      <h2>{{ $type === 'usage' ? 'Usage against limits' : 'Agencies' }}</h2>
      @if($type === 'usage')
        <table><tr><th style="width:24%">Agency</th><th style="width:26%">Workspaces</th><th style="width:26%">Team</th><th>Items</th><th>Status</th></tr>
          @foreach($rows as $a)<tr><td><b>{{ $a['name'] }}</b></td>
            <td>{{ $a['workspaces'] }} / {{ $a['max_workspaces'] }}{!! $meter($a['workspaces'], $a['max_workspaces']) !!}</td>
            <td>{{ $a['members'] }} / {{ $a['max_members'] }}{!! $meter($a['members'], $a['max_members']) !!}</td>
            <td>{{ $a['items'] }}</td><td>{{ ucfirst($a['status']) }}</td></tr>@endforeach
          @if(!count($rows))<tr><td colspan="5" class="muted">No agencies match.</td></tr>@endif
        </table>
      @else
        <table><tr><th>Agency</th><th>Owner</th><th>Status</th><th>WS</th><th>Team</th><th>Items</th><th>Done</th><th>Created</th></tr>
          @foreach($rows as $a)<tr><td><b>{{ $a['name'] }}</b></td><td>{{ $a['owner'] }}<br><span class="muted small">{{ $a['email'] }}</span></td><td>{{ ucfirst($a['status']) }}</td><td>{{ $a['workspaces'] }}</td><td>{{ $a['members'] }}</td><td>{{ $a['items'] }}</td><td>{{ $a['pct'] }}%</td><td class="muted">{{ $a['created'] }}</td></tr>@endforeach
          @if(!count($rows))<tr><td colspan="8" class="muted">No agencies match.</td></tr>@endif
        </table>
      @endif
    @endif

  {{-- ───────── Single agency ───────── --}}
  @elseif(empty($detail))
    <h2>Agency profile</h2><p class="muted">There are no agencies yet. Create an agency first, then pick it here to generate its profile.</p>
  @else
    @php $a = $detail['agency']; $c = $a['counts']; $tot = array_sum($c); @endphp
    @if(in_array('kpis', $sec))
      <h2>Profile</h2>
      <table class="kpi"><tr>
        <td><span class="tag">Status</span><b style="font-size:16px">{{ ucfirst($a['status']) }}</b><span class="muted small">Since {{ $a['created'] }}</span></td>
        <td><span class="tag">Workspaces</span><b>{{ $a['workspaces'] }}</b><span class="muted small">limit {{ $a['max_workspaces'] }}</span></td>
        <td><span class="tag">Team</span><b>{{ $a['members'] }}</b><span class="muted small">limit {{ $a['max_members'] }}</span></td>
        <td><span class="tag">Content items</span><b>{{ $a['items'] }}</b><span class="muted small">{{ $a['pct'] }}% complete</span></td>
      </tr></table>
      <p style="margin-top:10px"><b>Owner:</b> {{ $a['owner'] }} &nbsp;·&nbsp; {{ $a['email'] }}</p>
    @endif
    @if(in_array('charts', $sec))
      <h2>Content status</h2>{!! $bar($c, $tot) !!}
      <table style="margin-top:4px"><tr>@foreach($S as $k => $v)<td style="border:0;padding:3px 0;width:25%"><span style="display:inline-block;width:8px;height:8px;background:{{ $v[1] }}"></span> {{ $v[0] }} <b>{{ $c[$k] }}</b></td>@endforeach</tr></table>
    @endif
    @if(in_array('table', $sec))
      <h2>Workspaces</h2>
      <table><tr><th>Workspace</th><th>Items</th><th>Complete</th><th>Client link</th><th>Created</th></tr>
        @foreach($detail['workspaces'] as $w)<tr><td><b>{{ $w['name'] }}</b></td><td>{{ $w['items'] }}</td><td>{{ $w['pct'] }}%</td><td>{{ $w['shared'] ? 'On' : 'Off' }}</td><td class="muted">{{ $w['created'] }}</td></tr>@endforeach
        @if(!count($detail['workspaces']))<tr><td colspan="5" class="muted">No workspaces yet.</td></tr>@endif
      </table>
      <h2>Team</h2>
      <table><tr><th>Name</th><th>Email</th><th>Role</th><th>Added</th></tr>
        @foreach($detail['team'] as $u)<tr><td><b>{{ $u['name'] }}</b></td><td>{{ $u['email'] }}</td><td>{{ ucfirst($u['role']) }}</td><td class="muted">{{ $u['created'] }}</td></tr>@endforeach
      </table>
    @endif
    @if(in_array('notes', $sec) && !empty($a['notes']))
      <h2>Internal notes</h2><div class="box">{{ $a['notes'] }}</div>
    @endif
  @endif

  <div class="foot">{{ $footer }}</div>
</div>
</body>
</html>
