<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A folder the team creates inside a client's Files & Assets. Its files use the folder key "c{id}". */
class FileFolder extends Model
{
    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function key(): string
    {
        return 'c'.$this->id;
    }
}
