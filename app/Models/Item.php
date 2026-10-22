<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $guarded = [];

    protected $casts = ['data' => 'array', 'locked' => 'boolean'];

    public function tab()
    {
        return $this->belongsTo(Tab::class);
    }
}
