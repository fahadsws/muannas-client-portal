<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $guarded = [];

    protected $casts = ['due_on' => 'date'];

    public const STATUSES = ['active' => 'In progress', 'on_hold' => 'On hold', 'completed' => 'Completed'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('position')->orderBy('id');
    }

    /** Whole-number percentage of tasks that are done. */
    public function progress(): int
    {
        $total = $this->tasks->count();

        return $total ? (int) round($this->tasks->where('status', 'done')->count() / $total * 100) : ($this->status === 'completed' ? 100 : 0);
    }
}
