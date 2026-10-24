@extends('portal.layout')
@section('title', 'Projects & Tasks')
@section('content')
@php use App\Models\Project; use App\Support\Ui; @endphp
<div class="pagehead"><div><h1>Projects &amp; Tasks</h1><p>Where each piece of work stands. Open a project to see and manage its tasks.</p></div>
  @if($manage)<div class="prow"><button type="button" class="btn primary" data-modal="addProject">{!! Ui::icon('plus') !!}Add project</button></div>@endif</div>

@if($manage)
<x-modal id="addProject" title="Add project">
<form method="post" action="/portal/c/{{ $account->id }}/projects">@csrf
  <div class="two"><div class="fld"><label>Name</label><input name="name" required maxlength="120" placeholder="October Content"></div>
  <div class="fld"><label>Due date</label><input type="date" name="due_on"></div></div>
  <div class="fld"><label>Description</label><textarea name="description" maxlength="500" style="min-height:70px"></textarea></div>
  <button class="btn primary sm">Add project</button>
</form>
</x-modal>
@endif

@if($projects->isEmpty())
  <div class="empty"><div class="eic">{!! Ui::icon('photo') !!}</div><h3>No projects yet</h3><p>@if($manage)Add the first project above.@else Your team will add projects here as work begins.@endif</p></div>
@else
<div class="pcards">
  @foreach($projects as $pr)
    @php $people = $pr->tasks->pluck('assignee')->filter()->unique('id')->values(); @endphp
    <a class="pcard" href="/portal/projects/{{ $pr->id }}">
      <div class="prow between" style="gap:8px"><h3>{{ $pr->name }}</h3><span class="stag s-{{ $pr->status }}">{{ Project::STATUSES[$pr->status] }}</span></div>
      @if($pr->description)<p>{{ $pr->description }}</p>@endif
      <div class="prow" style="gap:10px"><div class="pmeter" style="flex:1"><i style="width:{{ $pr->progress() }}%"></i></div><b style="font-size:12.5px">{{ $pr->progress() }}%</b></div>
      <div class="foot">
        <span>{{ $pr->tasks->where('status', 'done')->count() }}/{{ $pr->tasks->count() }} tasks done @if($pr->due_on)· Due {{ $pr->due_on->format('j M') }}@endif</span>
        @if($people->isNotEmpty())<span class="avs">@foreach($people->take(4) as $p){!! Ui::avatar($p->name) !!}@endforeach</span>@endif
      </div>
    </a>
  @endforeach
</div>
@endif
@endsection
