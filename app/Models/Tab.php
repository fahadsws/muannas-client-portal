<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tab extends Model
{
    protected $guarded = [];

    protected $casts = ['meta' => 'array'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class)->orderBy('position')->orderBy('col')->orderBy('id');
    }
}
