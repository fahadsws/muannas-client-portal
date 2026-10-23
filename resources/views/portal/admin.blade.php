@extends('portal.layout')
@section('title', 'Control Room')
@section('crumb', 'Control Room')
@section('content')
@php $me = auth()->user(); $ic = fn ($n) => \App\Support\Ui::icon($n); @endphp
<div class="pagehead"><div><h1>Control Room</h1><p>Clients and team, bulk upload, and everything waiting for approval.</p></div></div>

<div class="grid g2 gap" style="margin-bottom:16px">
  <div class="pn">
    <h3>Clients</h3>
    <p>{{ $accounts->count() }} {{ \Illuminate\Support\Str::plural('client', $accounts->count()) }}. Add or remove clients, their logins and logo, and choose which team members look after them.</p>
    <div class="actions">
      @if($me->hasPerm('workspaces.create'))<a class="btn primary" href="/portal/clients/create">{!! $ic('plus') !!}Add client</a>@endif
      <a class="btn" href="/portal/clients">{!! $ic('users') !!}Manage clients</a>
    </div>
  </div>
  <div class="pn">
    <h3>Team members</h3>
    <p>{{ $teamCount }} on the team. Add or remove team members and set what each role can do.</p>
    <div class="actions">
      @if($me->hasPerm('team.manage'))<a class="btn primary" href="/team/create">{!! $ic('plus') !!}Add team member</a>@endif
      <a class="btn" href="/team">{!! $ic('user') !!}Manage team</a>
      @if($me->hasPerm('roles.manage'))<a class="btn" href="/roles">{!! $ic('lock') !!}Roles &amp; permissions</a>@endif
    </div>
  </div>
</div>

<div class="pn" style="margin-bottom:16px">
  <h3>Bulk upload posts</h3>
  <p>Upload many images at once, with an optional CSV for captions, platforms and dates. They land as drafts for the client you pick.</p>
  @if($editable->isEmpty())
    <p class="mute" style="margin:0">You have no client you can upload to yet.</p>
  @else
    <form class="inl" onsubmit="location.href='/portal/c/'+this.client.value+'/content/bulk';return false">
      <select name="client" aria-label="Client">@foreach($editable as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
      <button class="btn primary">{!! $ic('upload') !!}Continue to bulk upload</button>
    </form>
  @endif
</div>

<div class="pn" id="approvals">
  <h3>Pending approvals, all clients</h3>
  <p>Everything waiting on a client or sent back with changes. Oldest first.</p>
  @forelse($groups as $accountId => $posts)
    @php $a = $accounts[$accountId]; @endphp
    <div class="prow between" style="margin:14px 0 6px"><b><a href="/portal/c/{{ $a->id }}" style="text-decoration:none">{{ $a->name }}</a></b><a class="plink" href="/portal/c/{{ $a->id }}/content?status=pending">Open Content Review {!! $ic('chev-r') !!}</a></div>
    <div class="tblwrap"><table class="tbl stack-m">
      <thead><tr><th>Post</th><th>Status</th><th>Round</th><th>Planned</th><th>Waiting</th></tr></thead>
      <tbody>
      @foreach($posts as $p)
        <tr>
          <td data-l="Post"><a href="/portal/posts/{{ $p->id }}"><b>{{ $p->title }}</b></a><br><small>{{ $p->platformLabel() }}</small></td>
          <td data-l="Status"><span class="stag s-{{ $p->status }}">{{ $p->statusLabel() }}</span></td>
          <td data-l="Round">{{ $p->round }}</td>
          <td data-l="Planned">{{ $p->scheduled_on?->format('j M') ?? '—' }}</td>
          <td data-l="Since">{{ $p->updated_at->diffForHumans() }}</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  @empty
    <div class="empty" style="margin-top:12px"><div class="eic">{!! $ic('check') !!}</div><h3>All clear</h3><p>No client is waiting on anything.</p></div>
  @endforelse
</div>
@endsection
