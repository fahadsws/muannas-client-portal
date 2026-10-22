<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Agency guide</title>
<style>
  @page { margin: 40px 44px; }
  body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 10.5px; color: #1a1a1a; line-height: 1.55; }
  h1 { font-size: 24px; margin: 0 0 4px; letter-spacing: -.4px; }
  .lead { color: #555; font-size: 12px; margin: 0 0 6px; }
  h2 { font-size: 15px; margin: 26px 0 8px; padding-bottom: 5px; border-bottom: 2px solid #0a0a0a; page-break-after: avoid; }
  h3 { font-size: 12px; margin: 16px 0 6px; page-break-after: avoid; }
  p { margin: 6px 0; } ul, ol { margin: 6px 0 6px 18px; padding: 0; } li { margin: 3px 0; }
  table { width: 100%; border-collapse: collapse; margin: 8px 0; }
  th { text-align: left; background: #f1f1f1; font-size: 9px; text-transform: uppercase; letter-spacing: .6px; padding: 6px 8px; border: 1px solid #d6d6d6; }
  td { padding: 6px 8px; border: 1px solid #e0e0e0; vertical-align: top; }
  pre { background: #f6f6f6; border: 1px solid #e0e0e0; border-left: 3px solid #0a0a0a; padding: 8px 10px; margin: 5px 0 9px; font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; white-space: pre-wrap; }
  code { background: #efefef; padding: 1px 4px; font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; }
  .muted { color: #777; }
  .tip { border: 1px solid #0a0a0a; padding: 8px 11px; margin: 10px 0; background: #fafafa; }
  .foot { margin-top: 28px; padding-top: 8px; border-top: 1px solid #ddd; color: #888; font-size: 8.5px; }
</style>
</head>
<body>
  <h1>{{ config('board.brand') }} — agency guide</h1>
  <p class="lead">Everything your team needs to plan, approve, share and report on client content.</p>
  @include('help.guide')
  <div class="foot">Generated {{ now()->format('d M Y') }} · {{ config('board.brand') }}</div>
</body>
</html>
