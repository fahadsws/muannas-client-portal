<?php

namespace App\Support;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;

/** Central permission rules: 'edit' > 'comment' > 'view' > null. */
class Access
{
    public const LEVEL = ['view' => 1, 'comment' => 2, 'edit' => 3];

    public static function perm(Request $r, Account $account): ?string
    {
        $user = $r->user();
        if ($user) {
            if ($user->isSuper()) {
                return 'edit';
            }
            if ($user->agency_id && $user->agency?->status !== 'active') {
                return null;
            }
            if ($user->agency_id === $account->agency_id && in_array($user->role, ['owner', 'member'])) {
                return $user->hasPerm('content.edit') ? 'edit' : 'comment';
            }
            $pivot = $user->sharedAccounts()->where('accounts.id', $account->id)->first();
            if ($pivot) {
                return $pivot->pivot->perm;
            }
        }

        $token = $r->header('X-Share-Token') ?: $r->query('token');
        if ($token && $account->share_enabled && hash_equals($account->token, (string) $token)) {
            return $account->agency?->status === 'active' ? $account->share_perm : null;
        }

        return null;
    }

    /** Abort unless the caller has at least $need on the account. Returns the actual permission. */
    public static function need(Request $r, Account $account, string $need = 'edit'): string
    {
        $p = self::perm($r, $account);
        abort_unless($p && self::LEVEL[$p] >= self::LEVEL[$need], 403, 'You do not have access to do that.');

        return $p;
    }

    /** All workspaces a user can open, with their permission. */
    public static function accountsFor(User $user)
    {
        if ($user->isSuper()) {
            return collect();
        }
        if (in_array($user->role, ['owner', 'member'])) {
            return Account::where('agency_id', $user->agency_id)->orderBy('position')->orderBy('id')->get()
                ->each(fn ($a) => $a->perm = 'edit');
        }

        return $user->sharedAccounts()->orderBy('accounts.id')->get()->each(fn ($a) => $a->perm = $a->pivot->perm);
    }
}
