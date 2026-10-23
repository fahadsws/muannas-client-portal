@php
  use App\Support\Ui;
  $account = $account ?? null; $accNav = $accNav ?? [];
@endphp
{{-- Client list for the sidebar: fixed height, searchable, loads 10 at a time as you scroll. Behaviour: /js/ui.js ([data-cl]) --}}
<div class="cl" data-cl data-per="10">
  <div class="cl-head">
    <a class="slabel" href="/portal/clients" style="text-decoration:none">Clients</a>
    <div class="cl-tools">
      <button type="button" class="ibtn" data-cl-search title="Search clients" aria-label="Search clients">{!! Ui::icon('search') !!}</button>
      @if($u->hasPerm('workspaces.create'))<a class="ibtn" href="/portal/clients/create" title="Add client">{!! Ui::icon('plus') !!}</a>@endif
    </div>
  </div>
  <div class="cl-sbar" hidden><input type="text" placeholder="Search clients" autocomplete="off" aria-label="Search clients"><button type="button" class="ibtn" data-cl-x title="Clear" aria-label="Clear search">{!! Ui::icon('x') !!}</button></div>
  <div class="cl-scroll nav">
    @forelse($workspaces as $w)
      @php $active = $account && $w->id === $account->id; @endphp
      <div class="cl-item" data-name="{{ \Illuminate\Support\Str::lower($w->name) }}" @if($active) data-active="1" @endif>
        <a class="ws {{ $active ? 'on' : '' }}" href="/board?ws={{ $w->id }}" style="text-decoration:none">{!! Ui::mark($w->name, $w->logoUrl()) !!}<span><b>{{ $w->name }}</b><small class="ago">{{ $w->activity_at ? 'Updated '.$w->activity_at->shortRelativeDiffForHumans() : 'Client' }}</small></span></a>
        @if($active && $accNav)
          <div class="subnav">
            @foreach($accNav as $n)
              <a class="nbtn {{ $n[3] ? 'on' : '' }}" href="{{ $n[0] }}" style="text-decoration:none">{!! Ui::icon($n[2]) !!}{{ $n[1] }}@if($n[4])<span class="badge">{{ $n[4] }}</span>@endif</a>
            @endforeach
          </div>
        @endif
      </div>
    @empty
      <div class="cl-empty">No clients yet</div>
    @endforelse
    <div class="cl-empty cl-none" hidden>No client matches</div>
  </div>
</div>
