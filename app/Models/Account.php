<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    protected $guarded = [];

    protected $casts = ['docs' => 'array', 'share_enabled' => 'boolean', 'activity_at' => 'datetime'];

    /** Adds `activity_at`: the newest change anywhere in this client (content, board, projects, files, invoices, reports, notes). */
    public function scopeWithActivity($q)
    {
        $subs = collect(['posts', 'projects', 'client_files', 'invoices', 'reports', 'notes'])
            ->map(fn ($t) => "(select max($t.updated_at) from $t where $t.account_id = accounts.id)")
            ->push('(select max(items.updated_at) from items join tabs on tabs.id = items.tab_id where tabs.account_id = accounts.id)')
            ->push('(select max(tasks.updated_at) from tasks join projects pr on pr.id = tasks.project_id where pr.account_id = accounts.id)');
        $fn = DB::getDriverName() === 'sqlite' ? 'max' : 'greatest';
        $expr = $fn.'(accounts.updated_at, '.$subs->map(fn ($s) => "coalesce($s, accounts.updated_at)")->implode(', ').')';

        return $q->select('accounts.*')->selectRaw("$expr as activity_at");
    }

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

    /** Client logins (role "guest") for this workspace. */
    public function clientUsers()
    {
        return $this->people()->where('users.role', 'guest');
    }

    /** Muannas team members assigned to look after this client. */
    public function team()
    {
        return $this->belongsToMany(User::class, 'account_team')->withTimestamps();
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function batches()
    {
        return $this->hasMany(ReviewBatch::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class)->latest('id');
    }

    public function files()
    {
        return $this->hasMany(ClientFile::class)->latest('id');
    }

    public function fileFolders()
    {
        return $this->hasMany(FileFolder::class)->orderBy('name');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class)->orderByDesc('issued_on')->orderByDesc('id');
    }

    public function currencyName(): string
    {
        return config('currencies.'.$this->currency, $this->currency);
    }

    public function reports()
    {
        return $this->hasMany(Report::class)->orderByDesc('month');
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? route('portal.logo', $this).'?v='.$this->updated_at?->timestamp : null;
    }
}
