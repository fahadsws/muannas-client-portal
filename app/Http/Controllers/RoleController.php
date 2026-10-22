<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Agency roles & permissions: full CRUD with a permission matrix. */
class RoleController extends Controller
{
    private function gate(Request $r): void
    {
        abort_unless($r->user()->hasPerm('roles.manage'), 403, 'Your role does not allow managing roles.');
    }

    private function mine(Request $r, Role $role): Role
    {
        abort_unless($role->agency_id === $r->user()->agency_id, 404);

        return $role;
    }

    public function index(Request $r)
    {
        $this->gate($r);
        $roles = Role::where('agency_id', $r->user()->agency_id)->withCount('users')->orderBy('id')->get();

        return view('roles.index', ['roles' => $roles, 'total' => count(Permissions::keys())]);
    }

    public function create(Request $r)
    {
        $this->gate($r);

        return view('roles.form', ['role' => null, 'groups' => Permissions::GROUPS]);
    }

    private function rules(Request $r, ?Role $role = null): array
    {
        $agencyId = $r->user()->agency_id;

        return [
            'name' => ['required', 'string', 'max:40', Rule::unique('roles')->where('agency_id', $agencyId)->ignore($role?->id)],
            'description' => 'nullable|string|max:160',
            'permissions' => 'array', 'permissions.*' => ['string', Rule::in(Permissions::keys())],
        ];
    }

    public function store(Request $r)
    {
        $this->gate($r);
        $v = $r->validate($this->rules($r));
        $role = Role::create(['agency_id' => $r->user()->agency_id, 'name' => $v['name'], 'description' => $v['description'] ?? null, 'permissions' => array_values($v['permissions'] ?? []), 'is_system' => false]);

        return redirect('/roles')->with('ok', 'Role "'.$role->name.'" created');
    }

    public function edit(Request $r, Role $role)
    {
        $this->gate($r);
        $this->mine($r, $role);

        return view('roles.form', ['role' => $role, 'groups' => Permissions::GROUPS]);
    }

    public function update(Request $r, Role $role)
    {
        $this->gate($r);
        $this->mine($r, $role);
        abort_if($role->isOwnerRole(), 403, 'The Owner role cannot be changed.');
        $v = $r->validate($this->rules($r, $role));
        $upd = ['description' => $v['description'] ?? null, 'permissions' => array_values($v['permissions'] ?? [])];
        if (! $role->is_system) {
            $upd['name'] = $v['name'];
        }
        $role->update($upd);

        return redirect('/roles')->with('ok', 'Role "'.$role->name.'" saved');
    }

    public function destroy(Request $r, Role $role)
    {
        $this->gate($r);
        $this->mine($r, $role);
        abort_if($role->is_system, 403, 'Built-in roles cannot be deleted.');
        $n = $role->users()->count();
        if ($n > 0) {
            return back()->with('err', "Reassign the {$n} ".($n === 1 ? 'person' : 'people').' using "'.$role->name.'" before deleting it.');
        }
        $name = $role->name;
        $role->delete();

        return redirect('/roles')->with('ok', 'Role "'.$name.'" deleted');
    }
}
