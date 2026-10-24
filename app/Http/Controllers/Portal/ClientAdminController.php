<?php

namespace App\Http\Controllers\Portal;

use App\Models\Account;
use App\Models\User;
use App\Support\Access;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Admin panel: add / remove clients, their logins, and which team members look after them. */
class ClientAdminController extends PortalController
{
    private function perm(Request $r, string $perm): User
    {
        $u = $r->user();
        abort_unless($u->isStaff() && $u->hasPerm($perm), 403, 'Your role does not allow that.');

        return $u;
    }

    public function index(Request $r)
    {
        $u = $r->user();
        abort_unless($u->isStaff(), 403);
        $q = trim((string) $r->query('q', ''));
        $status = in_array($r->query('status'), ['pending', 'nologin']) ? $r->query('status') : 'all';
        $teamId = (int) $r->query('team');
        [$sortKey, $dirRaw] = array_pad(explode(':', (string) $r->query('order', 'activity:desc')), 2, 'desc');
        $dir = $dirRaw === 'asc' ? 'asc' : 'desc';
        $per = in_array((int) $r->query('per_page'), [10, 25, 50]) ? (int) $r->query('per_page') : 10;

        $mine = fn () => Account::where('agency_id', $u->agency_id)
            ->when(! $u->seesAllClients(), fn ($x) => $x->whereIn('accounts.id', $u->assignedAccounts()->select('accounts.id')));
        $pendingSql = "(select count(*) from posts where posts.account_id = accounts.id and posts.status = 'pending')";
        $noLoginSql = "not exists (select 1 from account_user au join users cu on cu.id = au.user_id where au.account_id = accounts.id and cu.role = 'guest')";

        $query = $mine()->withActivity()
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%$q%")
                ->orWhereHas('clientUsers', fn ($c) => $c->where('users.email', 'like', "%$q%")->orWhere('users.name', 'like', "%$q%"))))
            ->when($status === 'pending', fn ($x) => $x->whereRaw("$pendingSql > 0"))
            ->when($status === 'nologin', fn ($x) => $x->whereRaw($noLoginSql))
            ->when($teamId, fn ($x) => $x->whereHas('team', fn ($t) => $t->where('users.id', $teamId)))
            ->when($r->filled('from'), fn ($x) => $x->whereDate('accounts.created_at', '>=', $r->query('from')))
            ->when($r->filled('to'), fn ($x) => $x->whereDate('accounts.created_at', '<=', $r->query('to')))
            ->with(['team', 'clientUsers'])
            ->withCount(['posts', 'posts as pending_count' => fn ($x) => $x->where('status', 'pending')]);

        match ($sortKey) {
            'name' => $query->orderBy('name', $dir),
            'created' => $query->orderBy('accounts.created_at', $dir),
            'pending' => $query->orderByRaw("$pendingSql $dir"),
            'posts' => $query->orderBy('posts_count', $dir),
            default => $query->orderBy('activity_at', $dir),
        };
        $accounts = $query->orderByDesc('accounts.id')->paginate($per)->withQueryString();

        return view('portal.clients.index', [
            'accounts' => $accounts, 'agency' => $u->agency, 'canCreate' => $u->hasPerm('workspaces.create'),
            'canEdit' => $u->hasPerm('workspaces.edit'), 'canDelete' => $u->hasPerm('workspaces.delete'),
            'total' => Account::where('agency_id', $u->agency_id)->count(),
            'tabs' => [
                'all' => $mine()->count(),
                'pending' => $mine()->whereRaw("$pendingSql > 0")->count(),
                'nologin' => $mine()->whereRaw($noLoginSql)->count(),
            ],
            'staff' => $this->staffList($r), 'view' => $r->query('view', 'table') === 'cards' ? 'cards' : 'table',
        ]);
    }

    public function create(Request $r)
    {
        $this->perm($r, 'workspaces.create');

        return view('portal.clients.form', ['account' => null, 'staff' => $this->staffList($r)]);
    }

    public function store(Request $r)
    {
        $u = $this->perm($r, 'workspaces.create');
        $v = $r->validate([
            'name' => 'required|string|max:80', 'phone' => 'nullable|string|max:30', 'billing_address' => 'nullable|string|max:500', 'tax_id' => 'nullable|string|max:60', 'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:'.config('portal.max_image_kb'),
            'currency' => ['required', Rule::in(array_keys(config('currencies')))], 'client_name' => 'required|string|max:80', 'client_email' => 'required|email|unique:users,email',
            'client_password' => 'nullable|string|min:8|max:60', 'team' => 'array', 'team.*' => 'integer',
        ]);
        abort_if(Account::where('agency_id', $u->agency_id)->count() >= $u->agency->max_workspaces, 422, 'Client limit reached ('.$u->agency->max_workspaces.'). Ask the platform admin to raise it.');

        $account = Account::create([
            'agency_id' => $u->agency_id, 'name' => $v['name'], 'phone' => $v['phone'] ?? null, 'billing_address' => $v['billing_address'] ?? null, 'tax_id' => $v['tax_id'] ?? null, 'currency' => $v['currency'], 'token' => Str::random(24), 'share_enabled' => false, 'docs' => [],
            'position' => (Account::where('agency_id', $u->agency_id)->max('position') ?? 0) + 1,
        ]);
        DemoData::blank($account);
        if ($r->hasFile('logo')) {
            $account->update(['logo' => $this->keep($r, 'logo', 'logos', $account->id)]);
        }
        // a creator who only sees assigned clients must keep access to the one they just made
        $this->syncTeam($account, $u, array_merge($v['team'] ?? [], $u->seesAllClients() ? [] : [$u->id]));
        $password = $v['client_password'] ?? Str::password(10, symbols: false);
        $client = User::create(['name' => $v['client_name'], 'email' => $v['client_email'], 'password' => $password, 'role' => 'guest']);
        $account->people()->attach($client->id, ['perm' => 'comment']);

        return redirect('/portal/clients/'.$account->id)->with('ok', $account->name.' was added')
            ->with('creds', ['title' => 'Client added', 'agency' => $account->name, 'email' => $client->email, 'password' => $password]);
    }

    public function edit(Request $r, Account $account)
    {
        $this->perm($r, 'workspaces.edit');
        $this->reach($r, $account, 'edit');

        return view('portal.clients.form', ['account' => $account->load(['team', 'clientUsers']), 'staff' => $this->staffList($r)]);
    }

    public function update(Request $r, Account $account)
    {
        $u = $this->perm($r, 'workspaces.edit');
        $this->reach($r, $account, 'edit');
        $v = $r->validate([
            'name' => 'required|string|max:80', 'phone' => 'nullable|string|max:30', 'billing_address' => 'nullable|string|max:500', 'tax_id' => 'nullable|string|max:60', 'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:'.config('portal.max_image_kb'),
            'currency' => ['nullable', Rule::in(array_keys(config('currencies')))], 'remove_logo' => 'nullable|boolean', 'team' => 'array', 'team.*' => 'integer',
        ]);
        $account->name = $v['name'];
        $account->phone = $v['phone'] ?? null;
        $account->billing_address = $v['billing_address'] ?? null;
        $account->tax_id = $v['tax_id'] ?? null;
        // the billing currency is fixed once the first invoice exists
        if (! empty($v['currency']) && ! $account->invoices()->exists()) {
            $account->currency = $v['currency'];
        }
        if ($r->hasFile('logo') || $r->boolean('remove_logo')) {
            if ($account->logo) {
                Storage::disk('local')->delete($account->logo);
            }
            $account->logo = $r->hasFile('logo') ? $this->keep($r, 'logo', 'logos', $account->id) : null;
        }
        $account->save();
        if ($u->hasPerm('team.manage') || $u->role === 'owner') {
            $this->syncTeam($account, $u, $v['team'] ?? []);
        }

        return redirect('/portal/clients/'.$account->id)->with('ok', 'Client saved');
    }

    public function destroy(Request $r, Account $account)
    {
        $this->perm($r, 'workspaces.delete');
        $this->reach($r, $account, 'edit');
        Storage::disk('local')->deleteDirectory('posts/'.$account->id);
        Storage::disk('local')->deleteDirectory('files/'.$account->id);
        Storage::disk('local')->deleteDirectory('invoices/'.$account->id);
        Storage::disk('local')->deleteDirectory('reports/'.$account->id);
        Storage::disk('local')->deleteDirectory('logos/'.$account->id);
        $account->clientUsers()->get()->each->delete();
        $account->delete();

        return redirect('/portal/clients')->with('ok', 'Client removed');
    }

    // ── client logins ────────────────────────────────────────

    public function addUser(Request $r, Account $account)
    {
        $this->perm($r, 'sharing.manage');
        $this->reach($r, $account, 'edit');
        $v = $r->validate(['name' => 'required|string|max:80', 'email' => 'required|email|unique:users,email', 'password' => 'nullable|string|min:8|max:60']);
        $password = $v['password'] ?? Str::password(10, symbols: false);
        $user = User::create(['name' => $v['name'], 'email' => $v['email'], 'password' => $password, 'role' => 'guest']);
        $account->people()->attach($user->id, ['perm' => 'comment']);

        return back()->with('ok', $user->name.' can now sign in')
            ->with('creds', ['title' => 'Client login created', 'agency' => $account->name, 'email' => $user->email, 'password' => $password]);
    }

    public function resetUser(Request $r, Account $account, User $user)
    {
        $this->perm($r, 'sharing.manage');
        $this->reach($r, $account, 'edit');
        abort_unless($account->clientUsers()->where('users.id', $user->id)->exists(), 404);
        $password = Str::password(10, symbols: false);
        $user->update(['password' => $password]);

        return back()->with('creds', ['title' => 'New password set', 'agency' => $account->name, 'email' => $user->email, 'password' => $password]);
    }

    public function removeUser(Request $r, Account $account, User $user)
    {
        $this->perm($r, 'sharing.manage');
        $this->reach($r, $account, 'edit');
        abort_unless($account->clientUsers()->where('users.id', $user->id)->exists(), 404);
        $user->delete();

        return back()->with('ok', 'Login removed');
    }

    // ── helpers ──────────────────────────────────────────────

    /** Team members who can be assigned to a client. */
    private function staffList(Request $r)
    {
        return User::where('agency_id', $r->user()->agency_id)->whereIn('role', ['owner', 'member'])->orderBy('name')->get();
    }

    private function syncTeam(Account $account, User $by, array $ids): void
    {
        $valid = User::where('agency_id', $by->agency_id)->whereIn('role', ['owner', 'member'])->whereIn('id', $ids)->pluck('id');
        $account->team()->sync($valid);
    }
}
