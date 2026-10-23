<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Agency;
use App\Models\DocTemplate;
use App\Models\User;
use App\Support\Access;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgencyController extends Controller
{
    /** Agency staff (owner or member) who hold the given permission. */
    private function need(Request $r, string $perm): User
    {
        $u = $r->user();
        abort_unless($u && in_array($u->role, ['owner', 'member']) && $u->hasPerm($perm), 403, 'Your role does not allow that.');

        return $u;
    }

    // ── agency profile ───────────────────────────────────────
    public function update(Request $r)
    {
        $u = $this->need($r, 'settings.manage');
        $v = $r->validate(['name' => 'sometimes|string|max:80', 'brand_color' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/']]);
        $u->agency->update($v);

        return ['name' => $u->agency->name, 'color' => $u->agency->brand_color];
    }

    public function uploadLogo(Request $r)
    {
        $agency = $this->need($r, 'settings.manage')->agency;
        $r->validate(['logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:'.config('portal.max_image_kb')]);
        if ($agency->logo) {
            Storage::disk('local')->delete($agency->logo);
        }
        $agency->update(['logo' => $r->file('logo')->store('agency-logos/'.$agency->id, 'local')]);

        return ['logo' => $agency->logoUrl()];
    }

    public function removeLogo(Request $r)
    {
        $agency = $this->need($r, 'settings.manage')->agency;
        if ($agency->logo) {
            Storage::disk('local')->delete($agency->logo);
            $agency->update(['logo' => null]);
        }

        return ['logo' => null];
    }

    /** Streams the agency logo to its own team, its clients and the platform admin. */
    public function logo(Request $r, Agency $agency)
    {
        $u = $r->user();
        $ok = $u->isSuper() || $u->agency_id === $agency->id || $u->sharedAccounts()->where('accounts.agency_id', $agency->id)->exists();
        abort_unless($ok && $agency->logo && Storage::disk('local')->exists($agency->logo), 404);

        return Storage::disk('local')->response($agency->logo, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=3600']);
    }

    // ── workspaces ───────────────────────────────────────────
    public function storeWorkspace(Request $r)
    {
        $u = $this->need($r, 'workspaces.create');
        $v = $r->validate(['name' => 'required|string|max:80', 'sample' => 'nullable|boolean']);
        abort_if(Account::where('agency_id', $u->agency_id)->count() >= $u->agency->max_workspaces, 422, 'Workspace limit reached ('.$u->agency->max_workspaces.'). Ask the platform admin to raise it.');
        $account = Account::create([
            'agency_id' => $u->agency_id, 'name' => $v['name'], 'token' => Str::random(24),
            'position' => (Account::where('agency_id', $u->agency_id)->max('position') ?? 0) + 1, 'docs' => [],
        ]);
        if (! empty($v['sample'])) {
            DemoData::seed($account);
        } else {
            DemoData::blank($account);
        }

        return ['id' => $account->id, 'name' => $account->name, 'perm' => 'edit'];
    }

    public function updateWorkspace(Request $r, Account $account)
    {
        $this->need($r, 'workspaces.edit');
        Access::need($r, $account);
        $account->update($r->validate(['name' => 'required|string|max:80']));

        return ['id' => $account->id, 'name' => $account->name];
    }

    public function destroyWorkspace(Request $r, Account $account)
    {
        $this->need($r, 'workspaces.delete');
        abort_unless($r->user()->agency_id === $account->agency_id, 403);
        $account->delete();

        return ['ok' => true];
    }

    // ── sharing ──────────────────────────────────────────────
    public function updateShare(Request $r, Account $account)
    {
        $this->need($r, 'sharing.manage');
        Access::need($r, $account);
        $v = $r->validate(['enabled' => 'sometimes|boolean', 'perm' => 'sometimes|in:comment,view', 'regenerate' => 'sometimes|boolean']);
        $upd = [];
        if (isset($v['enabled'])) {
            $upd['share_enabled'] = $v['enabled'];
        }
        if (isset($v['perm'])) {
            $upd['share_perm'] = $v['perm'];
        }
        if (! empty($v['regenerate'])) {
            $upd['token'] = Str::random(24);
        }
        $account->update($upd);

        return ['enabled' => $account->share_enabled, 'perm' => $account->share_perm, 'token' => $account->token];
    }

    public function people(Request $r, Account $account)
    {
        $this->need($r, 'sharing.manage');
        Access::need($r, $account);

        return $account->people()->get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'email' => $p->email, 'perm' => $p->pivot->perm])->values();
    }

    public function addPerson(Request $r, Account $account)
    {
        $this->need($r, 'sharing.manage');
        Access::need($r, $account);
        $v = $r->validate(['name' => 'required|string|max:80', 'email' => 'required|email', 'perm' => 'required|in:edit,comment']);
        $user = User::where('email', $v['email'])->first();
        $password = null;
        if ($user) {
            abort_if($user->role !== 'guest', 422, 'That email already belongs to another account.');
        } else {
            $password = Str::password(10, symbols: false);
            $user = User::create(['name' => $v['name'], 'email' => $v['email'], 'password' => $password, 'role' => 'guest']);
        }
        $account->people()->syncWithoutDetaching([$user->id => ['perm' => $v['perm']]]);

        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'perm' => $v['perm'], 'password' => $password];
    }

    public function removePerson(Request $r, Account $account, User $user)
    {
        $this->need($r, 'sharing.manage');
        Access::need($r, $account);
        $account->people()->detach($user->id);

        return ['ok' => true];
    }

    // ── document templates ───────────────────────────────────
    public function templates(Request $r)
    {
        $u = $r->user();
        abort_unless($u && in_array($u->role, ['owner', 'member']) && ($u->hasPerm('documents.export') || $u->hasPerm('templates.manage')), 403, 'Your role does not allow that.');

        return $u->agency->templates()->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'config' => $t->config])->values();
    }

    private function cfg(Request $r): array
    {
        $v = $r->validate([
            'name' => 'required|string|max:60',
            'config.layout' => 'required|in:normal,graph,detailed',
            'config.orientation' => 'required|in:portrait,landscape',
            'config.paper' => 'required|in:a4,letter',
            'config.sections' => 'array',
            'config.sections.*' => 'in:strategy,kpis,charts,tabs,calendar,approval',
            'config.charts' => 'boolean',
            'config.show_notes' => 'boolean',
            'config.header' => 'nullable|string|max:120',
            'config.footer' => 'nullable|string|max:160',
        ]);

        return $v;
    }

    public function storeTemplate(Request $r)
    {
        $u = $this->need($r, 'templates.manage');
        $v = $this->cfg($r);
        $t = $u->agency->templates()->create(['name' => $v['name'], 'config' => $v['config']]);

        return ['id' => $t->id, 'name' => $t->name, 'config' => $t->config];
    }

    public function updateTemplate(Request $r, DocTemplate $template)
    {
        $u = $this->need($r, 'templates.manage');
        abort_unless($template->agency_id === $u->agency_id, 403);
        $v = $this->cfg($r);
        $template->update(['name' => $v['name'], 'config' => $v['config']]);

        return ['id' => $template->id, 'name' => $template->name, 'config' => $template->config];
    }

    public function destroyTemplate(Request $r, DocTemplate $template)
    {
        $u = $this->need($r, 'templates.manage');
        abort_unless($template->agency_id === $u->agency_id, 403);
        $template->delete();

        return ['ok' => true];
    }
}
