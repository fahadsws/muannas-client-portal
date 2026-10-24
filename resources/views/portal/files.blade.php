@extends('portal.layout')
@section('title', 'Files & Assets')
@section('content')
@php use App\Models\ClientFile; use App\Support\Ui; $base = '/portal/c/'.$account->id; @endphp
<div class="pagehead"><div><h1>Files &amp; Assets</h1><p>Brand kit, guidelines and final deliverables, plus anything you want to share with us. Open a folder to see its files.</p></div>
  <div class="prow">
    @if($staff)<button type="button" class="btn" data-modal="newFolder">{!! Ui::icon('plus') !!}New folder</button>@endif
    @if($canUpload)<button type="button" class="btn primary" data-modal="uploadFiles">{!! Ui::icon('upload') !!}Upload files</button>@endif
  </div></div>

<div class="gsearch">{!! Ui::icon('search') !!}<input type="search" id="gsearch" data-url="{{ $base }}/files-search" placeholder="Search all files by name…" autocomplete="off"><div class="gres" id="gres" hidden></div></div>

@if($canUpload)
<x-modal id="uploadFiles" title="Upload files">
<form method="post" enctype="multipart/form-data" action="{{ $base }}/files">@csrf
  @if($staff)
    <div class="fld"><label>Folder</label><select name="folder">
      @foreach($folders->where('key', '!=', 'reports') as $f)<option value="{{ $f['key'] }}" @selected($f['key'] === 'deliverables')>{{ $f['name'] }}</option>@endforeach</select></div>
  @else
    <p class="mute" style="margin-top:0">Files you upload go to the “Shared by you” folder: raw photos, references, anything we should see.</p>
  @endif
  <div class="fld"><x-file-input name="files" multiple label="Attach files" hint="Up to 20 files, {{ config('portal.max_file_kb') / 1024 }} MB each. Images, PDF, Office files, ZIP, AI/PSD/EPS and audio. Videos: share a link in the post instead." /></div>
  <button class="btn primary sm">Upload</button>
</form>
</x-modal>
@endif

@if($staff)
<x-modal id="newFolder" title="New folder">
<form method="post" action="{{ $base }}/file-folders">@csrf
  <div class="fld"><label>Folder name</label><input name="name" required maxlength="80" placeholder="e.g. Campaign photos" value="{{ old('name') }}"></div>
  <button class="btn primary sm">Create folder</button>
</form>
</x-modal>
@endif

<style>
.fgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px}
.fcardx{position:relative;display:block;text-decoration:none;color:inherit;background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px;transition:.15s}
.fcardx:hover{border-color:#cbd5e1;box-shadow:var(--shadow-md)}
.fcardx .fic{display:grid;place-items:center;width:42px;height:42px;border-radius:10px;background:#eef2ff;color:#4f46e5;margin-bottom:14px}.fcardx .fic .i{width:20px;height:20px}
.fcardx.rep .fic{background:#f0fdf4;color:#16a34a}.fcardx.mine .fic{background:#fff7ed;color:#ea580c}
.fcardx b{display:block;font-size:14.5px;font-weight:650;margin-bottom:2px}.fcardx small{color:var(--mute);font-size:12px}
.fcardx .facts{position:absolute;top:10px;right:10px;display:flex;gap:2px}
</style>
<div class="fgrid">
  @foreach($folders as $f)
    <a class="fcardx {{ $f['key'] === 'reports' ? 'rep' : ($f['key'] === 'client' ? 'mine' : '') }}" href="{{ $base }}/files/folder/{{ $f['key'] }}">
      <div class="fic">{!! Ui::icon('folder') !!}</div>
      <b>{{ $f['name'] }}</b><small>{{ $f['n'] }} {{ \Illuminate\Support\Str::plural('file', $f['n']) }}@if($f['latest']) · updated {{ \Carbon\Carbon::parse($f['latest'])->format('j M Y') }}@endif</small>
      @if($staff && $f['custom'])
        <div class="facts" onclick="event.preventDefault();event.stopPropagation()">
          <button type="button" class="ibtn" title="Rename" data-rename="{{ $f['custom']->id }}" data-name="{{ $f['name'] }}">{!! Ui::icon('edit') !!}</button>
        </div>
      @endif
    </a>
  @endforeach
</div>

@if($staff)
<x-modal id="renameFolder" title="Rename folder">
<form method="post" action="" id="renameForm">@csrf @method('PUT')
  <div class="fld"><label>Folder name</label><input name="name" id="rn-name" required maxlength="80"></div>
  <button class="btn primary sm">Save</button>
</form>
</x-modal>
@push('scripts')
<script>
  document.querySelectorAll('[data-rename]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.getElementById('renameForm').action = '/portal/file-folders/' + b.dataset.rename;
      document.getElementById('rn-name').value = b.dataset.name;
      document.getElementById('renameFolder').hidden = false; document.body.classList.add('mdl-open');
    });
  });
</script>
@endpush
@endif
@endsection
