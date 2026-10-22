<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Agency;
use App\Models\DocTemplate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperController extends Controller
{
    // ── shared helpers ───────────────────────────────────────

    /** item counts per status for a set of agency ids: [agency_id => [status => n]] */
    public static function itemCounts(?array $agencyIds = null): array
    {
        $q = DB::table('items')->join('tabs', 'tabs.id', '=', 'items.tab_id')->join('accounts', 'accounts.id', '=', 'tabs.account_id')
            ->where(function ($w) { $w->whereNull('tabs.meta')->orWhere('tabs.meta', 'not like', '%"overview":true%'); })
            ->select('accounts.agency_id', 'items.status', DB::raw('count(*) as n'))->groupBy('accounts.agency_id', 'items.status');
        if ($agencyIds) {
            $q->whereIn('accounts.agency_id', $agencyIds);
        }
        $out = [];
        foreach ($q->get() as $r) {
            $out[$r->agency_id][$r->status] = (int) $r->n;
        }

        return $out;
    }

    public static function row(Agency $a, ?array $counts = null): array
    {
        $owner = $a->users->firstWhere('role', 'owner');
        $c = $counts ?? [];
        $items = array_sum($c);
        $fin = ($c['done'] ?? 0) + ($c['live'] ?? 0);

        return [
            'id' => $a->id, 'name' => $a->name, 'status' => $a->status, 'color' => $a->brand_color,
            'owner' => $owner?->name, 'email' => $owner?->email,
            'workspaces' => $a->accounts_count ?? $a->accounts()->count(), 'members' => $a->users_count ?? $a->users()->count(),
            'max_workspaces' => $a->max_workspaces, 'max_members' => $a->max_members,
            'items' => $items, 'pct' => $items ? round($fin / $items * 100) : 0,
            'created' => $a->created_at->format('d M Y'), 'created_iso' => $a->created_at->toDateString(),
        ];
    }

    public function filtered(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        $status = $r->query('status', 'all');
        [$sortKey, $dirRaw] = array_pad(explode(':', (string) $r->query('order', 'created:desc')), 2, 'desc');
        $dir = $dirRaw === 'asc' ? 'asc' : 'desc';
        $wsCount = '(select count(*) from accounts where accounts.agency_id = agencies.id)';
        $teamCount = "(select count(*) from users where users.agency_id = agencies.id and users.role in ('owner','member'))";
        $itemCount = "(select count(*) from items join tabs on tabs.id = items.tab_id join accounts on accounts.id = tabs.account_id where accounts.agency_id = agencies.id)";
        $ownerName = "(select users.name from users where users.agency_id = agencies.id and users.role = 'owner' limit 1)";

        $query = Agency::query()->with(['users' => fn ($u) => $u->where('role', 'owner')])->withCount(['accounts', 'users'])
            ->when($q, fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhereHas('users', fn ($u) => $u->where('role', 'owner')->where(fn ($o) => $o->where('email', 'like', "%$q%")->orWhere('name', 'like', "%$q%")))))
            ->when(in_array($status, ['active', 'suspended']), fn ($x) => $x->where('status', $status))
            ->when($r->filled('from'), fn ($x) => $x->whereDate('created_at', '>=', $r->query('from')))
            ->when($r->filled('to'), fn ($x) => $x->whereDate('created_at', '<=', $r->query('to')));

        match ($r->query('usage', 'any')) {
            'limit' => $query->whereRaw("($wsCount >= max_workspaces or $teamCount >= max_members)"),
            'none' => $query->whereRaw("$wsCount = 0"),
            'some' => $query->whereRaw("$wsCount > 0"),
            default => null,
        };

        match ($sortKey) {
            'name' => $query->orderBy('name', $dir),
            'owner' => $query->orderByRaw("$ownerName $dir"),
            'workspaces' => $query->orderByRaw("$wsCount $dir"),
            'members' => $query->orderByRaw("$teamCount $dir"),
            'items' => $query->orderByRaw("$itemCount $dir"),
            'status' => $query->orderBy('status', $dir),
            default => $query->orderBy('id', $dir),
        };

        return $query->orderBy('id', 'desc');
    }

    // ── dashboard ────────────────────────────────────────────
    public function dashboardData(): array
    {
        $agencies = Agency::withCount(['accounts', 'users'])->with(['users' => fn ($u) => $u->where('role', 'owner')])->get();
        $counts = self::itemCounts();
        $status = ['todo' => 0, 'ready' => 0, 'done' => 0, 'live' => 0];
        foreach ($counts as $c) {
            foreach ($c as $k => $v) {
                $status[$k] = ($status[$k] ?? 0) + $v;
            }
        }
        $items = array_sum($status);

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = now()->startOfMonth()->subMonths($i);
            $months[$d->format('Y-m')] = ['label' => $d->format('M'), 'n' => 0, 'from' => $d->toDateString(), 'to' => $d->copy()->endOfMonth()->toDateString()];
        }
        foreach ($agencies as $a) {
            $k = $a->created_at->format('Y-m');
            if (isset($months[$k])) {
                $months[$k]['n']++;
            }
        }

        $rows = $agencies->map(fn ($a) => self::row($a, $counts[$a->id] ?? []));

        return [
            'totals' => [
                'agencies' => $agencies->count(), 'active' => $agencies->where('status', 'active')->count(), 'suspended' => $agencies->where('status', 'suspended')->count(),
                'workspaces' => Account::count(), 'users' => User::where('role', '!=', 'super')->count(), 'guests' => User::where('role', 'guest')->count(),
                'items' => $items, 'completion' => $items ? round(($status['done'] + $status['live']) / $items * 100) : 0,
            ],
            'status' => $status,
            'months' => array_values($months),
            'top' => $rows->sortByDesc('items')->take(5)->values(),
            'recent' => $rows->sortByDesc('id')->take(5)->values(),
            'limits' => $rows->filter(fn ($r) => $r['workspaces'] >= $r['max_workspaces'] || $r['members'] >= $r['max_members'])->values(),
        ];
    }

    public function showData(Agency $agency): array
    {
        $agency->loadCount(['accounts', 'users'])->load(['users' => fn ($u) => $u->orderByRaw("role='owner' desc")->orderBy('id')]);
        $counts = self::itemCounts([$agency->id])[$agency->id] ?? [];
        $row = self::row($agency, $counts);
        $row['notes'] = $agency->notes;
        $row['counts'] = ['todo' => $counts['todo'] ?? 0, 'ready' => $counts['ready'] ?? 0, 'done' => $counts['done'] ?? 0, 'live' => $counts['live'] ?? 0];

        $perAccount = DB::table('items')->join('tabs', 'tabs.id', '=', 'items.tab_id')->join('accounts', 'accounts.id', '=', 'tabs.account_id')
            ->where('accounts.agency_id', $agency->id)->where(function ($w) { $w->whereNull('tabs.meta')->orWhere('tabs.meta', 'not like', '%"overview":true%'); })
            ->select('accounts.id', 'items.status', DB::raw('count(*) as n'))->groupBy('accounts.id', 'items.status')->get()->groupBy('id');

        return [
            'agency' => $row,
            'workspaces' => $agency->accounts()->get()->map(function ($a) use ($perAccount) {
                $c = $perAccount[$a->id] ?? collect();
                $tot = $c->sum('n');
                $fin = $c->whereIn('status', ['done', 'live'])->sum('n');

                return ['id' => $a->id, 'name' => $a->name, 'items' => $tot, 'pct' => $tot ? round($fin / $tot * 100) : 0, 'shared' => (bool) $a->share_enabled, 'created' => $a->created_at->format('d M Y')];
            })->values()->all(),
            'team' => $agency->users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role, 'created' => $u->created_at->format('d M Y')])->values()->all(),
        ];
    }

    // ── pages (server-rendered) ──────────────────────────────

    public function dashboard()
    {
        return view('admin.dashboard', ['d' => $this->dashboardData()]);
    }

    public function agencies(Request $r)
    {
        $per = in_array((int) $r->query('per_page'), [10, 25, 50, 100]) ? (int) $r->query('per_page') : 10;
        $p = $this->filtered($r)->paginate($per)->withQueryString();
        $counts = self::itemCounts($p->pluck('id')->all());

        return view('admin.agencies', [
            'rows' => $p->getCollection()->map(fn ($a) => self::row($a, $counts[$a->id] ?? []))->all(),
            'p' => $p, 'per' => $per,
            'tabs' => ['all' => Agency::count(), 'active' => Agency::where('status', 'active')->count(), 'suspended' => Agency::where('status', 'suspended')->count()],
            'view' => $r->query('view', 'table') === 'cards' ? 'cards' : 'table',
        ]);
    }

    public function create()
    {
        return view('admin.agency-create', ['def' => Setting::all_()]);
    }

    public function store(Request $r)
    {
        $defaults = Setting::all_();
        $v = $r->validate([
            'name' => 'required|string|max:80', 'owner_name' => 'required|string|max:80',
            'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:8|max:60',
            'max_workspaces' => 'nullable|integer|min:1|max:1000', 'max_members' => 'nullable|integer|min:1|max:1000',
        ]);
        $agency = Agency::create(['name' => $v['name'], 'max_workspaces' => $v['max_workspaces'] ?? $defaults['default_max_workspaces'], 'max_members' => $v['max_members'] ?? $defaults['default_max_members']]);
        User::create(['name' => $v['owner_name'], 'email' => $v['email'], 'password' => $v['password'], 'role' => 'owner', 'agency_id' => $agency->id]);
        foreach (DocTemplate::defaults() as $t) {
            $agency->templates()->create($t);
        }
        \App\Models\Role::ensureDefaults($agency);

        return redirect('/admin/agencies/'.$agency->id)->with('creds', ['title' => 'Agency created', 'agency' => $agency->name, 'email' => $v['email'], 'password' => $v['password']]);
    }

    public function show(Request $r, Agency $agency)
    {
        $tab = in_array($r->query('tab'), ['workspaces', 'team', 'settings']) ? $r->query('tab') : 'overview';

        return view('admin.agency', ['d' => $this->showData($agency), 'tab' => $tab]);
    }

    public function update(Request $r, Agency $agency)
    {
        $v = $r->validate([
            'name' => 'sometimes|string|max:80', 'status' => 'sometimes|in:active,suspended',
            'max_workspaces' => 'sometimes|integer|min:1|max:1000', 'max_members' => 'sometimes|integer|min:1|max:1000', 'notes' => 'sometimes|nullable|string|max:2000',
        ]);
        $agency->update($v);

        return back()->with('ok', isset($v['status']) && count($v) === 1 ? ($v['status'] === 'active' ? 'Agency activated' : 'Agency suspended') : 'Settings saved');
    }

    public function updateOwner(Request $r, Agency $agency)
    {
        $owner = $agency->users()->where('role', 'owner')->firstOrFail();
        $v = $r->validate(['owner_name' => 'required|string|max:80', 'email' => 'required|email|unique:users,email,'.$owner->id]);
        $owner->update(['name' => $v['owner_name'], 'email' => $v['email']]);

        return back()->with('ok', 'Owner updated');
    }

    public function resetPassword(Agency $agency)
    {
        $owner = $agency->users()->where('role', 'owner')->firstOrFail();
        $pass = Str::password(12, symbols: false);
        $owner->update(['password' => $pass]);

        return back()->with('creds', ['title' => 'New password', 'agency' => $agency->name, 'email' => $owner->email, 'password' => $pass]);
    }

    public function destroy(Agency $agency)
    {
        $agency->delete();

        return redirect('/admin/agencies')->with('ok', 'Agency deleted');
    }

    public function impersonate(Request $r, Agency $agency)
    {
        $owner = $agency->users()->where('role', 'owner')->firstOrFail();
        $r->session()->put('impersonator', Auth::id());
        Auth::login($owner);

        return redirect('/board');
    }

    public function reports(Request $r)
    {
        return view('admin.reports', ['types' => SuperReportController::TYPES, 'agencies' => Agency::orderBy('name')->get(['id', 'name']), 'st' => Setting::all_()]);
    }

    // ── platform settings ────────────────────────────────────
    public function settings(Request $r)
    {
        $sec = in_array($r->query('section'), ['reports', 'account']) ? $r->query('section') : 'platform';

        return view('admin.settings', ['st' => Setting::all_(), 'sec' => $sec]);
    }

    public function saveSettings(Request $r)
    {
        $group = $r->input('group', 'platform');
        $rules = $group === 'reports'
            ? ['report_header' => 'nullable|string|max:120', 'report_footer' => 'nullable|string|max:160', 'report_paper' => 'required|in:a4,letter']
            : ['brand' => 'required|string|max:40', 'tagline' => 'required|string|max:60', 'default_max_workspaces' => 'required|integer|min:1|max:1000', 'default_max_members' => 'required|integer|min:1|max:1000'];
        $v = $r->validate($rules);
        if ($group === 'reports') {
            $v['report_header'] = $v['report_header'] ?? '';
            $v['report_footer'] = $v['report_footer'] ?? '';
        }
        Setting::put($v);

        return redirect('/admin/settings?section='.$group)->with('ok', 'Settings saved');
    }

    public function saveAdmin(Request $r)
    {
        $u = $r->user();
        $v = $r->validate(['name' => 'required|string|max:80', 'email' => 'required|email|unique:users,email,'.$u->id, 'password' => 'nullable|string|min:8|max:60']);
        $u->name = $v['name'];
        $u->email = $v['email'];
        if (! empty($v['password'])) {
            $u->password = $v['password'];
        }
        $u->save();

        return redirect('/admin/settings?section=account')->with('ok', 'Account saved');
    }
}
