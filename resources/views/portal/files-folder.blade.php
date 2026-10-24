@extends('portal.layout')
@section('title', $label.' · Files & Assets')
@section('crumb', 'Files & Assets')
@section('content')
@php use App\Support\Ui; $base = '/portal/c/'.$account->id; @endphp
<a class="back" href="{{ $base }}/files">{!! Ui::icon('arrow') !!}All folders</a>
<div class="pagehead"><div><h1>{{ $label }}</h1><p>{{ $files->total() }} {{ \Illuminate\Support\Str::plural('file', $files->total()) }}{{ $q !== '' ? ' matching “'.$q.'”' : '' }}@if($key === 'reports') · added automatically when a monthly report PDF is uploaded @endif</p></div>
  <div class="prow">
    @if($staff && $custom)<button type="button" class="btn" data-modal="renameFolder">{!! Ui::icon('edit') !!}Rename</button>
      <form method="post" action="/portal/file-folders/{{ $custom->id }}" data-confirm="Delete the folder “{{ $label }}”? It must be empty." style="margin:0">@csrf @method('DELETE')<button class="btn danger">{!! Ui::icon('trash') !!}Delete folder</button></form>@endif
    @if($canUpload)<button type="button" class="btn primary" data-modal="uploadFiles">{!! Ui::icon('upload') !!}Upload files</button>@endif
  </div></div>

<form method="get" class="gsearch" style="max-width:520px">{!! Ui::icon('search') !!}<input type="search" name="q" value="{{ $q }}" placeholder="Search this folder by file name…" autocomplete="off"></form>

@if($canUpload)
<x-modal id="uploadFiles" title="Upload to {{ $label }}">
<form method="post" enctype="multipart/form-data" action="{{ $base }}/files">@csrf
  <input type="hidden" name="folder" value="{{ $key }}">
  <div class="fld"><x-file-input name="files" multiple label="Attach files" hint="Up to 20 files, {{ config('portal.max_file_kb') / 1024 }} MB each. Images, PDF, Office files, ZIP, AI/PSD/EPS and audio." /></div>
  <button class="btn primary sm">Upload</button>
</form>
</x-modal>
@endif
@if($staff && $custom)
<x-modal id="renameFolder" title="Rename folder">
<form method="post" action="/portal/file-folders/{{ $custom->id }}">@csrf @method('PUT')
  <div class="fld"><label>Folder name</label><input name="name" required maxlength="80" value="{{ $custom->name }}"></div>
  <button class="btn primary sm">Save</button>
</form>
</x-modal>
@endif

<div class="pn folder">
  @forelse($files as $f)
    @php [$kind, $fg, $bg] = Ui::fileKind($f->name); @endphp
    <div class="file">
      @if($f->isImage())<a href="/portal/files/{{ $f->id }}?view=1" target="_blank" rel="noopener"><img class="thumb" src="/portal/files/{{ $f->id }}?view=1" alt="" loading="lazy"></a>
      @else<a class="ftype" href="/portal/files/{{ $f->id }}?view=1" target="_blank" rel="noopener" style="background:{{ $bg }};color:{{ $fg }}" title="Open {{ $f->name }}">{{ $kind }}</a>@endif
      <div class="nm"><b><a href="/portal/files/{{ $f->id }}?view=1" target="_blank" rel="noopener" class="flink" style="color:inherit;text-decoration:none">{{ $f->name }}</a></b><small>{{ $f->sizeLabel() }} · {{ $f->uploader }} · {{ $f->created_at->format('j M Y') }}</small></div>
      <a class="btn sm" href="/portal/files/{{ $f->id }}">{!! Ui::icon('dl') !!}Download</a>
      @if(! $f->isLinked() && ($staff || ($f->uploaded_by === auth()->id())))
        <form method="post" action="/portal/files/{{ $f->id }}" data-confirm="Delete {{ $f->name }}?" style="margin:0">@csrf @method('DELETE')<button class="ibtn" aria-label="Delete file">{!! Ui::icon('trash') !!}</button></form>
      @endif
    </div>
  @empty
    <p class="mute" style="margin:0">{{ $q !== '' ? 'No file in this folder matches “'.$q.'”.' : 'Nothing here yet.' }}@if($canUpload && $q === '') Use “Upload files” to add the first one.@endif</p>
  @endforelse
</div>
@include('partials.pager', ['p' => $files])
@endsection
