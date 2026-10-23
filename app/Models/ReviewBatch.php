<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewBatch extends Model
{
    protected $guarded = [];

    protected $casts = ['sent_at' => 'datetime'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'batch_id')->orderBy('scheduled_on')->orderBy('id');
    }

    /** The link the team pastes into WhatsApp. */
    public function url(): string
    {
        return url('/r/'.$this->token);
    }
}
