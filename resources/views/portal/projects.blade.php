@extends('portal.layout')
@section('title', 'Projects & Tasks')
@section('content')
@php use App\Models\Project; use App\Models\Task; use App\Support\Ui; @endphp
<div class="pagehead"><div><h1>Projects &amp; Tasks</h1><p>Where each piece of work stands.</p></div>
  @if($manage)<div class="prow"><button type="button" class="btn primary" data-modal="addProject">{!! Ui::icon('plus') !!}Add project</button></div>@endif</div>

@if($manage)
<x-modal id="addProject" title="Add project">
<form method="post" action="/portal/c/{{ $account->id }}/projects">@csrf
  <div class="two"><div class="fld"><label>Name</label><input name="name" required maxlength="120" placeholder="October Content"></div>
  <div class="fld"><label>Due date</label><input type="date" name="due_on"></div></div>
  <div class="fld"><label>Description</label><input name="description" maxlength="500"></div>
  <button class="btn primary sm">Add project</button>
</form>
</x-modal>
@endif

@forelse($projects as $pr)
  <div class="pn" style="margin-bottom:18px">
    <div class="prow between">
      <div><h3 style="margin:0">{{ $pr->name }}</h3>@if($pr->description)<small>{{ $pr->description }}</small>@endif</div>
      <div class="prow"><span class="stag s-{{ $pr->status }}">{{ Project::STATUSES[$pr->status] }}</span>@if($pr->due_on)<small>Due {{ $pr->due_on->format('j M Y') }}</small>@endif</div>
    </div>
    <div class="prow" style="margin:12px 0 6px;gap:12px"><div class="pmeter" style="flex:1"><i style="width:{{ $pr->progress() }}%"></i></div><b>{{ $pr->progress() }}%</b></div>
    <small>{{ $pr->tasks->where('status', 'done')->count() }} of {{ $pr->tasks->count() }} tasks done</small>

    <div style="margin-top:10px">
      @forelse($pr->tasks as $t)
        <div class="file">
          @if($manage)
            <form method="post" action="/portal/tasks/{{ $t->id }}" class="tedit">@csrf @method('PUT')
              <input name="title" value="{{ $t->title }}" required maxlength="160" placeholder="Task">
              <input type="date" name="due_on" value="{{ $t->due_on?->format('Y-m-d') }}">
              <button class="btn sm primary">Save</button><button type="button" class="btn sm" data-edit-cancel>Cancel</button>
            </form>
          @endif
          <div class="nm"><b style="{{ $t->status === 'done' ? 'text-decoration:line-through;color:var(--mute)' : '' }}">{{ $t->title }}</b>
            @if($t->due_on)<small style="{{ $t->overdue() ? 'color:var(--red);font-weight:600' : '' }}">Due {{ $t->due_on->format('j M') }}{{ $t->overdue() ? ' · overdue' : '' }}</small>@endif</div>
          @if($manage)
            <form method="post" action="/portal/tasks/{{ $t->id }}" class="inl" style="margin:0">@csrf @method('PUT')
              <select name="status" onchange="this.form.submit()" style="min-height:36px;padding:4px 8px">@foreach(Task::STATUSES as $k => $l)<option value="{{ $k }}" @selected($t->status === $k)>{{ $l }}</option>@endforeach</select></form>
            <button type="button" class="ibtn" title="Edit task" aria-label="Edit task" data-edit-task>{!! Ui::icon('edit') !!}</button>
            <form method="post" action="/portal/tasks/{{ $t->id }}" data-confirm="Remove this task?" style="margin:0">@csrf @method('DELETE')<button class="ibtn" aria-label="Remove task">{!! Ui::icon('trash') !!}</button></form>
          @else
            <span class="stag s-{{ $t->status }}">{{ Task::STATUSES[$t->status] }}</span>
          @endif
        </div>
      @empty
        <p class="mute" style="margin:6px 0 0">No tasks yet.</p>
      @endforelse
    </div>

    @if($manage)
      <form class="inl" method="post" action="/portal/projects/{{ $pr->id }}/tasks" style="margin-top:12px">@csrf
        <input name="title" placeholder="New task" required maxlength="160"><input type="date" name="due_on" style="max-width:160px"><button class="btn sm primary">Add task</button></form>
      <form class="inl" method="post" action="/portal/projects/{{ $pr->id }}" style="margin-top:10px">@csrf @method('PUT')
        <select name="status" style="max-width:170px">@foreach(Project::STATUSES as $k => $l)<option value="{{ $k }}" @selected($pr->status === $k)>{{ $l }}</option>@endforeach</select>
        <button class="btn sm">Set status</button>
      </form>
      <form method="post" action="/portal/projects/{{ $pr->id }}" data-confirm="Delete this project and all its tasks?" style="margin-top:10px">@csrf @method('DELETE')<button class="btn sm danger">Delete project</button></form>
    @endif
  </div>
@empty
  <div class="empty"><div class="eic">{!! \App\Support\Ui::icon('photo') !!}</div><h3>No projects yet</h3><p>@if($manage)Add the first project above.@else Your team will add projects here as work begins.@endif</p></div>
@endforelse

@if($manage)
<style>
.file .tedit{display:none}
.file.editing>:not(.tedit){display:none!important}
.file.editing .tedit{display:flex;flex:1;gap:8px;align-items:center;flex-wrap:wrap;margin:0}
.file .tedit input[name=title]{flex:1 1 260px;min-width:0}
.file .tedit input:not([type=hidden]){height:38px;padding:0 12px;border:1px solid var(--border);border-radius:9px;background:#fff;font:inherit;font-size:13.5px;outline:none}
.file .tedit input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft)}
</style>
@push('scripts')
<script>
  document.querySelectorAll('[data-edit-task]').forEach(function (b) {
    b.addEventListener('click', function () {
      var row = b.closest('.file'); row.classList.add('editing');
      var t = row.querySelector('.tedit input[name=title]'); t.focus(); t.setSelectionRange(t.value.length, t.value.length);
    });
  });
  document.querySelectorAll('[data-edit-cancel]').forEach(function (b) {
    b.addEventListener('click', function () { var row = b.closest('.file'); row.classList.remove('editing'); row.querySelector('.tedit').reset(); var d = row.querySelector('.tedit input[name=due_on]'); if (d._flatpickr) d._flatpickr.setDate(d.defaultValue || null, false); });
  });
</script>
@endpush
@endif
@endsection
