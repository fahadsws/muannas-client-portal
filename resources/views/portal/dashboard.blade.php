@extends('portal.layout')
@section('title', 'Client Dashboard')
@section('content')
@php
  use App\Models\Project;
  use App\Support\Ui;
  $first = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
  $base = '/portal/c/'.$account->id;
  $overdueCount = $outstanding->filter(fn ($i) => $i->status() === 'overdue')->count();
  $owed = $outstanding->sum('amount');
  $delta = function ($now, $before) { return ($before ?? 0) > 0 ? round(($now - $before) / $before * 100) : null; };
@endphp

{{-- welcome --}}
<div class="hero">
  <span class="plogo big">@if($account->logo)<img src="{{ $account->logoUrl() }}" alt="">@else{{ Ui::initials($account->name) }}@endif</span>
  <div class="hero-t">
    @if($manage)
      <h1>{{ $account->name }}</h1><p>Client dashboard</p>
    @else
      <h1>{{ $greeting }}, {{ $first }}</h1>
      <p>{{ $account->name }} ·
        @if($summary['pending'] > 0)<b>{{ $summary['pending'] }} {{ \Illuminate\Support\Str::plural('post', $summary['pending']) }}</b> waiting for your approval.
        @else You are all caught up.@endif</p>
    @endif
  </div>
  <div class="hero-a">
    @if($manage)
      <a class="btn" href="/portal/clients/{{ $account->id }}">{!! Ui::icon('edit') !!}Edit client</a>
      <a class="btn primary" href="{{ $base }}/content/new">{!! Ui::icon('plus') !!}New post</a>
    @elseif($summary['pending'] > 0)
      <a class="btn primary" href="{{ $base }}/content?status=pending">Review now {!! Ui::icon('chev-r') !!}</a>
    @endif
  </div>
</div>

{{-- summary counts --}}
<div class="kpis">
  <a class="kpi k-pending" href="{{ $base }}/content?status=pending"><small>Pending approvals</small><b>{{ $summary['pending'] }}</b><div class="sub">Waiting for you</div></a>
  <a class="kpi k-approved" href="{{ $base }}/content?status=approved"><small>Approved</small><b>{{ $summary['approved'] }}</b><div class="sub">Signed off, ready to go</div></a>
  <a class="kpi k-progress" href="{{ $base }}/content?status=changes"><small>In progress</small><b>{{ $summary['progress'] }}</b><div class="sub">Our team is working on it</div></a>
  <a class="kpi k-published" href="{{ $base }}/content?status=published"><small>Published</small><b>{{ $summary['published'] }}</b><div class="sub">Live on your channels</div></a>
</div>

@if($pipeline->sum() > 0)
<div class="chartrow">
  <div class="chartcard"><h3>Content pipeline</h3><small>Posts by status</small>
    @php $pmax = max(1, $pipeline->max()); @endphp
    <div class="cols">@foreach($pipeline as $k => $n)<div class="c" title="{{ \App\Models\Post::STATUSES[$k][0] }}: {{ $n }}"><em>{{ $n }}</em><div class="stk" style="height:{{ max(6, $n / $pmax * 100) }}%"><i class="s-{{ $k }}" style="height:100%"></i></div><span>{{ \App\Models\Post::STATUSES[$k][0] }}</span></div>@endforeach</div></div>
  <div class="chartcard"><h3>Posts per month</h3><small>By planned date, last 6 months</small>
    @php $mmax = max(1, $monthly->max('n')); @endphp
    <div class="cols">@foreach($monthly as $m)<div class="c" title="{{ $m['label'] }}: {{ $m['n'] }} posts"><em>{{ $m['n'] }}</em><div class="stk" style="height:{{ max(4, $m['n'] / $mmax * 100) }}%"><i class="bar1" style="height:100%"></i></div><span>{{ $m['label'] }}</span></div>@endforeach</div></div>
</div>
@endif

