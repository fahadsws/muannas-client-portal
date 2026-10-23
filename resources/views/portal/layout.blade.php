@php
  use App\Models\Post;
  use App\Support\Access;
  use App\Support\Ui;
  $u = auth()->user();
  $staff = $u->isStaff();
  $workspaces = Access::accountsFor($u);
  $account = $account ?? null;
  $agency = $account?->agency ?? $u->agency ?? $workspaces->first()?->agency;
  $path = request()->path();
  $pendingHere = $account ? Post::where('account_id', $account->id)->where('status', 'pending')->count() : 0;
  $pendingAll = $staff ? Post::whereIn('account_id', $workspaces->pluck('id'))->where('status', 'pending')->count() : 0;
  $base = $account ? '/portal/c/'.$account->id : null;
  $sub = (preg_match('#^portal/c/\d+/(calendar|projects|files|invoices|reports|content)#', $path, $m) ? $m[1] : (str_starts_with($path, 'portal/posts') || str_starts_with($path, 'r/') ? 'content' : ''));
  $accNav = $account ? [
    [$base, 'Client Dashboard', 'home', $path === 'portal/c/'.$account->id, null],
    [$base.'/content', 'Content Review', 'photo', str_contains($path, '/content') || str_starts_with($path, 'portal/posts') || str_starts_with($path, 'r/'), $pendingHere ?: null],
    [$base.'/calendar', 'Content Calendar', 'cal', str_ends_with($path, '/calendar'), null],
    [$base.'/projects', 'Projects & Tasks', 'tasks', str_ends_with($path, '/projects'), null],
    [$base.'/files', 'Files & Assets', 'folder', str_ends_with($path, '/files'), null],
    [$base.'/invoices', 'Payments & Invoices', 'receipt', str_ends_with($path, '/invoices'), null],
    [$base.'/reports', 'Performance Reports', 'chart', str_ends_with($path, '/reports'), null],
  ] : [];
  if ($account && $staff) { $accNav[0][0] = '/board?ws='.$account->id; $accNav[0][3] = false; }
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Portal') · {{ ($staff ? null : $account?->name) ?? $agency?->name ?? config('board.brand') }}</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600;650;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<link rel="stylesheet" href="/css/board.css?v={{ filemtime(public_path('css/board.css')) }}">
<link rel="stylesheet" href="/css/portal.css?v={{ filemtime(public_path('css/portal.css')) }}">
<style>:root{--accent:{{ $agency?->brand_color ?? '#7c3aed' }}}</style>
</head>
<body>
<div id="nprog"></div>
@if(session('impersonator'))
<div class="impbar"><span><b>Admin view</b> — you are signed in as the owner of <b>{{ $agency?->name }}</b>. Changes you make are real.</span>
  <form method="post" action="/impersonate/stop" style="margin:0">@csrf<button>Exit &amp; back to admin</button></form></div>
@endif
<div class="app">
  <aside class="side" id="side">
    @if($staff)
      {{-- Same sidebar as the board: agency, its clients (the open client shows its modules), then Manage --}}
      <div class="agbox">{!! Ui::mark($agency?->name, $agency?->logoUrl()) !!}<div><b>{{ $agency?->name }}</b><small>Agency</small></div></div>

      @include('partials.side-clients', ['account' => $account, 'accNav' => $accNav])
      @include('partials.side-manage', ['account' => $account])
    @else
      {{-- Client login: only their own name, logo and the modules --}}
      <div class="agbox">
        <span class="plogo sm">@if($account?->logo)<img src="{{ $account->logoUrl() }}" alt="">@else{{ Ui::initials($account?->name ?? $u->name) }}@endif</span>
        <div><b>{{ $account?->name ?? $u->name }}</b><small>Client portal</small></div>
      </div>
      <div class="nav" style="margin-top:14px">
        @foreach($accNav as $n)
          <a class="nbtn {{ $n[3] ? 'on' : '' }}" href="{{ $n[0] }}" style="text-decoration:none">{!! Ui::icon($n[2]) !!}{{ $n[1] }}@if($n[4])<span class="badge">{{ $n[4] }}</span>@endif</a>
        @endforeach
      </div>
    @endif

    <div class="sfoot">
      {!! Ui::avatar($u->name) !!}<div class="who"><b>{{ $u->name }}</b><small>{{ $u->roleLabel() }}</small></div>
      <form method="post" action="/logout" style="margin:0">@csrf<button class="ibtn" title="Log out">{!! Ui::icon('out') !!}</button></form>
    </div>
  </aside>

  <div class="main">
    <header class="bar">
      <button class="ibtn burger" type="button" onclick="document.getElementById('side').classList.toggle('open')">{!! Ui::icon('menu') !!}</button>
      <div class="crumb"><span>{{ $staff ? ($account?->name ?? $agency?->name) : ($account?->name ?? 'Client portal') }}</span><span class="sep">/</span><b>@yield('crumb', ['content' => 'Content Review', 'calendar' => 'Content Calendar', 'projects' => 'Projects & Tasks', 'files' => 'Files & Assets', 'invoices' => 'Payments & Invoices', 'reports' => 'Performance Reports'][$sub] ?? ($account && $path === 'portal/c/'.$account->id ? 'Client Dashboard' : 'Client portal'))</b></div>
      <div class="spacer"></div>
      @yield('actions')
    </header>
    <div class="content">
      @if($errors->any())<div class="notice err">{{ $errors->first() }}</div>@endif
      @if(session('err'))<div class="notice err">{{ session('err') }}</div>@endif
      @if(session('creds'))
        @php $c = session('creds'); @endphp
        <div class="notice cred">
          <div><b>{{ $c['title'] }}</b> — share these login details securely. They sign in at <b>{{ url('/login') }}</b>.</div>
          <div class="credbox"><span>Email <code>{{ $c['email'] }}</code></span><span>Password <code>{{ $c['password'] }}</code></span></div>
          <button type="button" class="btn sm" data-copy="{{ url('/login') }}&#10;Email: {{ $c['email'] }}&#10;Password: {{ $c['password'] }}">Copy details</button>
        </div>
      @endif
      @if(session('batch'))
        <div class="notice cred">
          <div><b>Review link for WhatsApp</b> — the client was also emailed. The link still asks them to sign in.</div>
          <div class="copyrow"><input type="text" value="{{ session('batch') }}" readonly onclick="this.select()"><button type="button" class="btn" data-copy="{{ session('batch') }}">Copy</button></div>
          <button type="button" class="btn sm primary" data-wa="Hi, your new content is ready for review: {{ session('batch') }}">Share on WhatsApp</button>
        </div>
      @endif
      @yield('content')
    </div>
  </div>
</div>
@if(session('ok'))<div class="toast on" id="flash"><i>✓</i>{{ session('ok') }}</div>@endif
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.14.5/sweetalert2.all.min.js"></script>
<script src="/js/ui.js?v={{ filemtime(public_path('js/ui.js')) }}"></script>
<script src="/js/admin.js?v={{ filemtime(public_path('js/admin.js')) }}"></script>
<script src="/js/portal.js?v={{ filemtime(public_path('js/portal.js')) }}"></script>
@stack('scripts')
</body>
</html>
