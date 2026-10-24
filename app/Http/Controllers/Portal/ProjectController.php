<?php

namespace App\Http\Controllers\Portal;

use App\Models\Account;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends PortalController
{
    public function index(Request $r, Account $account)
    {
        $this->reach($r, $account);
        $projects = $account->projects()->with('tasks.assignee')->get();

        return view('portal.projects', ['account' => $account, 'projects' => $projects, 'manage' => $this->isStaff($r) && $this->reach($r, $account) === 'edit']);
    }

    public function show(Request $r, Project $project)
    {
        $account = $project->account;
        $this->reach($r, $account);
        $project->load('tasks.assignee');

        return view('portal.project-show', ['account' => $account, 'project' => $project, 'manage' => $this->isStaff($r) && $this->reach($r, $account) === 'edit', 'staff' => $this->assignable($account)]);
    }

    /** Team members who can take a task: everyone assigned to this client plus the agency owners. */
    private function assignable(Account $account)
    {
        return User::where('agency_id', $account->agency_id)->whereIn('role', ['owner', 'member'])
            ->where(fn ($q) => $q->where('role', 'owner')->orWhereIn('id', $account->team()->select('users.id')))->orderBy('name')->get(['id', 'name']);
    }

    private function assignee(Request $r, Task|Project $for, ?int $id): ?int
    {
        $account = $for instanceof Task ? $for->project->account : $for->account;

        return $id && $this->assignable($account)->contains('id', $id) ? $id : null;
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
        $id = $project->account_id;
        $project->delete();

        return redirect('/portal/c/'.$id.'/projects')->with('ok', 'Project deleted');
    }

    public function addTask(Request $r, Project $project)
    {
        $this->manage($r, $project->account);
        $v = $r->validate(['title' => 'required|string|max:160', 'description' => 'nullable|string|max:1000', 'assignee_id' => 'nullable|integer', 'due_on' => 'nullable|date', 'status' => ['nullable', Rule::in(array_keys(Task::STATUSES))]]);
        $v['assignee_id'] = $this->assignee($r, $project, $v['assignee_id'] ?? null);
        $v['status'] = $v['status'] ?? 'todo';
        $project->tasks()->create($v + ['position' => ($project->tasks()->max('position') ?? 0) + 1]);

        return back()->with('ok', 'Task added');
    }

    public function updateTask(Request $r, Task $task)
    {
        $this->manage($r, $task->project->account);
        $v = $r->validate([
            'title' => 'sometimes|required|string|max:160', 'description' => 'nullable|string|max:1000', 'assignee_id' => 'nullable|integer', 'due_on' => 'nullable|date',
            'status' => ['sometimes', Rule::in(array_keys(Task::STATUSES))],
        ]);
        if ($r->has('assignee_id')) {
            $v['assignee_id'] = $this->assignee($r, $task, $v['assignee_id'] ?? null);
        }
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
