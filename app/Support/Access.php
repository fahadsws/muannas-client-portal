<?php

namespace App\Support;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;

/** Central permission rules: 'edit' > 'comment' > 'view' > null. */
class Access
{
    public const LEVEL = ['view' => 1, 'comment' => 2, 'edit' => 3];

    /**
     * What the caller may do on one client workspace.
     * Owners: everything. Team members: the clients they are assigned to (or all, with "clients.all").
     * Clients: only their own workspace, and only through the portal ($portal = true).
     * The internal planning board (legacy, $portal = false) never opens for client logins; it may still honour an explicit public share link.
     */
    public static function perm(Request $r, Account $account, bool $portal = false): ?string
    {
        $user = $r->user();
        if ($user) {
            if ($user->isSuper()) {
                return 'edit';
            }
            // a suspended agency locks out its team and all of its clients
            if (($user->agency_id && $user->agency?->status !== 'active') || $account->agency?->status !== 'active') {
                return null;
            }
            if ($user->agency_id === $account->agency_id && $user->isStaff()) {
                if ($user->seesAllClients() || $user->assignedAccounts()->where('accounts.id', $account->id)->exists()) {
                    return $user->hasPerm('content.edit') ? 'edit' : 'comment';
                }

                return null;
            }
            if ($portal) {
                $pivot = $user->sharedAccounts()->where('accounts.id', $account->id)->first();
                if ($pivot) {
                    return $pivot->pivot->perm;
                }
            }
        }

        if ($portal) {
            return null;
        }
        $token = $r->header('X-Share-Token') ?: $r->query('token');
        if ($token && $account->share_enabled && hash_equals($account->token, (string) $token)) {
            return $account->agency?->status === 'active' ? $account->share_perm : null;
        }

        return null;
    }

    /** Abort unless the caller has at least $need on the account. Returns the actual permission. */
    public static function need(Request $r, Account $account, string $need = 'edit', bool $portal = false): string
    {
        $p = self::perm($r, $account, $portal);
        abort_unless($p && self::LEVEL[$p] >= self::LEVEL[$need], 403, 'You do not have access to do that.');

        return $p;
    }

    /** All workspaces a user can open, with their permission. */
    public static function accountsFor(User $user)
    {
        if ($user->isSuper()) {
            return collect();
        }
        if ($user->isStaff()) {
            $perm = $user->hasPerm('content.edit') ? 'edit' : 'comment';

            // the client with the newest update comes first
            return Account::withActivity()->where('agency_id', $user->agency_id)
                ->when(! $user->seesAllClients(), fn ($q) => $q->whereIn('accounts.id', $user->assignedAccounts()->select('accounts.id')))
                ->orderByDesc('activity_at')->orderByDesc('accounts.id')->get()
                ->each(fn ($a) => $a->perm = $perm);
        }

        return $user->sharedAccounts()->orderBy('accounts.id')->get()->each(fn ($a) => $a->perm = $a->pivot->perm);
    }
}
