@extends('portal.layout')
@section('title', $post->title)
@section('content')
@php use App\Support\Ui; @endphp
<a class="back" href="/portal/c/{{ $account->id }}/content">{!! Ui::icon('arrow') !!} Back to content</a>
<div class="pd">
  <div>@include('portal.partials.media', ['post' => $post, 'video' => $video])</div>

  <div class="stack">
    <div>
      <div class="prow" style="margin-bottom:8px"><span class="stag s-{{ $post->status }}">{{ $post->statusLabel() }}</span>@if($post->status !== 'draft' || $post->round > 1)<span class="round">Round {{ $post->round }}</span>@endif</div>
      <h1>{{ $post->title }}</h1>
      <div class="pmeta" style="margin-top:8px">
        <span>{{ $post->platformLabel() }}</span>
        <span>{{ $post->type === 'reel' ? 'Reel' : ($post->type === 'carousel' ? 'Carousel' : 'Image post') }}</span>
        <span>{!! Ui::icon('cal') !!} {{ $post->scheduled_on ? $post->scheduled_on->format('l, j F Y') : 'No date set' }}</span>
      </div>
    </div>

    @if($post->caption)<div class="pn"><small>Caption</small><div class="caption">{{ $post->caption }}</div></div>@endif

    {{-- client decision --}}
    @if($canAct && $post->status === 'pending')
      <div class="pn" style="border-color:var(--amber)">
        <h3>Your decision</h3>
        <div class="actions" style="margin:12px 0">
          <form method="post" action="/portal/posts/{{ $post->id }}/approve" style="flex:1">@csrf<button class="btn ok block">{!! Ui::icon('check') !!}Approve</button></form>
        </div>
        <details>
          <summary class="btn warn block" style="list-style:none">Request Changes</summary>
          <form method="post" enctype="multipart/form-data" action="/portal/posts/{{ $post->id }}/changes" style="margin-top:12px">@csrf
            <div class="fld"><label for="why">What should we change?</label><textarea id="why" name="body" required maxlength="2000" placeholder="e.g. Use the second photo and shorten the caption"></textarea></div>
            <div class="fld"><label>Reference image (optional)</label><x-image-input name="image" label="Add a reference image" hint="Show us what you mean · PNG, JPG or WEBP" /></div>
            <button class="btn warn">Send feedback</button>
          </form>
        </details>
      </div>
    @elseif($canAct && $post->status === 'changes')
      <div class="notice" style="background:var(--red-soft)">You asked for changes. The team will send an updated version for your review.</div>
    @elseif($canAct && in_array($post->status, ['approved', 'published']))
      @php $ap = $post->events->where('action', 'approved')->last(); @endphp
      <div class="notice ok">{{ $post->status === 'published' ? 'Approved and published.' : 'Approved' }}@if($ap) by {{ $ap->actor }} on {{ $ap->created_at->format('j M Y, g:i A') }}@endif.</div>
    @endif

    {{-- team actions --}}
    @if($manage)
      <div class="pn">
        <small>Team actions</small>
        <div class="actions" style="margin-top:8px">
          <a class="btn sm" href="/portal/posts/{{ $post->id }}/edit">{!! Ui::icon('edit') !!}Edit</a>
          @if(in_array($post->status, ['draft', 'changes']))
            <form method="post" action="/portal/posts/{{ $post->id }}/status" data-confirm="Send this post to the client for review?">@csrf<input type="hidden" name="do" value="send"><button class="btn sm primary">Send for review</button></form>
          @endif
          @if($post->status === 'approved')
            <form method="post" action="/portal/posts/{{ $post->id }}/status">@csrf<input type="hidden" name="do" value="publish"><button class="btn sm ok">Mark published</button></form>
          @endif
          @if($post->status === 'published')
            <form method="post" action="/portal/posts/{{ $post->id }}/status" data-confirm="Move back to Approved?">@csrf<input type="hidden" name="do" value="unpublish"><button class="btn sm">Un-publish</button></form>
          @endif
          @if(in_array($post->status, ['pending', 'changes', 'approved']))
            <form method="post" action="/portal/posts/{{ $post->id }}/status" data-confirm="Pull this back to draft? The client will no longer see it.">@csrf<input type="hidden" name="do" value="reopen"><button class="btn sm">Back to draft</button></form>
          @endif
          <form method="post" action="/portal/posts/{{ $post->id }}" data-confirm="Delete this post and its images permanently?">@csrf @method('DELETE')<button class="btn sm danger">{!! Ui::icon('trash') !!}Delete</button></form>
        </div>
        @if($post->batch)
          <div class="copybox" style="margin-top:12px"><code>{{ $post->batch->url() }}</code><button type="button" class="btn sm" data-copy="{{ $post->batch->url() }}">Copy</button></div>
        @endif
      </div>
    @endif

    <div class="pn">
      <h3>Comments</h3>
      @forelse($post->comments as $c)
        <div class="cmt">
          <div class="h"><b>{{ $c->author }}</b><span class="tag {{ $c->role }}">{{ $c->role === 'client' ? 'Client' : 'Muannas' }}</span><small>{{ $c->created_at->format('j M, g:i A') }}</small></div>
          <div class="caption">{{ $c->body }}</div>
          @if($c->image)<a href="/portal/comments/{{ $c->id }}/image" target="_blank" rel="noopener"><img src="/portal/comments/{{ $c->id }}/image" alt="Reference image" style="margin-top:8px;max-width:100%;max-height:260px;border-radius:8px;border:1px solid var(--border)"></a>@endif
        </div>
      @empty
        <p class="mute" style="margin:0 0 8px">No comments yet.</p>
      @endforelse
      <form method="post" enctype="multipart/form-data" action="/portal/posts/{{ $post->id }}/comments" style="margin-top:14px">@csrf
        <div class="fld"><textarea name="body" required maxlength="2000" placeholder="Write a comment…" style="min-height:76px"></textarea></div>
        <div class="fld"><x-image-input name="image" label="Attach an image (optional)" hint="PNG, JPG or WEBP" /></div>
        <button class="btn sm primary">Post comment</button>
      </form>
    </div>

    @if($post->events->isNotEmpty())
      <div class="pn">
        <h3>Approval history</h3>
        <ul class="tl" style="margin-top:10px">
          @foreach($post->events->reverse() as $e)
            <li class="{{ $e->action }}"><b>{{ $e->label() }}</b> by {{ $e->actor }} · Round {{ $e->round }}<br><small>{{ $e->created_at->format('j M Y, g:i A') }}</small></li>
          @endforeach
        </ul>
      </div>
    @endif
  </div>
</div>
@endsection
