@extends('portal.layout')
@section('title', $project->name.' · Projects & Tasks')
@section('crumb', 'Projects & Tasks')
@section('content')
@php use App\Models\Project; use App\Models\Task; use App\Support\Ui; $done = $project->tasks->where('status', 'done')->count(); @endphp
<a class="back" href="/portal/c/{{ $account->id }}/projects">{!! Ui::icon('arrow') !!}All projects</a>
<div class="pagehead"><div><h1>{{ $project->name }}</h1>
    <p><span class="stag s-{{ $project->status }}">{{ Project::STATUSES[$project->status] }}</span>@if($project->due_on) &nbsp;Due {{ $project->due_on->format('j M Y') }}@endif @if($project->description) · {{ $project->description }}@endif</p></div>
  @if($manage)<div class="prow">
    <button type="button" class="btn" data-modal="editProject">{!! Ui::icon('edit') !!}Edit project</button>
    <button type="button" class="btn primary" data-modal="taskModal" data-task="">{!! Ui::icon('plus') !!}Add task</button>
  </div>@endif</div>

<div class="pn" style="margin-bottom:16px">
  <div class="prow" style="gap:12px"><div class="pmeter" style="flex:1"><i style="width:{{ $project->progress() }}%"></i></div><b>{{ $project->progress() }}%</b></div>
  <small>{{ $done }} of {{ $project->tasks->count() }} tasks done</small>
</div>

<div class="pn">
  <h3 style="margin-bottom:12px">Tasks</h3>
  @if($project->tasks->isEmpty())
    <p class="mute" style="margin:0">No tasks yet.@if($manage) Use “Add task” to create the first one.@endif</p>
  @else
    <div class="trow head"><span>Task</span><span>Assigned to</span><span>Due</span><span>Status</span><span></span></div>
    @foreach($project->tasks as $t)
      <div class="trow">
        <div style="min-width:0"><b style="{{ $t->status === 'done' ? 'text-decoration:line-through;color:var(--mute)' : '' }}">{{ $t->title }}</b>@if($t->description)<small style="display:block;color:var(--mute)">{{ \Illuminate\Support\Str::limit($t->description, 110) }}</small>@endif</div>
        <div class="who">@if($t->assignee){!! Ui::avatar($t->assignee->name) !!}<span>{{ $t->assignee->name }}</span>@else<span class="mute">Unassigned</span>@endif</div>
        <div style="font-size:13px;{{ $t->overdue() ? 'color:var(--red);font-weight:600' : 'color:var(--mute)' }}">{{ $t->due_on ? $t->due_on->format('j M Y') : '—' }}{{ $t->overdue() ? ' · overdue' : '' }}</div>
        <div>
          @if($manage)
            <form method="post" action="/portal/tasks/{{ $t->id }}" style="margin:0">@csrf @method('PUT')
              <select name="status" data-search="99" onchange="this.form.submit()">@foreach(Task::STATUSES as $k => $l)<option value="{{ $k }}" @selected($t->status === $k)>{{ $l }}</option>@endforeach</select></form>
          @else
            <span class="stag s-{{ $t->status }}">{{ Task::STATUSES[$t->status] }}</span>
          @endif
        </div>
        <div class="prow" style="gap:4px;justify-content:flex-end">
          @if($manage)
            <button type="button" class="ibtn" title="Edit task" aria-label="Edit task" data-modal="taskModal" data-task="{{ json_encode(['id' => $t->id, 'title' => $t->title, 'description' => $t->description, 'assignee_id' => $t->assignee_id, 'due_on' => $t->due_on?->format('Y-m-d'), 'status' => $t->status]) }}">{!! Ui::icon('edit') !!}</button>
            <form method="post" action="/portal/tasks/{{ $t->id }}" data-confirm="Remove this task?" style="margin:0">@csrf @method('DELETE')<button class="ibtn" aria-label="Remove task">{!! Ui::icon('trash') !!}</button></form>
          @endif
        </div>
      </div>
    @endforeach
  @endif
</div>

@if($manage)
<x-modal id="taskModal" title="Add task">
  <form class="task-form" method="post" action="/portal/projects/{{ $project->id }}/tasks" data-add="/portal/projects/{{ $project->id }}/tasks">@csrf <input type="hidden" name="_method" value="POST">
    <div class="task-grid">
      <div class="fld"><label>Task</label><input name="title" required maxlength="160" placeholder="e.g. Design October carousel"></div>
      <div class="fld"><label>Assign to</label><select name="assignee_id" data-search="99"><option value="">Unassigned</option>@foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
    </div>
    <div class="fld"><label>Details (optional)</label><textarea name="description" maxlength="1000" style="min-height:70px"></textarea></div>
    <div class="task-grid">
      <div class="fld"><label>Due date</label><input type="date" name="due_on"></div>
      <div class="fld"><label>Status</label><select name="status" data-search="99">@foreach(Task::STATUSES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
    </div>
    <button class="btn primary sm">Save task</button>
  </form>
</x-modal>

<x-modal id="editProject" title="Edit project">
  <form method="post" action="/portal/projects/{{ $project->id }}">@csrf @method('PUT')
    <div class="fld"><label>Name</label><input name="name" required maxlength="120" value="{{ $project->name }}"></div>
    <div class="fld"><label>Description</label><textarea name="description" maxlength="500" style="min-height:70px">{{ $project->description }}</textarea></div>
    <div class="two">
      <div class="fld"><label>Due date</label><input type="date" name="due_on" value="{{ $project->due_on?->format('Y-m-d') }}"></div>
      <div class="fld"><label>Status</label><select name="status" data-search="99">@foreach(Project::STATUSES as $k => $l)<option value="{{ $k }}" @selected($project->status === $k)>{{ $l }}</option>@endforeach</select></div>
    </div>
    <div class="prow between"><button class="btn primary sm">Save project</button></div>
  </form>
  <form method="post" action="/portal/projects/{{ $project->id }}" data-confirm="Delete this project and all its tasks?" style="margin-top:14px">@csrf @method('DELETE')<button class="btn sm danger">{!! Ui::icon('trash') !!}Delete project</button></form>
</x-modal>
@endif
@endsection
