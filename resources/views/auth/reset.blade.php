<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>New password · {{ config('board.brand') }}</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;550;600;650;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
:root{--btn-radius:0px;--accent:#4f46e5;--ink:#14141c;--mute:#7f7f90;--line:#e4e4ee}
html,body{margin:0;min-height:100%}
body{font-family:'Inter',system-ui,sans-serif;color:var(--ink);-webkit-font-smoothing:antialiased;min-height:100vh;background:#fff}
#bar.on{opacity:1}
.shell{width:100%;min-height:100vh;display:grid;grid-template-columns:1.1fr 1fr;background:#fff}

.formside{display:flex;align-items:center;justify-content:center;padding:40px 56px;background:#fff}
.brand{display:flex;align-items:center;gap:9px;font-weight:650;font-size:15px;letter-spacing:-.3px}
.brand svg{width:26px;height:26px}
.box{width:min(380px,100%)}
.box h1{margin:0 0 6px;font-size:30px;font-weight:650;letter-spacing:-1px;text-align:center}
.box .sub{margin:0 0 30px;text-align:center;color:var(--mute);font-size:14px}
.f{margin-bottom:16px}
.f label{display:block;font-size:12.5px;font-weight:600;margin-bottom:7px;color:#3b3b4a}
.inp{position:relative}
.inp input{width:100%;height:48px;border:1.5px solid transparent;background:#f2f2f7;border-radius:12px;padding:0 44px 0 16px;font:inherit;font-size:14.5px;color:var(--ink);outline:none;transition:.15s}
.inp input::placeholder{color:#a8a8b8}
.inp input:hover{background:#ececf4}
.inp input:focus{background:#fff;border-color:var(--accent);box-shadow:0 0 0 4px rgba(124,58,237,.12)}
.inp .eye{position:absolute;right:6px;top:6px;width:36px;height:36px;border:0;background:transparent;border-radius:var(--btn-radius);color:#9a9aab;cursor:pointer;display:grid;place-items:center}
.inp .eye:hover{color:var(--ink);background:#e6e6ef}
.inp .eye svg,.inp .ic svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.opts{display:flex;align-items:center;justify-content:space-between;margin:2px 0 22px}
.chk{display:flex;align-items:center;gap:9px;font-size:13px;color:#4b4b5c;cursor:pointer}
.chk input{appearance:none;width:18px;height:18px;border:1.5px solid #cfcfdc;border-radius:5px;background:#fff;display:grid;place-items:center;cursor:pointer;margin:0;transition:.15s}
.chk input:checked{background:var(--ink);border-color:var(--ink)}
.chk input:checked:after{content:'';width:9px;height:5px;border-left:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(-45deg) translate(1px,-1px)}
.go{width:100%;height:50px;border:0;border-radius:var(--btn-radius);background:var(--ink);color:#fff;font:inherit;font-size:14.5px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:.15s}
.go:hover{background:#2a2a38;transform:translateY(-1px)}
.go:active{transform:none}
.go.loading{pointer-events:none;opacity:.9}
.spin{width:16px;height:16px;border-radius:50%;border:2px solid rgba(255,255,255,.35);border-top-color:#fff;animation:r .7s linear infinite}
@keyframes r{to{transform:rotate(360deg)}}
.foot{margin-top:22px;text-align:center;font-size:13px;color:var(--mute)}.foot b{color:var(--ink);font-weight:600}
.err{background:#fdecec;color:#c8342f;padding:11px 14px;border-radius:11px;font-size:13px;margin-bottom:18px;font-weight:500;display:flex;gap:9px;align-items:center}

.art{position:relative;overflow:hidden;min-height:100vh;color:#fff;padding:44px 64px 52px;display:flex;flex-direction:column;
  background:radial-gradient(820px 520px at 8% -6%,#4f46e5 0%,transparent 62%),radial-gradient(760px 560px at 105% 108%,#f59e0b 0%,transparent 58%),#0b0a2e}
.art:before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.16) 1px,transparent 1px);background-size:26px 26px;-webkit-mask-image:radial-gradient(ellipse at 35% 35%,#000 0%,transparent 72%);mask-image:radial-gradient(ellipse at 35% 35%,#000 0%,transparent 72%);pointer-events:none}
.art>*{position:relative;z-index:1}
.art .brand{color:#fff}
.art h2{font-size:52px;line-height:1.05;letter-spacing:-2px;font-weight:650;margin:0 0 16px;max-width:500px}
.art h2 em{font-style:normal;background:linear-gradient(90deg,#fde7c2,#c7ccff);-webkit-background-clip:text;background-clip:text;color:transparent}
.art p{max-width:470px;color:rgba(255,255,255,.74);font-size:16px;line-height:1.65;margin:0 0 26px}
.feat{display:flex;gap:10px;flex-wrap:wrap}
.feat span{display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:550;color:rgba(255,255,255,.92);background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);padding:7px 13px;border-radius:30px;backdrop-filter:blur(8px)}
.feat span:before{content:'';width:6px;height:6px;border-radius:50%;background:#ffd29a}
.stage{position:relative;margin:5vh 0 auto;height:330px;max-width:560px;perspective:1300px}
.win{position:absolute;left:0;top:0;width:min(470px,100%);background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.2);border-radius:16px;backdrop-filter:blur(16px);transform:rotateY(-9deg) rotateX(4deg) rotateZ(-1.5deg);transform-origin:left center;box-shadow:0 40px 80px -30px rgba(0,0,0,.6);animation:bob 7s ease-in-out infinite}
@keyframes bob{50%{transform:rotateY(-9deg) rotateX(4deg) rotateZ(-1.5deg) translateY(-8px)}}
.wbar{display:flex;align-items:center;gap:6px;padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.12)}
.wbar i{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.28)}
.wbar span{margin-left:10px;font-size:10.5px;color:rgba(255,255,255,.5);letter-spacing:.03em}
.wbody{padding:14px}
.wk{display:grid;grid-template-columns:1.3fr 1fr 1fr 1fr;gap:8px}
.wk div{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:9px 10px}
.wk div.p{background:#fff;color:#0b0a2e}
.wk small{display:block;font-size:8.5px;letter-spacing:.1em;opacity:.65;font-weight:600}
.wk b{font-size:20px;letter-spacing:-.8px;font-weight:650}
.wprog{height:4px;border-radius:4px;background:rgba(255,255,255,.14);margin:12px 0;overflow:hidden}
.wprog i{display:block;height:100%;width:68%;border-radius:4px;background:linear-gradient(90deg,#c7ccff,#ffb36b);animation:fill 3.6s ease-in-out infinite}
@keyframes fill{0%{width:8%}60%,100%{width:68%}}
.wtabs{display:flex;gap:16px;font-size:10px;color:rgba(255,255,255,.5);margin-bottom:10px;padding-bottom:7px;border-bottom:1px solid rgba(255,255,255,.1)}
.wtabs b{color:#fff;font-weight:600;border-bottom:1.5px solid #fff;padding-bottom:7px;margin-bottom:-8px}
.wrow{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.wc{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:9px}
.wc u{display:block;height:5px;border-radius:5px;background:rgba(255,255,255,.3);margin-top:7px;text-decoration:none}
.wc u.s{width:62%;background:rgba(255,255,255,.16)}
.chip{display:inline-block;font-size:8.5px;font-weight:700;letter-spacing:.06em;padding:2px 8px;border-radius:20px;background:rgba(255,255,255,.18)}
.chip.live{background:#18c27a;color:#04281a}.chip.ready{background:#9aa0ff;color:#14134a}
.chip.live:before{content:'';display:inline-block;width:5px;height:5px;border-radius:50%;background:#04281a;margin-right:5px;animation:bl 1.5s infinite}
@keyframes bl{50%{opacity:.25}}
.nt{position:absolute;left:250px;top:238px;display:flex;align-items:center;gap:10px;background:#fff;color:#0b0a2e;font-size:12px;font-weight:600;padding:10px 15px 10px 11px;border-radius:12px;box-shadow:0 20px 40px -14px rgba(0,0,0,.55);animation:pop 6s ease-in-out infinite}
.nt i{width:20px;height:20px;border-radius:50%;background:#18c27a;color:#fff;display:grid;place-items:center;font-style:normal;font-size:11px}
@keyframes pop{0%,12%{opacity:0;transform:translateY(14px)}22%,80%{opacity:1;transform:none}92%,100%{opacity:0;transform:translateY(-8px)}}
@media (max-width:860px){.shell{grid-template-columns:1fr}.art{min-height:0}.formside{padding:36px 22px}.art{min-height:0;padding:28px}.art h2{font-size:30px;margin-top:10px}.stage,.art p,.feat{display:none}}
</style>
</head>
<body>
<div id="bar"></div>
<div class="shell">
  <aside class="art">
    <div class="brand">
      <img src="/brand/logo" alt="" style="width:30px;height:30px;border-radius:8px">
      {{ config('board.brand') }}
    </div>
    <div class="stage" aria-hidden="true">
      <div class="win">
        <div class="wbar"><i></i><i></i><i></i><span>atelo.app / campaign board</span></div>
        <div class="wbody">
          <div class="wk"><div class="p"><small>COMPLETE</small><b>68%</b></div><div><small>TO DO</small><b>11</b></div><div><small>READY</small><b>6</b></div><div><small>LIVE</small><b>20</b></div></div>
          <div class="wprog"><i></i></div>
          <div class="wtabs"><b>May</b><span>June</span><span>Reels</span><span>Podcasts</span></div>
          <div class="wrow">
            <div class="wc"><span class="chip live">LIVE</span><u></u><u class="s"></u></div>
            <div class="wc"><span class="chip ready">READY</span><u></u><u class="s"></u></div>
            <div class="wc"><span class="chip">TO DO</span><u></u><u class="s"></u></div>
          </div>
        </div>
      </div>
      <div class="nt"><i>✓</i>Client approved Post 03</div>
    </div>
    <h2>Every campaign.<br><em>One calm board.</em></h2>
    <p>Plan, approve and track your content in one shared workspace — so your clients always know where things stand, without chasing a chat thread.</p>
    <div class="feat"><span>Live progress</span><span>Client approvals</span><span>Notes in context</span></div>
  </aside>

  <section class="formside">
    <form class="box" method="post" action="/reset-password">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <h1>New password</h1>
      <p class="sub">Choose a password of at least 8 characters</p>
      @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
      <div class="f"><label for="u">Email</label><div class="inp"><input type="email" name="email" id="u" value="{{ old('email', $email) }}" required></div></div>
      <div class="f"><label for="p">New password</label><div class="inp"><input type="password" name="password" id="p" minlength="8" autofocus required></div></div>
      <div class="f"><label for="p2">Confirm password</label><div class="inp"><input type="password" name="password_confirmation" id="p2" minlength="8" required></div></div>
      <button class="go"><span>Change password</span></button>
    </form>
  </section>
</div>

</body>
</html>
