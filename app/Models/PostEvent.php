<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime'];

    public const LABELS = [
        'submitted' => 'Sent for review',
        'approved' => 'Approved',
        'changes_requested' => 'Requested changes',
        'published' => 'Marked published',
        'reopened' => 'Moved back to draft',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->action] ?? $this->action;
    }
}