<div class="grid g-main">
  <div class="stack">
    {{-- needs attention --}}
    <div class="pn">
      <div class="phead"><div><h3>{{ $manage ? 'Waiting on review' : 'Needs your approval' }}</h3><small>{{ $manage ? 'Sent to the client, not yet signed off' : 'Approve in one tap, or ask for changes' }}</small></div>
        <a class="plink" href="{{ $base }}/content">All content {!! Ui::icon('chev-r') !!}</a></div>
      @forelse($attention as $p)
        <div class="item">
          <a href="/portal/posts/{{ $p->id }}" class="thumb">@if($p->cover())<img src="{{ $p->cover() }}" alt="">@else{!! Ui::icon($p->type === 'reel' ? 'video' : 'photo') !!}@endif</a>
          <div class="nm"><a class="tl-t" href="/portal/posts/{{ $p->id }}">{{ $p->title }}</a>
            <small>{{ $p->platformLabel() }}@if($p->scheduled_on) · {{ $p->scheduled_on->format('D j M') }}@endif @if($p->round > 1)· Round {{ $p->round }}@endif</small></div>
          @if($canAct && $p->status === 'pending')
            <a class="btn sm" href="/portal/posts/{{ $p->id }}">Review</a>
            <form method="post" action="/portal/posts/{{ $p->id }}/approve" style="margin:0">@csrf<button class="btn sm ok">{!! Ui::icon('check') !!}Approve</button></form>
          @else
            <span class="stag s-{{ $p->status }}">{{ $p->statusLabel() }}</span>
          @endif
        </div>
      @empty
        <div class="calm">{!! Ui::icon('check') !!}<div><b>Nothing is waiting right now</b><small>New posts will show up here for your approval.</small></div></div>
      @endforelse
    </div>

    {{-- coming up --}}
    <div class="pn">
      <div class="phead"><div><h3>Coming up</h3><small>Next scheduled posts</small></div><a class="plink" href="{{ $base }}/calendar">Content Calendar {!! Ui::icon('chev-r') !!}</a></div>
      @forelse($upcoming as $p)
        <a class="item link" href="/portal/posts/{{ $p->id }}">
          <span class="dchip"><b>{{ $p->scheduled_on->format('j') }}</b>{{ $p->scheduled_on->format('M') }}</span>
          <div class="nm"><span class="tl-t">{{ $p->title }}</span><small>{{ $p->platformLabel() }} · {{ $p->type === 'reel' ? 'Reel' : ($p->type === 'carousel' ? 'Carousel' : 'Image post') }}</small></div>
          <span class="stag s-{{ $p->status }}">{{ $p->statusLabel() }}</span>
        </a>
      @empty
        <p class="mute" style="margin:6px 0 0">No upcoming posts are scheduled.</p>
      @endforelse
    </div>

    {{-- activity --}}
    <div class="pn">
      <div class="phead"><div><h3>Recent activity</h3><small>Approvals, feedback and updates</small></div></div>
      @forelse($activity as $a)
        <a class="feed {{ $a['kind'] }}" href="/portal/posts/{{ $a['post'] }}">
          <span class="fdot"></span>
          <div><b>{{ $a['who'] }}</b> {{ $a['text'] }}<small>{{ $a['role'] === 'client' ? 'Client' : 'Muannas' }} · {{ $a['at']->diffForHumans() }}</small></div>
        </a>
      @empty
        <p class="mute" style="margin:6px 0 0">Activity will appear here once content is shared with you.</p>
      @endforelse
    </div>
  </div>

  <div class="stack">
    {{-- performance --}}
    <div class="pn">
      <div class="phead"><div><h3>Performance</h3><small>{{ $report ? $report->month->format('F Y') : 'Monthly results' }}</small></div><a class="plink" href="{{ $base }}/reports">Reports {!! Ui::icon('chev-r') !!}</a></div>
      @if($report)
        @php $dr = $delta($report->reach, $previous?->reach); @endphp
        <div class="mini">
          <div><small>Reach</small><b>{{ number_format($report->reach) }}</b>@if($dr !== null)<span class="delta {{ $dr >= 0 ? 'up' : 'down' }}">{{ $dr >= 0 ? '▲' : '▼' }} {{ abs($dr) }}%</span>@endif</div>
          <div><small>Engagement</small><b>{{ rtrim(rtrim(number_format($report->engagement, 2), '0'), '.') }}%</b>@if($previous)@php $de = round($report->engagement - $previous->engagement, 1); @endphp<span class="delta {{ $de >= 0 ? 'up' : 'down' }}">{{ $de >= 0 ? '▲' : '▼' }} {{ abs($de) }} pts</span>@endif</div>
          <div><small>Follower growth</small><b class="{{ $report->follower_growth < 0 ? 'neg' : 'pos' }}">{{ $report->follower_growth > 0 ? '+' : '' }}{{ number_format($report->follower_growth) }}</b></div>
          <div><small>Top post</small><b class="txt">{{ $report->top_post ?: '—' }}</b></div>
        </div>
      @else
        <p class="mute" style="margin:6px 0 0">Your first monthly report will appear here.</p>
      @endif
    </div>

    {{-- projects --}}
    <div class="pn">
      <div class="phead"><div><h3>Projects</h3><small>Where each piece of work stands</small></div><a class="plink" href="{{ $base }}/projects">View all {!! Ui::icon('chev-r') !!}</a></div>
      @forelse($projects as $pr)
        <div class="proj">
          <div class="prow between"><b>{{ $pr->name }}</b><span class="stag s-{{ $pr->status }}">{{ Project::STATUSES[$pr->status] }}</span></div>
          <div class="prow" style="margin-top:8px;gap:10px"><div class="pmeter" style="flex:1"><i style="width:{{ $pr->progress() }}%"></i></div><small>{{ $pr->progress() }}%</small></div>
          <small>{{ $pr->tasks->where('status', 'done')->count() }} of {{ $pr->tasks->count() }} tasks done @if($pr->due_on)· Due {{ $pr->due_on->format('j M') }}@endif</small>
        </div>
      @empty
        <p class="mute" style="margin:6px 0 0">No active projects.</p>
      @endforelse
    </div>

    {{-- invoices --}}
    <div class="pn">
      <div class="phead"><div><h3>Payments & Invoices</h3><small>@if($outstanding->isEmpty())All paid up @else Outstanding {{ $account->currency }} {{ number_format($owed, 2) }}@if($overdueCount) · <span style="color:var(--red)">{{ $overdueCount }} overdue</span>@endif @endif</small></div><a class="plink" href="{{ $base }}/invoices">All invoices {!! Ui::icon('chev-r') !!}</a></div>
      @forelse($outstanding->take(3) as $i)
        <div class="item">
          <div class="nm"><b>{{ $i->number }}</b><small>Due {{ $i->due_on->format('j M Y') }}</small></div>
          <div style="text-align:right"><b>{{ $i->money() }}</b><br><span class="stag s-{{ $i->status() }}">{{ $i->statusLabel() }}</span></div>
          <a class="ibtn act" href="/portal/invoices/{{ $i->id }}/pdf" title="Download PDF">{!! Ui::icon('dl') !!}</a>
        </div>
      @empty
        <div class="calm">{!! Ui::icon('check') !!}<div><b>Nothing to pay</b><small>Thank you. Your invoices are up to date.</small></div></div>
      @endforelse
    </div>

    {{-- shortcuts --}}
    <div class="pn">
      <div class="phead"><div><h3>Shortcuts</h3></div></div>
      <div class="qa">
        <a href="{{ $base }}/files">{!! Ui::icon('folder') !!}<span>Files &amp; Assets</span></a>
        <a href="{{ $base }}/calendar">{!! Ui::icon('cal') !!}<span>Content Calendar</span></a>
        <a href="{{ $base }}/projects">{!! Ui::icon('tasks') !!}<span>Projects &amp; Tasks</span></a>
        <a href="{{ $base }}/reports">{!! Ui::icon('chart') !!}<span>Performance Reports</span></a>
      </div>
    </div>
  </div>
</div>
@endsection
