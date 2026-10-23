<?php

namespace App\Http\Controllers\Portal;

use App\Models\Account;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends PortalController
{
    public function index(Request $r, Account $account)
    {
        $this->reach($r, $account);
        $projects = $account->projects()->with('tasks')->get();

        return view('portal.projects', ['account' => $account, 'projects' => $projects, 'manage' => $this->isStaff($r) && $this->reach($r, $account) === 'edit']);
    }

    public function store(Request $r, Account $account)
    {
        $this->manage($r, $account);
        $v = $r->validate(['name' => 'required|string|max:120', 'description' => 'nullable|string|max:500', 'due_on' => 'nullable|date']);
        $account->projects()->create($v);

        return back()->with('ok', 'Project added');
    }

    public function update(Request $r, Project $project)
    {
        $this->manage($r, $project->account);
        $v = $r->validate([
            'name' => 'sometimes|required|string|max:120', 'description' => 'nullable|string|max:500', 'due_on' => 'nullable|date',
            'status' => ['sometimes', Rule::in(array_keys(Project::STATUSES))],
        ]);
        $project->update($v);

        return back()->with('ok', 'Project updated');
    }

    public function destroy(Request $r, Project $project)
    {
        $this->manage($r, $project->account);
        $project->delete();

        return back()->with('ok', 'Project deleted');
    }

    public function addTask(Request $r, Project $project)
    {
        $this->manage($r, $project->account);
        $v = $r->validate(['title' => 'required|string|max:160', 'due_on' => 'nullable|date']);
        $project->tasks()->create($v + ['position' => ($project->tasks()->max('position') ?? 0) + 1]);

        return back()->with('ok', 'Task added');
    }

    public function updateTask(Request $r, Task $task)
    {
        $this->manage($r, $task->project->account);
        $v = $r->validate([
            'title' => 'sometimes|required|string|max:160', 'due_on' => 'nullable|date',
            'status' => ['sometimes', Rule::in(array_keys(Task::STATUSES))],
        ]);
        $task->update($v);
        $project = $task->project->load('tasks');
        // finishing the last task completes the project; reopening a task reactivates it
        if ($project->tasks->isNotEmpty() && $project->tasks->every(fn ($t) => $t->status === 'done')) {
            $project->update(['status' => 'completed']);
        } elseif ($project->status === 'completed') {
            $project->update(['status' => 'active']);
        }

        return back();
    }

    public function destroyTask(Request $r, Task $task)
    {
        $this->manage($r, $task->project->account);
        $task->delete();

        return back()->with('ok', 'Task removed');
    }
}
