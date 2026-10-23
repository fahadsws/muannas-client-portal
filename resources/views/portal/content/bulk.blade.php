@extends('portal.layout')
@section('title', 'Bulk upload')
@section('content')
<a class="back" href="/portal/c/{{ $account->id }}/content">{!! \App\Support\Ui::icon('arrow') !!}Back to content</a>
<div class="pagehead"><div><h1>Bulk upload</h1><p>Create many draft posts at once for {{ $account->name }}.</p></div></div>
<div class="grid g-main">
  <form class="pn" method="post" enctype="multipart/form-data" action="/portal/c/{{ $account->id }}/content/bulk">@csrf
    <div class="fld"><label>1. Images</label>
      <x-file-input name="images" multiple label="Attach images" accept="image/jpeg,image/png,image/webp,image/gif" />
      <div class="hint">Up to 60 images, {{ config('portal.max_image_kb') / 1024 }} MB each. Every image without a CSV row becomes its own draft post, titled from its file name.</div></div>
    <div class="fld"><label>2. CSV with captions and dates (optional)</label>
      <x-file-input name="csv" label="Attach CSV" accept=".csv,text/csv,text/plain" />
      <div class="hint">Columns: <code>title, caption, platform, date, images, video_url</code>. In <code>images</code>, list file names separated by <code>|</code> to make a carousel. Nothing is created if any row has a problem.</div></div>
    <div class="bchk" id="bchk" hidden></div>
    <button class="btn primary" id="bsubmit">Create drafts</button>
  </form>
  <div class="pn">
    <h3>How it works</h3>
    <ol style="padding-left:18px;margin:0" class="stack">
      <li>Download the template and fill one row per post.</li>
      <li>Select the images that the CSV mentions (plus any extras).</li>
      <li>Upload. Everything lands as drafts, hidden from the client.</li>
      <li>Check the grid, then use <b>Send for review</b> to share a batch.</li>
    </ol>
    <p style="margin-top:14px"><a class="btn sm" href="/portal/bulk-template.csv">Download CSV template</a></p>
    <p class="hint mute">Platforms: {{ implode(', ', array_keys(\App\Models\Post::PLATFORMS)) }}. Dates: YYYY-MM-DD.</p>
  </div>
