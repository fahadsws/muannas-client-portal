<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Help guide · {{ config('board.brand') }}</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600;650;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/board.css?v={{ filemtime(public_path('css/board.css')) }}">
<style>:root{--accent:{{ auth()->user()->agency->brand_color ?? '#7c3aed' }}}</style>
</head>
<body>
@php
  $toc = ['overview' => 'How it fits together', 'quickstart' => 'Quick start', 'workspaces' => 'Workspaces', 'strategy' => 'Writing the Strategy', 'content' => 'Planning & tracking', 'sharing' => 'Sharing & permissions', 'documents' => 'Documents & templates', 'team' => 'Team & settings', 'faq' => 'Questions & answers'];
@endphp
<header class="bar" style="position:sticky;top:0">
  <a class="btn" href="/board" style="text-decoration:none">← Back to workspace</a>
  <div class="crumb" style="margin-left:6px"><b>Help guide</b></div>
  <div class="spacer"></div>
  <a class="btn primary" href="/help/pdf" data-noload="1" style="text-decoration:none">Download PDF</a>
</header>
<div class="guidewrap">
  <nav class="guidetoc">
    <div class="slabel" style="padding-left:0">On this page</div>
    @foreach($toc as $id => $label)<a href="#{{ $id }}">{{ $label }}</a>@endforeach
  </nav>
  <main class="guide">
    <h1>{{ config('board.brand') }} — agency guide</h1>
    <p class="lead">Everything your team needs to plan, approve, share and report on client content. Start with the quick start, then use the sections below as a reference.</p>
    @include('help.guide')
  </main>
</div>
<script>
  // highlight the section currently in view
  var links = document.querySelectorAll('.guidetoc a'), heads = [].map.call(links, function (a) { return document.querySelector(a.getAttribute('href')); });
  function spy() { var y = window.scrollY + 120, cur = 0; heads.forEach(function (h, i) { if (h && h.offsetTop <= y) cur = i; }); links.forEach(function (a, i) { a.classList.toggle('on', i === cur); }); }
  window.addEventListener('scroll', spy); spy();
</script>
</body>
</html>
