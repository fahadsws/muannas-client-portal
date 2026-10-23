@php
  use App\Support\Ui;
  $u = auth()->user(); $agency = $u->agency;
  $perm = fn ($p) => $u->hasPerm($p);
  $workspaces = \App\Support\Access::accountsFor($u);
  $path = request()->path();
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') · {{ $agency->name }}</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600;650;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<link rel="stylesheet" href="/css/board.css?v={{ filemtime(public_path('css/board.css')) }}">
<style>:root{--accent:{{ $agency->brand_color }}}</style>
</head>
<body class="{{ session('impersonator') ? 'imp' : '' }}">
<div id="nprog"></div>
@if(session('impersonator'))
<div class="impbar"><span><b>Admin view</b> — you are signed in as the owner of <b>{{ $agency->name }}</b>. Changes you make are real.</span>
  <form method="post" action="/impersonate/stop" style="margin:0">@csrf<button>Exit &amp; back to admin</button></form></div>
@endif
<div class="app">
  <aside class="side" id="side">
    <div class="agbox">{!! Ui::mark($agency->name, $agency->logoUrl()) !!}<div><b>{{ $agency->name }}</b><small>Agency</small></div></div>
    @include('partials.side-clients', ['account' => null, 'accNav' => []])
    @include('partials.side-manage', ['account' => null])
    <div class="sfoot">
      {!! Ui::avatar($u->name) !!}<div class="who"><b>{{ $u->name }}</b><small>{{ $u->role === 'owner' ? 'Owner' : ($u->agencyRole?->name ?? 'Team member') }}</small></div>
      <form method="post" action="/logout" style="margin:0">@csrf<button class="ibtn" title="Log out">{!! Ui::icon('out') !!}</button></form>
    </div>
    @if(session('impersonator'))<form method="post" action="/impersonate/stop" style="margin:12px 0 0">@csrf<button class="btn" style="width:100%;justify-content:center">Exit to admin console</button></form>@endif
  </aside>

  <div class="main">
    <header class="bar">
      <button class="ibtn burger" type="button" onclick="document.getElementById('side').classList.toggle('open')">{!! Ui::icon('menu') !!}</button>
      <div class="crumb"><span>{{ $agency->name }}</span><span class="sep">/</span>@yield('crumb')</div>
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
@stack('scripts')
</body>
</html>
