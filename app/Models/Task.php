<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = [];

    protected $casts = ['due_on' => 'date'];

    public const STATUSES = ['todo' => 'To do', 'doing' => 'In progress', 'done' => 'Done'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function overdue(): bool
    {
        return $this->status !== 'done' && $this->due_on && $this->due_on->isBefore(today());
    }
}
