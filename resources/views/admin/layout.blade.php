@php
  use App\Support\Ui;
  $nav = [['/admin', 'Dashboard', 'grid', request()->is('admin')], ['/admin/agencies', 'Agencies', 'building', request()->is('admin/agencies*')], ['/admin/reports', 'Reports & export', 'doc', request()->is('admin/reports')], ['/admin/settings', 'Settings', 'cog', request()->is('admin/settings')]];
  $agencyCount = \App\Models\Agency::count();
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Admin') · {{ config('board.brand') }}</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600;650;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<link rel="stylesheet" href="/css/board.css?v={{ filemtime(public_path('css/board.css')) }}">
</head>
<body>
<div id="nprog"></div>
<div class="app">
  <aside class="side admin" id="side">
    <div><a class="logo" href="/admin">@include('partials.logo-mark')<span class="wordmark">{{ config('board.brand') }}</span></a></div>
    <div class="slabel">Menu</div>
    <div class="nav">
      @foreach($nav as $n)
        <a class="nbtn {{ $n[3] ? 'on' : '' }}" href="{{ $n[0] }}">{!! Ui::icon($n[2]) !!}{{ $n[1] }}@if($n[0] === '/admin/agencies' && $agencyCount)<span class="badge">{{ $agencyCount }}</span>@endif</a>
      @endforeach
    </div>
    <div class="sfoot">
      <span class="av">{{ Ui::initials(auth()->user()->name) }}</span>
      <div class="who"><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></div>
      <form method="post" action="/logout" style="margin:0">@csrf<button class="ibtn" title="Log out">{!! Ui::icon('out') !!}</button></form>
    </div>
  </aside>

  <div class="main">
    <header class="bar">
      <button class="ibtn burger" type="button" onclick="document.getElementById('side').classList.toggle('open')">{!! Ui::icon('menu') !!}</button>
      <div class="crumb"><span>Platform</span><span class="sep">/</span>@yield('crumb')</div>
      <div class="spacer"></div>
      <a class="btn primary" href="/admin/agencies/create">{!! Ui::icon('plus') !!}<span class="hide-s">New agency</span></a>
    </header>
    <div class="content">
      @if($errors->any())<div class="notice err">{{ $errors->first() }}</div>@endif
      @if(session('creds'))
        @php $c = session('creds'); @endphp
        <div class="notice cred">
          <div><b>{{ $c['title'] }}</b> — share these login details with the agency owner. They sign in at <b>{{ url('/login') }}</b>.</div>
          <div class="credbox"><span>Agency <b>{{ $c['agency'] }}</b></span><span>Email <code>{{ $c['email'] }}</code></span><span>Password <code>{{ $c['password'] }}</code></span></div>
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
