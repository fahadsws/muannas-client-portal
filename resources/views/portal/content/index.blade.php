@extends('portal.layout')
@section('title', 'Content Review')
@section('content')
@php use App\Models\Post; use App\Support\Ui; $total = $counts->sum(); @endphp
<div class="pagehead">
  <div><h1>Content Review</h1><p>Posts as they will appear on the grid. Tap a post to review it.</p></div>
  @if($manage)
    <div class="prow">
      <a class="btn" href="/portal/c/{{ $account->id }}/content/bulk">{!! Ui::icon('upload') !!}Bulk upload</a>
      <a class="btn primary" href="/portal/c/{{ $account->id }}/content/new">{!! Ui::icon('plus') !!}New post</a>
    </div>
  @endif
</div>

<div class="prow between" style="margin-bottom:18px;align-items:flex-start">
  <div class="pseg" style="margin:0">
    <a class="{{ ! $filter ? 'on' : '' }}" href="{{ Ui::with(['status' => null]) }}">All<em>{{ $total }}</em></a>
    @foreach(Post::STATUSES as $k => $s)
      @continue(! $manage && $k === 'draft')
      <a class="{{ $filter === $k ? 'on' : '' }}" href="{{ Ui::with(['status' => $k]) }}">{{ $s[0] }}<em>{{ $counts[$k] ?? 0 }}</em></a>
    @endforeach
  </div>
  <form method="get" action="/portal/c/{{ $account->id }}/content" class="prow" id="rangeform" style="gap:8px">
    @if($filter)<input type="hidden" name="status" value="{{ $filter }}">@endif
    <input type="hidden" name="from" value="{{ request('from') }}"><input type="hidden" name="to" value="{{ request('to') }}">
    <div class="rangewrap">{!! Ui::icon('cal') !!}<input type="text" data-range placeholder="Planned date range"></div>
    @if(request('from') || request('to'))<a class="btn grey sm" href="{{ Ui::with(['from' => null, 'to' => null]) }}">{!! Ui::icon('x') !!}Clear</a>@endif
  </form>
</div>

@if($posts->isEmpty())
  <div class="empty"><div class="eic">{!! \App\Support\Ui::icon('photo') !!}</div><h3>No posts here yet</h3><p>@if($manage)Add a post or bulk upload a batch of images to get started.@else Your team will add content for you to review.@endif</p></div>
@else
  @if($manage)
  <form method="post" action="/portal/c/{{ $account->id }}/content/send" id="sendform" data-confirm="Send the selected posts to the client for review? They will be emailed.">@csrf
    <div class="pn prow between" style="margin-bottom:14px">
      <label class="check"><input type="checkbox" data-pick-all> Select all drafts</label>
      <div class="inl"><input name="title" placeholder="Batch name (optional)" maxlength="120" style="max-width:260px"><button class="btn primary sm">Send for review</button></div>
    </div>
  </form>
  @endif
  <div class="igrid">
    @foreach($posts as $p)
      <div class="tw">
        <a class="tile" href="/portal/posts/{{ $p->id }}" title="{{ $p->title }}">
          @if($p->cover())<img src="{{ $p->cover() }}" alt="{{ $p->title }}" loading="lazy">@else<span class="ph">{{ \Illuminate\Support\Str::limit($p->title, 40) }}</span>@endif
          @if($p->scheduled_on)<span class="when">{{ $p->scheduled_on->format('j M') }}</span>@endif
          @if($p->type !== 'image')<span class="tag">{!! Ui::icon($p->type === 'reel' ? 'video' : 'layers') !!}</span>@endif
          <span class="pbadge stag s-{{ $p->status }}" style="background:#fffdf8">{{ $p->statusLabel() }}@if($p->round > 1) · R{{ $p->round }}@endif</span>
        </a>
        @if($manage && in_array($p->status, ['draft', 'changes']))
          <label class="pick"><input type="checkbox" name="ids[]" value="{{ $p->id }}" form="sendform"> Send</label>
        @endif
      </div>
    @endforeach
  </div>
  @include('partials.pager', ['p' => $posts])
@endif

@if($batches->isNotEmpty())
  <div class="pn" style="margin-top:28px">
    <h3>Recent review links</h3>
    @foreach($batches as $b)
      <div class="file"><div class="nm"><b>{{ $b->title }}</b><small>Sent {{ $b->sent_at?->format('j M Y, g:i A') }}</small></div>
        <button type="button" class="btn sm" data-copy="{{ $b->url() }}">Copy link</button>
        <button type="button" class="btn sm" data-wa="Hi, your content is ready for review: {{ $b->url() }}">WhatsApp</button></div>
    @endforeach
  </div>
@endif
@push('scripts')
<script>
  var rg = document.querySelector('#rangeform input[data-range]');
  if (rg) rg.addEventListener('change', function () { setTimeout(function () { document.getElementById('rangeform').requestSubmit(); }, 80); });
</script>
@endpush
@endsection
