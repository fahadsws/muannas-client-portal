@php
  $S = ['todo' => ['To do', '#b8b8c6'], 'ready' => ['Ready', '#7c3aed'], 'done' => ['Done', '#16a765'], 'live' => ['Live', '#0e8a4f']];
  $sec = $cfg['sections'] ?? [];
  $layout = $cfg['layout'];
  $accent = $color ?: '#7c3aed';
  $content = collect($tabs)->filter(fn ($t) => $t['kind'] !== 'strategy' && !($t['meta']['overview'] ?? false));
  $all = $content->flatMap(fn ($t) => $t['items']);
  $count = ['todo' => 0, 'ready' => 0, 'done' => 0, 'live' => 0];
  foreach ($all as $i) { $count[$i['status']] = ($count[$i['status']] ?? 0) + 1; }
  $total = $all->count();
  $fin = $count['done'] + $count['live'];
  $pct = $total ? round($fin / $total * 100) : 0;
  $strategy = collect($tabs)->firstWhere('kind', 'strategy');
  $brand = config('board.brand');
  $footer = str_replace('{brand}', $brand, $cfg['footer'] ?? '');
  $showCharts = in_array('charts', $sec) && ($cfg['charts'] ?? true);
  $plat = ['IG' => 0, 'LI' => 0, 'FB' => 0];
  foreach ($all as $i) { foreach (($i['data']['platforms'] ?? []) as $p) { $plat[$p] = ($plat[$p] ?? 0) + 1; } }
  $maxPlat = max(1, max($plat));
  $bar = function ($vals, $total) use ($S) {
      $h = '<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse"><tr>';
      foreach ($vals as $k => $v) { if ($v > 0 && $total > 0) { $h .= '<td style="width:'.round($v / $total * 100, 2).'%;background:'.$S[$k][1].';height:9px;font-size:1px;line-height:1px">&nbsp;</td>'; } }
      if ($total == 0) { $h .= '<td style="background:#eee;height:9px;font-size:1px">&nbsp;</td>'; }
      return $h.'</tr></table>';
  };
