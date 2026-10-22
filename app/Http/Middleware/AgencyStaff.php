<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Owners and team members of an active agency only (no guests, no platform admin). */
class AgencyStaff
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        abort_unless($u && in_array($u->role, ['owner', 'member']) && $u->agency?->status === 'active', 403);

        return $next($request);
    }
}
