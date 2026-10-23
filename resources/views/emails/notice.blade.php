<!doctype html>
<html>
<body style="margin:0;padding:0;background:#f7f2e8;font-family:Arial,Helvetica,sans-serif;color:#14203a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f2e8;padding:28px 12px">
  <tr><td align="center">
    <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%">
      <tr><td style="background:#0f1e3d;padding:22px 28px;border-radius:10px 10px 0 0">
        <span style="font-family:Georgia,serif;font-size:20px;letter-spacing:3px;color:#f7f2e8;font-weight:bold">MUANNAS</span>
        <span style="font-size:10px;letter-spacing:5px;color:#b08d57;margin-left:8px">ATELIER</span>
      </td></tr>
      <tr><td style="background:#fffdf8;padding:32px 28px;border:1px solid #e3dac6;border-top:0">
        <h1 style="font-family:Georgia,serif;font-size:24px;margin:0 0 16px;color:#0f1e3d">{{ $heading }}</h1>
        @foreach($lines as $l)
          <p style="font-size:15px;line-height:1.65;margin:0 0 12px;color:#2b3652">{{ $l }}</p>
        @endforeach
        @if($button && $url)
          <p style="margin:26px 0 8px"><a href="{{ $url }}" style="background:#0f1e3d;color:#f7f2e8;text-decoration:none;padding:13px 26px;border-radius:8px;font-size:15px;font-weight:bold;display:inline-block">{{ $button }}</a></p>
          <p style="font-size:12px;color:#6f7690;margin:14px 0 0;word-break:break-all">Button not working? Copy this link: {{ $url }}</p>
        @endif
      </td></tr>
      <tr><td style="padding:16px 28px;font-size:12px;color:#6f7690;text-align:center">{{ config('portal.legal') }}</td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