@endphp
<!doctype html>
<html lang="en" @if(!empty($word)) xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" @endif>
<head>
<meta charset="utf-8">
<title>{{ $tpl }} · {{ $account->name }}</title>
<style>
  @page { margin: 34px 38px; }
  * { box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 10px; color: #1c1c28; margin: 0; line-height: 1.45; @if($preview) background:#e9e9f0; @endif }
  .paper { @if($preview) max-width: {{ $cfg['orientation'] === 'landscape' ? '1050px' : '800px' }}; margin: 24px auto; background:#fff; padding: 36px 40px; border:1px solid #d9d9e4; @endif }
  h1 { font-size: 22px; margin: 0 0 3px; letter-spacing: -.3px; }
  h2 { font-size: 13px; margin: 22px 0 8px; padding-bottom: 5px; border-bottom: 1.5px solid {{ $accent }}; color: #1c1c28; }
  h3 { font-size: 11px; margin: 14px 0 6px; color: {{ $accent }}; }
  .muted { color: #7b7b8c; }
  .small { font-size: 8.5px; }
  .tag { font-size: 8px; letter-spacing: 1px; text-transform: uppercase; color: #7b7b8c; }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; font-size: 8px; letter-spacing: .8px; text-transform: uppercase; color: #7b7b8c; padding: 6px 7px; border-bottom: 1px solid #cfcfdb; background: #f6f6fa; }
  td { padding: 6px 7px; border-bottom: 1px solid #ececf3; vertical-align: top; }
  .pill { display: inline-block; padding: 1px 7px; border-radius: 9px; font-size: 8px; color: #fff; }
  .box { border: 1px solid #dcdce8; padding: 9px 11px; margin-bottom: 7px; }
  .kpi td { border: 1px solid #dcdce8; padding: 10px 12px; width: 25%; }
  .kpi b { font-size: 20px; display: block; }
  .ribbon { background: #fff4d6; border: 1px solid #f0d78a; color: #8a6200; padding: 6px 10px; font-size: 9px; margin-bottom: 14px; }
  .sq { display:inline-block; width:9px; height:9px; border:1px solid #888; margin-right:4px; }
  .foot { margin-top: 26px; padding-top: 8px; border-top: 1px solid #dcdce8; font-size: 8px; color: #8d8d9e; }
</style>
</head>
<body>
<div class="paper">

  <table style="margin-bottom:6px"><tr>
    <td style="border:0;padding:0;width:6px;background:{{ $accent }}">&nbsp;</td>
    <td style="border:0;padding:0 0 0 12px">
      <div class="tag">{{ $agency->name }}</div>
      <h1>{{ $tpl }}</h1>
      <div class="muted">{{ $account->name }} &nbsp;·&nbsp; {{ $date }}</div>
    </td>
    <td style="border:0;padding:0;text-align:right;width:140px" class="muted small">
      @if(!empty($cfg['header'])){{ $cfg['header'] }}@endif
    </td>
  </tr></table>

  @if($sample)<div class="ribbon"><b>Sample data.</b> This workspace has no content yet, so example content is shown to preview the layout.</div>@endif

  @if(in_array('strategy', $sec))
    <h2>Strategy</h2>
    @if($strategy && (!empty($strategy['meta']['do']) || !empty($strategy['meta']['pillars'])))
      @php $m = $strategy['meta']; @endphp
      @if(!empty($m['do']) || !empty($m['dont']))
      <table><tr>
        <td style="width:50%;border:1px solid #dcdce8"><h3 style="margin-top:0;color:#16a765">Do</h3>@foreach(($m['do'] ?? []) as $x)<div>• {{ $x }}</div>@endforeach</td>
        <td style="width:50%;border:1px solid #dcdce8"><h3 style="margin-top:0;color:#dc4a46">Don't</h3>@foreach(($m['dont'] ?? []) as $x)<div>• {{ $x }}</div>@endforeach</td>
      </tr></table>
      @endif
      @if(!empty($m['pillars']))
        <h3>Content pillars</h3>
        <table>@foreach(array_chunk($m['pillars'], 3) as $row)<tr>@foreach($row as $p)<td style="width:33%;border:1px solid #dcdce8"><b>{{ $p[0] }}</b><br><span class="muted">{{ $p[1] ?? '' }}</span></td>@endforeach @for($k = count($row); $k < 3; $k++)<td style="border:0"></td>@endfor</tr>@endforeach</table>
      @endif
      @if(!empty($m['arc']))
        <h3>{{ $m['arc_title'] ?? 'Campaign arc' }}</h3>
        <table><tr>@foreach($m['arc'] as $a)<td style="border:1px solid #dcdce8"><span class="tag">{{ $a[0] }}</span><br><b>{{ $a[1] ?? '' }}</b><br><span class="muted">{{ $a[2] ?? '' }}</span></td>@endforeach</tr></table>
      @endif
      @if(!empty($m['mix']))
        <h3>Content mix</h3>
        <table><tr>@foreach($m['mix'] as $x)<td style="border:1px solid #dcdce8"><b style="font-size:16px;color:{{ $accent }}">{{ $x[2] }}</b><br>{{ $x[0] }}<br><span class="muted small">{{ $x[1] ?? '' }}</span></td>@endforeach</tr></table>
      @endif
    @else
      <p class="muted">No strategy has been written for this workspace yet.</p>
    @endif
  @endif

  @if(in_array('kpis', $sec))
    <h2>Progress summary</h2>
    <table class="kpi"><tr>
      <td><span class="tag">Total items</span><b>{{ $total }}</b><span class="muted small">{{ $pct }}% complete</span></td>
      <td><span class="tag">To do</span><b>{{ $count['todo'] }}</b></td>
      <td><span class="tag">Ready for approval</span><b style="color:#7c3aed">{{ $count['ready'] }}</b></td>
      <td><span class="tag">Done &amp; live</span><b style="color:#16a765">{{ $fin }}</b><span class="muted small">{{ $count['live'] }} live</span></td>
    </tr></table>
  @endif

  @if($showCharts)
    <h2>Charts</h2>
    <h3 style="margin-top:4px">Status distribution</h3>
    {!! $bar($count, max($total, 0)) !!}
    <table style="margin-top:4px"><tr>@foreach($S as $k => $v)<td style="border:0;padding:3px 0;width:25%"><span style="display:inline-block;width:8px;height:8px;background:{{ $v[1] }}"></span> {{ $v[0] }} <b>{{ $count[$k] }}</b></td>@endforeach</tr></table>

    <h3>Progress by section</h3>
    <table>
      @foreach($content as $t)
        @php $c = ['todo'=>0,'ready'=>0,'done'=>0,'live'=>0]; foreach ($t['items'] as $i) { $c[$i['status']]++; } $n = count($t['items']); @endphp
        <tr><td style="width:22%;border:0;padding:4px 7px">{{ $t['label'] }}</td><td style="border:0;padding:4px 7px">{!! $bar($c, $n) !!}</td><td style="width:9%;border:0;padding:4px 7px;text-align:right" class="muted">{{ $n }}</td></tr>
      @endforeach
    </table>

    <h3>Platform mix</h3>
    <table>@foreach($plat as $p => $n)
      <tr><td style="width:10%;border:0;padding:3px 7px"><b>{{ $p }}</b></td>
      <td style="border:0;padding:3px 7px"><table width="100%"><tr><td style="width:{{ max(1, round($n / $maxPlat * 100)) }}%;background:{{ $accent }};height:9px;font-size:1px;border:0;padding:0">&nbsp;</td><td style="border:0;padding:0"></td></tr></table></td>
      <td style="width:9%;border:0;padding:3px 7px;text-align:right" class="muted">{{ $n }}</td></tr>
    @endforeach</table>
  @endif

  @if(in_array('tabs', $sec))
    <h2>Content by section</h2>
    @foreach($content as $t)
      <h3>{{ $t['label'] }} <span class="muted small">· {{ count($t['items']) }} items</span></h3>
      @if($layout === 'detailed')
        @foreach($t['items'] as $i)
          @php $x = $i['data']; @endphp
          <div class="box">
            <span class="tag">{{ $x['label'] ?? '' }} @if(!empty($x['day'])) · {{ $x['day'] }} @endif</span>
            <span class="pill" style="background:{{ $S[$i['status']][1] }};float:right">{{ $S[$i['status']][0] }}</span><br>
            <b>{{ $x['caption'] ?? '' }}</b>
            @if(!empty($x['visual']))<br><span class="muted"><i>Visual: {{ $x['visual'] }}</i></span>@endif
            @if(!empty($x['platforms']))<br><span class="small muted">{{ implode(' · ', $x['platforms']) }} @if(!empty($x['time'])) &nbsp;|&nbsp; {{ $x['time'] }} @endif</span>@endif
            @if(($cfg['show_notes'] ?? true) && !empty($x['notes']))<br><span class="small" style="color:{{ $accent }}">Note: {{ $x['notes'] }}</span>@endif
          </div>
        @endforeach
      @else
        <table>
          <tr><th style="width:13%">Item</th><th>Caption</th><th style="width:15%">Platforms</th><th style="width:11%">Status</th>@if($cfg['show_notes'] ?? true)<th style="width:22%">Notes</th>@endif</tr>
          @foreach($t['items'] as $i)
            @php $x = $i['data']; @endphp
            <tr><td class="muted">{{ $x['label'] ?? '' }}</td><td>{{ $x['caption'] ?? '' }}</td><td class="muted">{{ implode(' · ', $x['platforms'] ?? []) }}</td>
            <td><span class="pill" style="background:{{ $S[$i['status']][1] }}">{{ $S[$i['status']][0] }}</span></td>@if($cfg['show_notes'] ?? true)<td class="muted small">{{ $x['notes'] ?? '' }}</td>@endif</tr>
          @endforeach
          @if(!count($t['items']))<tr><td colspan="5" class="muted">No items.</td></tr>@endif
        </table>
      @endif
    @endforeach
    @if(!$content->count())<p class="muted">No content sections yet.</p>@endif
  @endif

  @if(in_array('calendar', $sec))
    <h2>Content calendar</h2>
    <table>
      <tr><th style="width:14%">When</th><th style="width:12%">Section</th><th style="width:9%">Item</th><th>Caption</th><th style="width:11%">Platforms</th><th style="width:9%">Status</th>@if($cfg['show_notes'] ?? true)<th style="width:16%">Notes</th>@endif</tr>
      @foreach($content as $t) @foreach($t['items'] as $i) @php $x = $i['data']; @endphp
        <tr><td>{{ $x['day'] ?? '' }}<br><span class="muted small">{{ $x['time'] ?? '' }}</span></td><td class="muted">{{ $t['label'] }}</td><td class="muted">{{ $x['label'] ?? '' }}</td><td>{{ $x['caption'] ?? '' }}</td><td class="muted">{{ implode(' · ', $x['platforms'] ?? []) }}</td>
        <td><span class="pill" style="background:{{ $S[$i['status']][1] }}">{{ $S[$i['status']][0] }}</span></td>@if($cfg['show_notes'] ?? true)<td class="muted small">{{ $x['notes'] ?? '' }}</td>@endif</tr>
      @endforeach @endforeach
      @if(!$all->count())<tr><td colspan="7" class="muted">No items yet.</td></tr>@endif
    </table>
  @endif

  @if(in_array('approval', $sec))
    <h2>Client approval</h2>
    <p class="muted">Please review each item below and mark your decision.</p>
    @php $ready = $content->flatMap(fn ($t) => collect($t['items'])->where('status', 'ready')->map(fn ($i) => $i + ['tab' => $t['label']])); @endphp
    <table>
      <tr><th style="width:15%">Item</th><th>Caption</th><th style="width:22%">Decision</th><th style="width:22%">Comments</th></tr>
      @foreach($ready as $i) @php $x = $i['data']; @endphp
        <tr><td class="muted">{{ $i['tab'] }}<br>{{ $x['label'] ?? '' }}</td><td>{{ $x['caption'] ?? '' }}</td><td><span class="sq"></span>Approve<br><span class="sq"></span>Changes</td><td>&nbsp;<br>&nbsp;</td></tr>
      @endforeach
      @if(!$ready->count())<tr><td colspan="4" class="muted">Nothing is waiting for approval.</td></tr>@endif
    </table>
    <table style="margin-top:26px"><tr><td style="border:0;width:45%;border-top:1px solid #888;padding-top:4px" class="muted small">Client signature</td><td style="border:0"></td><td style="border:0;width:25%;border-top:1px solid #888;padding-top:4px" class="muted small">Date</td></tr></table>
  @endif

  <div class="foot">{{ $footer }}</div>
</div>
</body>
</html>
