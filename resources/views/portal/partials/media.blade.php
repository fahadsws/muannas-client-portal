@php use App\Support\Ui; $urls = $post->mediaUrls(); @endphp
@if(count($urls))
  <div class="car">
    <div class="track">@foreach($urls as $u)<img src="{{ $u }}" alt="{{ $post->title }}" loading="lazy">@endforeach</div>
    @if(count($urls) > 1)
      <button type="button" class="cnav l" aria-label="Previous">{!! Ui::icon('chev-l') !!}</button>
      <button type="button" class="cnav r" aria-label="Next">{!! Ui::icon('chev-r') !!}</button>
      <span class="count"></span>
    @endif
  </div>
@elseif(! $video)
  <div class="noimg">{{ $post->title }}</div>
@endif
@if($video)
  @if($video['embed'])
    <div class="video"><iframe src="{{ $video['embed'] }}" allow="autoplay; encrypted-media; fullscreen; picture-in-picture" allowfullscreen loading="lazy" referrerpolicy="no-referrer"></iframe></div>
  @endif
  <div class="prow" style="margin-top:10px">
    <a class="btn sm" href="{{ $video['open'] }}" target="_blank" rel="noopener noreferrer">{!! Ui::icon('play') !!}{{ $video['embed'] ? 'Open reel in new tab' : 'Watch reel ('.$video['host'].')' }}</a>
    <small>Reel shared by link</small>
  </div>
@endif
