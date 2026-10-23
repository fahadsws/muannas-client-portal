<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $agency['name'] }} · {{ config('board.brand') }}</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600;650;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<link rel="stylesheet" href="/css/board.css?v={{ filemtime(public_path('css/board.css')) }}">
<style>:root{--accent:{{ $agency['color'] }}}</style>
</head>
<body class="{{ $mode === 'client' ? 'solo' : '' }} {{ !empty($me['impersonating']) ? 'imp' : '' }}">
<div id="nprog"></div>
<div id="splash">
  @include('partials.logo')
  <div class="bar"><i></i></div>
  <small>Loading your workspace…</small>
</div>

@if(!empty($me['impersonating']))
<div class="impbar">
  <span><b>Admin view</b> — you are signed in as the owner of <b>{{ $agency['name'] }}</b>. Changes you make are real.</span>
  <form method="post" action="/impersonate/stop" style="margin:0">@csrf<button>Exit &amp; back to admin</button></form>
</div>
@endif

<div class="app">
  <aside class="side" id="side"></aside>
  <div class="main">
    <header class="bar" id="bar"></header>
    <div class="content" id="content"></div>
  </div>
</div>

<div class="scrim" id="scrim"><div class="modal" id="modal"></div></div>
<div class="scrim cmdk" id="cmdk"><div class="cmd" id="cmd"></div></div>
<aside class="drawer" id="drawer"></aside>
<div class="toast" id="toast"></div>

<script>
window.BOARD = {
  mode: @json($mode), token: @json($token), accounts: @json($accounts), me: @json($me), agency: @json($agency),
  brand: @json(config('board.brand')), csrf: @json(csrf_token())
};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script src="/js/ui.js?v={{ filemtime(public_path('js/ui.js')) }}"></script>
<script src="/js/board.js?v={{ filemtime(public_path('js/board.js')) }}"></script>
</body>
</html>
