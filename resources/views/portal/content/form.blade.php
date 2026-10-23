@extends('portal.layout')
@section('title', $post ? 'Edit post' : 'New post')
@section('content')
@php use App\Models\Post; $v = fn ($k, $d = '') => old($k, $post?->{$k} ?? $d); @endphp
<a class="back" href="{{ $post ? '/portal/posts/'.$post->id : '/portal/c/'.$account->id.'/content' }}">{!! \App\Support\Ui::icon('arrow') !!}Back</a>
<div class="pagehead"><h1>{{ $post ? 'Edit post' : 'New post' }}</h1></div>
@if($post && $post->status === 'approved')<div class="notice" style="background:#fff4e0">This post is approved. Changing it sends it back to draft so the client can approve the new version.</div>@endif
<form class="pn" method="post" enctype="multipart/form-data" action="{{ $post ? '/portal/posts/'.$post->id : '/portal/c/'.$account->id.'/content' }}" style="max-width:760px">
  @csrf @if($post) @method('PUT') @endif
  <div class="fld"><label for="title">Title (internal name)</label><input id="title" name="title" value="{{ $v('title') }}" maxlength="120" required></div>
  <div class="two">
    <div class="fld"><label for="platform">Platform</label><select id="platform" name="platform">@foreach(Post::PLATFORMS as $k => $l)<option value="{{ $k }}" @selected($v('platform', 'instagram') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="fld"><label for="scheduled_on">Planned date</label><input id="scheduled_on" type="date" name="scheduled_on" value="{{ old('scheduled_on', $post?->scheduled_on?->format('Y-m-d')) }}"></div>
  </div>
  <div class="fld"><label for="caption">Caption</label><textarea id="caption" name="caption" maxlength="3000" style="min-height:150px">{{ $v('caption') }}</textarea></div>

  <div class="fld"><label>Image or carousel</label>
    @if($post && count($post->media ?? []))
      <div class="thumbs">@foreach($post->mediaUrls() as $i => $u)<div class="th"><img src="{{ $u }}" alt=""><label><input type="checkbox" name="remove[]" value="{{ $i }}"> remove</label></div>@endforeach</div>
    @endif
    <x-file-input name="images" multiple label="Attach images" accept="image/jpeg,image/png,image/webp,image/gif" />
    <div style="margin-top:8px"></div>
    <div class="hint">JPG, PNG, WebP or GIF, up to {{ config('portal.max_image_kb') / 1024 }} MB each, max {{ config('portal.max_post_images') }} images. More than one makes a carousel. Add them in the order they should appear.</div>
  </div>

  <div class="fld"><label for="video_url">Reel link (optional)</label><input id="video_url" type="url" name="video_url" value="{{ $v('video_url') }}" placeholder="https://drive.google.com/file/d/… or YouTube / Frame.io / Vimeo">
    <div class="hint">Reels are shared by link, not uploaded. Use a cover image above for the grid tile. Google Drive files must be shared as "anyone with the link".</div></div>
  <div class="actions"><button class="btn primary">{{ $post ? 'Save changes' : 'Save as draft' }}</button><a class="btn" href="{{ $post ? '/portal/posts/'.$post->id : '/portal/c/'.$account->id.'/content' }}">Cancel</a></div>
</form>
@endsection