</div>
@push('scripts')
<style>
.bchk{margin:4px 0 16px;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.bchk .bsum{display:flex;gap:8px;flex-wrap:wrap;padding:12px 14px;background:var(--surface2);border-bottom:1px solid var(--border)}
.bchk .bc{padding:4px 11px;border-radius:999px;font-size:12.5px;font-weight:600;background:#fff;border:1px solid var(--border)}
.bchk .bc.ok{background:#dcfce7;border-color:#bbf7d0;color:#166534}.bchk .bc.bad{background:#fee2e2;border-color:#fecaca;color:#b42318}.bchk .bc.warn{background:#fef3c7;border-color:#fde68a;color:#92400e}
.bchk .berr{padding:10px 14px;font-size:13px;color:#b42318;border-bottom:1px solid var(--border2)}.bchk .bwarn{padding:10px 14px;font-size:13px;color:#92400e;border-bottom:1px solid var(--border2)}
.bchk table{width:100%;border-collapse:collapse;font-size:12.5px}.bchk th{background:#f8fafc;text-align:left;padding:8px 12px;color:var(--mute);font-size:11px;text-transform:uppercase;letter-spacing:.04em}
.bchk td{padding:8px 12px;border-top:1px solid var(--border2);vertical-align:top}.bchk tr.bad td{background:#fff5f5}.bchk .tick{font-weight:700}.bchk .tick.ok{color:#16a34a}.bchk .tick.bad{color:#dc2626}
.bchk .bscroll{max-height:240px;overflow:auto}
</style>
<script>
(function () {
  var form = document.querySelector('form[action$="/content/bulk"]'), box = document.getElementById('bchk'), btn = document.getElementById('bsubmit');
  var MAXI = 60, MAXR = {{ config('portal.bulk_max_rows') }}, MAXKB = {{ config('portal.max_image_kb') }}, PLAT = @json(array_keys(\App\Models\Post::PLATFORMS));
  var HOSTS = @json(\App\Models\Post::VIDEO_HOSTS), OKTYPE = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); };
  var imgIn = form.querySelector('input[name="images[]"]'), csvIn = form.querySelector('input[name="csv"]'), seq = 0;

  function parseCsv(text) { // quotes, commas inside quotes, doubled quotes, CRLF
    var rows = [], row = [], cur = '', q = false;
    text = text.replace(/^\uFEFF/, '');
    for (var i = 0; i < text.length; i++) {
      var c = text[i];
      if (q) { if (c === '"') { if (text[i + 1] === '"') { cur += '"'; i++; } else q = false; } else cur += c; }
      else if (c === '"') q = true;
      else if (c === ',') { row.push(cur); cur = ''; }
      else if (c === '\n' || c === '\r') { if (c === '\r' && text[i + 1] === '\n') i++; row.push(cur); cur = ''; rows.push(row); row = []; }
      else cur += c;
    }
    if (cur !== '' || row.length) { row.push(cur); rows.push(row); }
    return rows.filter(function (r) { return r.some(function (x) { return x.trim() !== ''; }); });
  }
  function validDate(v) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v) || /^(\d{2})[\/-](\d{2})[\/-](\d{4})$/.exec(v); if (!m) return false;
    var y = m[1].length === 4 ? +m[1] : +m[3], mo = +m[2], d = m[1].length === 4 ? +m[3] : +m[1], t = new Date(y, mo - 1, d);
    return t.getFullYear() === y && t.getMonth() === mo - 1 && t.getDate() === d;
  }
  function validVideo(u) {
    try { var x = new URL(u); if (x.protocol !== 'https:') return false; var h = x.hostname.toLowerCase().replace(/^www\./, ''); return HOSTS.some(function (k) { return h === k || h.endsWith('.' + k); }); } catch (e) { return false; }
  }

  function render(files, csvRows, csvName) {
    var errors = [], warns = [], names = {}, byName = {};
    files.forEach(function (f) {
      var k = f.name.toLowerCase();
      if (byName[k]) errors.push('Two images are both named “' + f.name + '”. Rename one.');
      byName[k] = f;
      if (OKTYPE.indexOf(f.type) < 0) errors.push('“' + f.name + '” is not a JPG, PNG, WebP or GIF image.');
      if (f.size > MAXKB * 1024) errors.push('“' + f.name + '” is larger than ' + (MAXKB / 1024) + ' MB.');
    });
    if (files.length > MAXI) errors.push('You attached ' + files.length + ' images. The limit is ' + MAXI + ' per upload.');
    var used = {}, table = [];
    if (csvRows) {
      var head = csvRows[0].map(function (h) { return h.trim().toLowerCase(); }), body = csvRows.slice(1);
      if (head.indexOf('title') < 0) errors.push('The CSV needs a header row that includes “title”. Use the template.');
      if (body.length > MAXR) errors.push('The CSV has ' + body.length + ' rows. The limit is ' + MAXR + ' per upload.');
      body.forEach(function (cols, i) {
        var r = {}; head.forEach(function (h, j) { r[h] = (cols[j] || '').trim(); });
        var line = i + 2, bad = [], title = r.title || '', plat = (r.platform || 'instagram').toLowerCase() || 'instagram';
        if (!title) bad.push('title is empty');
        if (PLAT.indexOf(plat) < 0) bad.push('platform “' + plat + '” is not one of ' + PLAT.join(', '));
        if (r.date && !validDate(r.date)) bad.push('“' + r.date + '” is not a date (use YYYY-MM-DD)');
        if (r.video_url && !validVideo(r.video_url)) bad.push('reel link must be an https YouTube, Google Drive, Frame.io or Vimeo link');
        var imgs = (r.images || '').split(/[|;]/).map(function (x) { return x.trim().toLowerCase(); }).filter(Boolean);
        imgs.forEach(function (n) { if (!byName[n]) bad.push('image “' + n + '” was not attached above'); else used[n] = true; });
        bad.forEach(function (m) { errors.push('Row ' + line + ': ' + m + '.'); });
        table.push({line: line, title: title, plat: plat, date: r.date, imgs: imgs.length, video: !!r.video_url, bad: bad});
      });
    }
    var extra = files.filter(function (f) { return !used[f.name.toLowerCase()]; });
    var made = (csvRows ? table.length : 0) + extra.length;
    if (csvRows && extra.length) warns.push(extra.length + ' attached image' + (extra.length === 1 ? ' is' : 's are') + ' not in the CSV, so each becomes its own draft post: ' + extra.slice(0, 6).map(function (f) { return f.name; }).join(', ') + (extra.length > 6 ? '…' : ''));
    if (!files.length && !csvRows) { box.hidden = true; btn.disabled = false; btn.textContent = 'Create drafts'; return; }
    if (!files.length && csvRows && table.some(function (t) { return t.imgs; })) { /* the per-row errors already name the missing images */ }

    var chips = '<span class="bc ' + (files.length > MAXI ? 'bad' : 'ok') + '">' + files.length + ' / ' + MAXI + ' images</span>'
      + (csvRows ? '<span class="bc ' + (table.length > MAXR ? 'bad' : 'ok') + '">' + table.length + ' / ' + MAXR + ' CSV rows</span>' : '')
      + '<span class="bc">' + made + ' draft post' + (made === 1 ? '' : 's') + ' will be created</span>'
      + '<span class="bc ' + (errors.length ? 'bad' : 'ok') + '">' + (errors.length ? errors.length + ' problem' + (errors.length === 1 ? '' : 's') : 'No problems') + '</span>';
    var html = '<div class="bsum">' + chips + '</div>'
      + errors.slice(0, 12).map(function (m) { return '<div class="berr">✕ ' + esc(m) + '</div>'; }).join('') + (errors.length > 12 ? '<div class="berr">…and ' + (errors.length - 12) + ' more.</div>' : '')
      + warns.map(function (m) { return '<div class="bwarn">! ' + esc(m) + '</div>'; }).join('');
    if (table.length) {
      html += '<div class="bscroll"><table><thead><tr><th>Row</th><th>Title</th><th>Platform</th><th>Date</th><th>Images</th><th></th></tr></thead><tbody>'
        + table.map(function (t) { return '<tr class="' + (t.bad.length ? 'bad' : '') + '"><td>' + t.line + '</td><td>' + esc(t.title || '—') + '</td><td>' + esc(t.plat) + '</td><td>' + esc(t.date || '—') + '</td><td>' + t.imgs + (t.video ? ' + reel' : '') + '</td><td><span class="tick ' + (t.bad.length ? 'bad' : 'ok') + '">' + (t.bad.length ? '✕' : '✓') + '</span></td></tr>'; }).join('')
        + '</tbody></table></div>';
    }
    box.innerHTML = html; box.hidden = false;
    btn.disabled = errors.length > 0; btn.textContent = errors.length ? 'Fix ' + errors.length + ' problem' + (errors.length === 1 ? '' : 's') + ' to continue' : 'Create ' + made + ' draft' + (made === 1 ? '' : 's');
  }

  function check() {
    var files = [].slice.call(imgIn.files), csv = csvIn.files[0], my = ++seq;
    if (!csv) return render(files, null, '');
    var rd = new FileReader();
    rd.onload = function () { if (my === seq) render(files, parseCsv(String(rd.result)), csv.name); };
    rd.readAsText(csv);
  }
  form.addEventListener('fin', check);
  form.addEventListener('submit', function (e) { if (btn.disabled) e.preventDefault(); });
})();
</script>
@endpush
@endsection
