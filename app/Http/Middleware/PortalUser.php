<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Anyone who belongs to a client or to the agency team. The platform super admin has their own console. */
class PortalUser
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u?->isSuper()) {
            return redirect('/admin');
        }
        abort_unless($u && ($u->isStaff() || $u->isClient()), 403);

        return $next($request);
    }
}
