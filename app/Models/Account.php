<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $guarded = [];

    protected $casts = ['docs' => 'array', 'share_enabled' => 'boolean'];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function tabs()
    {
        return $this->hasMany(Tab::class)->orderBy('position')->orderBy('id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class)->latest('id');
    }

    public function people()
    {
        return $this->belongsToMany(User::class)->withPivot('perm');
    }
}
