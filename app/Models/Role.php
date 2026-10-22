<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    protected $casts = ['permissions' => 'array', 'is_system' => 'boolean'];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isOwnerRole(): bool
    {
        return $this->is_system && $this->name === 'Owner';
    }

    /** Create the four starting roles for an agency (idempotent) and give existing members a role. */
    public static function ensureDefaults(Agency $agency): void
    {
        foreach (Permissions::defaultRoles() as $r) {
            static::firstOrCreate(['agency_id' => $agency->id, 'name' => $r['name']], $r);
        }
        $manager = static::where('agency_id', $agency->id)->where('name', 'Manager')->first();
        $owner = static::where('agency_id', $agency->id)->where('name', 'Owner')->first();
        User::where('agency_id', $agency->id)->where('role', 'member')->whereNull('role_id')->update(['role_id' => $manager->id]);
        User::where('agency_id', $agency->id)->where('role', 'owner')->whereNull('role_id')->update(['role_id' => $owner->id]);
    }
}
