<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Agency team: server-rendered list + create/edit/remove with role assignment. */
class TeamController extends Controller
{
    private function agencyOf(Request $r)
    {
        return $r->user()->agency;
    }

    private function member(Request $r, User $user): User
    {
        abort_unless($user->agency_id === $r->user()->agency_id && in_array($user->role, ['owner', 'member']), 404);

        return $user;
    }

    private function assignable(Request $r)
    {
        return Role::where('agency_id', $r->user()->agency_id)->where('name', '!=', 'Owner')->orderBy('id')->get();
    }

    public function index(Request $r)
    {
        $agency = $this->agencyOf($r);
        $q = trim((string) $r->query('q', ''));
        $roleId = $r->query('role');
        $clientId = (int) $r->query('client');
        [$sortKey, $dirRaw] = array_pad(explode(':', (string) $r->query('order', 'role:asc')), 2, 'asc');
        $dir = $dirRaw === 'desc' ? 'desc' : 'asc';
        $per = in_array((int) $r->query('per_page'), [10, 25, 50]) ? (int) $r->query('per_page') : 10;
        $staff = fn () => User::where('agency_id', $agency->id)->whereIn('role', ['owner', 'member']);

        $query = $staff()->with('agencyRole')->withCount('assignedAccounts')
            ->when($q, fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->when(in_array($r->query('type'), ['owner', 'member']), fn ($x) => $x->where('role', $r->query('type')))
            ->when($roleId === 'owner', fn ($x) => $x->where('role', 'owner'))
            ->when($roleId && $roleId !== 'owner', fn ($x) => $x->where('role_id', (int) $roleId))
            ->when($clientId, fn ($x) => $x->where(fn ($w) => $w->where('role', 'owner')->orWhereHas('assignedAccounts', fn ($a) => $a->where('accounts.id', $clientId))))
            ->when($r->filled('from'), fn ($x) => $x->whereDate('created_at', '>=', $r->query('from')))
            ->when($r->filled('to'), fn ($x) => $x->whereDate('created_at', '<=', $r->query('to')));

        match ($sortKey) {
            'name' => $query->orderBy('name', $dir),
            'email' => $query->orderBy('email', $dir),
            'clients' => $query->orderBy('assigned_accounts_count', $dir),
            'created' => $query->orderBy('created_at', $dir),
            default => $query->orderByRaw("role = 'owner' ".($dir === 'asc' ? 'desc' : 'asc'))->orderBy('name'),
        };

        $staffCount = $staff()->count();
        $owners = $staff()->where('role', 'owner')->count();

        return view('team.index', [
            'members' => $query->paginate($per)->withQueryString(), 'roles' => Role::where('agency_id', $agency->id)->orderBy('id')->get(), 'agency' => $agency,
            'staffCount' => $staffCount, 'clients' => Account::where('agency_id', $agency->id)->orderBy('name')->get(['id', 'name']),
            'view' => $r->query('view', 'table') === 'cards' ? 'cards' : 'table',
            'tabs' => ['all' => $staffCount, 'owner' => $owners, 'member' => $staffCount - $owners],
        ]);
    }

    public function create(Request $r)
    {
        abort_unless($r->user()->hasPerm('team.manage'), 403);

        return view('team.form', ['u' => null, 'roles' => $this->assignable($r), 'agency' => $this->agencyOf($r)]);
    }

    public function store(Request $r)
    {
        $me = $r->user();
        abort_unless($me->hasPerm('team.manage'), 403);
        $agency = $this->agencyOf($r);
        $roleIds = $this->assignable($r)->pluck('id')->all();
        $v = $r->validate([
            'name' => 'required|string|max:80', 'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:8|max:60', 'role_id' => ['required', 'integer', 'in:'.implode(',', $roleIds ?: [0])],
        ]);
        abort_if(User::where('agency_id', $agency->id)->whereIn('role', ['owner', 'member'])->count() >= $agency->max_members, 422, 'Team limit reached ('.$agency->max_members.'). Ask the platform admin to raise it.');
        $password = $v['password'] ?? Str::password(10, symbols: false);
        $m = User::create(['name' => $v['name'], 'email' => $v['email'], 'password' => $password, 'role' => 'member', 'agency_id' => $agency->id, 'role_id' => $v['role_id']]);

        return redirect('/team')->with('ok', $m->name.' was added')->with('creds', ['title' => 'Member added', 'agency' => $agency->name, 'email' => $m->email, 'password' => $password]);
    }

    public function edit(Request $r, User $user)
    {
        $me = $r->user();
        $this->member($r, $user);
        abort_unless($me->hasPerm('team.manage') || $me->id === $user->id, 403);

        return view('team.form', ['u' => $user, 'roles' => $this->assignable($r), 'agency' => $this->agencyOf($r)]);
    }

    public function update(Request $r, User $user)
    {
        $me = $r->user();
        $this->member($r, $user);
        $manage = $me->hasPerm('team.manage');
        abort_unless($manage || $me->id === $user->id, 403);
        $roleIds = $this->assignable($r)->pluck('id')->all();
        $v = $r->validate([
            'name' => 'required|string|max:80', 'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|max:60', 'role_id' => ['nullable', 'integer', 'in:'.implode(',', $roleIds ?: [0])],
        ]);
        $user->name = $v['name'];
        $user->email = $v['email'];
        if (! empty($v['password'])) {
            $user->password = $v['password'];
        }
        // only someone with team.manage may change a role, never an owner's and never their own
        if ($manage && $user->role === 'member' && $me->id !== $user->id && ! empty($v['role_id'])) {
            $user->role_id = $v['role_id'];
        }
        $user->save();

        $res = redirect('/team')->with('ok', $user->name.' was updated');
        if (! empty($v['password'])) {
            $res->with('creds', ['title' => 'Password changed', 'agency' => $this->agencyOf($r)->name, 'email' => $user->email, 'password' => $v['password']]);
        }

        return $res;
    }

    public function destroy(Request $r, User $user)
    {
        $me = $r->user();
        $this->member($r, $user);
        abort_unless($me->hasPerm('team.manage'), 403);
        abort_if($user->role === 'owner' || $user->id === $me->id, 403, 'That person cannot be removed.');
        $name = $user->name;
        $user->delete();

        return redirect('/team')->with('ok', $name.' was removed');
    }
}
