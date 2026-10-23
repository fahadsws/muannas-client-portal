@php
  use App\Models\Note;
  use App\Models\Post;
  use App\Support\Ui;
  $account = $account ?? null; $path = request()->path();
  $pendingAll = Post::whereIn('account_id', $workspaces->pluck('id'))->where('status', 'pending')->count();
  $notesN = $account ? Note::where('account_id', $account->id)->count() : 0;
  // one menu for every page, same order and same counts; "Team notes" only when a client is open
  $items = array_values(array_filter([
    ['/board'.($account ? '?ws='.$account->id : ''), 'Campaign board', 'grid', false, null, true],
    ['/board?ws='.($account?->id).'&notes=1', 'Team notes', 'bell', false, $notesN ?: null, (bool) $account],
    ['/board?page=docs', 'Documents', 'doc', false, null, $u->hasPerm('documents.export')],
    ['/portal/clients', 'Clients', 'building', str_starts_with($path, 'portal/clients'), null, true],
    ['/team', 'Team', 'users', str_starts_with($path, 'team'), null, true],
    ['/roles', 'Roles & permissions', 'lock', str_starts_with($path, 'roles'), null, $u->hasPerm('roles.manage')],
    ['/portal/invoice-settings', 'Invoice settings', 'receipt', str_starts_with($path, 'portal/invoice-settings'), null, $u->hasPerm('billing.manage')],
    ['/portal/admin', 'Control Room', 'inbox', $path === 'portal/admin', $pendingAll ?: null, true],
    ['/board?page=settings', 'Settings', 'cog', false, null, $u->hasPerm('settings.manage') || $u->hasPerm('templates.manage')],
    ['/help', 'Help guide', 'doc', $path === 'help', null, true],
  ], fn ($n) => $n[5]));
@endphp
<div class="side-manage">
  <div class="slabel">Manage</div>
  <div class="nav">
    @foreach($items as $n)
      <a class="nbtn {{ $n[3] ? 'on' : '' }}" href="{{ $n[0] }}" style="text-decoration:none">{!! Ui::icon($n[2]) !!}{{ $n[1] }}@if($n[4])<span class="badge">{{ $n[4] }}</span>@endif</a>
    @endforeach
  </div>
</div>
