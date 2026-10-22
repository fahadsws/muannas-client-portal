<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class)->orderBy('position')->orderBy('id');
    }

    public function templates()
    {
        return $this->hasMany(DocTemplate::class)->orderBy('id');
    }
}
