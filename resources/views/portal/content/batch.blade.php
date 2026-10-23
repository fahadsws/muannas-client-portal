@extends('portal.layout')
@section('title', $batch->title)
@section('content')
@php use App\Support\Ui; @endphp
<div class="pagehead">
  <div><h1>{{ $batch->title }}</h1><p>{{ $account->name }} · sent {{ $batch->sent_at?->format('j M Y') }} · {{ $posts->count() }} {{ \Illuminate\Support\Str::plural('post', $posts->count()) }}</p></div>
  <a class="btn" href="/portal/c/{{ $account->id }}/content">All content</a>
</div>
@forelse($posts as $post)
  @php $video = \App\Models\Post::videoInfo($post->video_url); @endphp
  <div class="pn" style="margin-bottom:20px">
    <div class="pd">
      <div>@include('portal.partials.media', ['post' => $post, 'video' => $video])</div>
      <div class="stack">
        <div class="prow"><span class="stag s-{{ $post->status }}">{{ $post->statusLabel() }}</span><span class="round">Round {{ $post->round }}</span></div>
        <h2>{{ $post->title }}</h2>
        <div class="pmeta"><span>{{ $post->platformLabel() }}</span><span>{{ $post->scheduled_on ? $post->scheduled_on->format('D, j M Y') : 'No date set' }}</span></div>
        @if($post->caption)<div class="caption">{{ $post->caption }}</div>@endif
        @if($canAct && $post->status === 'pending')
          <div class="actions">
            <form method="post" action="/portal/posts/{{ $post->id }}/approve" style="flex:1">@csrf<button class="btn ok block">{!! Ui::icon('check') !!}Approve</button></form>
          </div>
          <details>
            <summary class="btn warn block" style="list-style:none">Request Changes</summary>
            <form method="post" action="/portal/posts/{{ $post->id }}/changes" style="margin-top:12px">@csrf
              <div class="fld"><textarea name="body" required maxlength="2000" placeholder="What should we change?"></textarea></div><button class="btn warn">Send feedback</button></form>
          </details>
        @endif
        <a class="plink" href="/portal/posts/{{ $post->id }}" style="align-self:flex-start">Comments &amp; history {!! \App\Support\Ui::icon('chev-r') !!}</a>
      </div>
    </div>
  </div>
@empty
  <div class="empty"><div class="eic">{!! \App\Support\Ui::icon('photo') !!}</div><h3>Nothing to review</h3><p>This batch has no posts waiting.</p></div>
@endforelse
@endsection
