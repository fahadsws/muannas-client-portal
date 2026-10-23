<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'agency_id',
        'role_id',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function sharedAccounts()
    {
        return $this->belongsToMany(Account::class)->withPivot('perm');
    }

    /** Clients this team member is assigned to. */
    public function assignedAccounts()
    {
        return $this->belongsToMany(Account::class, 'account_team')->withTimestamps();
    }

    /** Client login: sees only their own workspace. */
    public function isClient(): bool
    {
        return $this->role === 'guest';
    }

    /** Owner or team member of an agency. */
    public function isStaff(): bool
    {
        return in_array($this->role, ['owner', 'member'], true);
    }

    /** Every client in the agency, or only the assigned ones. */
    public function seesAllClients(): bool
    {
        return $this->role === 'owner' || $this->hasPerm('clients.all');
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'super' => 'Platform admin',
            'owner' => 'Admin',
            'member' => $this->agencyRole?->name ?? 'Team member',
            default => 'Client',
        };
    }

    public function agencyRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** Permission check for agency staff. Owners and the platform admin can do everything. */
    public function hasPerm(string $perm): bool
    {
        if ($this->isSuper() || $this->role === 'owner') {
            return true;
        }
        if ($this->role !== 'member') {
            return false;
        }

        return in_array($perm, $this->agencyRole?->permissions ?? [], true);
    }

    /** All permission keys this user holds (sent to the front end for showing/hiding controls). */
    public function permList(): array
    {
        if ($this->isSuper() || $this->role === 'owner') {
            return \App\Support\Permissions::keys();
        }

        return $this->role === 'member' ? array_values($this->agencyRole?->permissions ?? []) : [];
    }

    public function isSuper(): bool
    {
        return $this->role === 'super';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
