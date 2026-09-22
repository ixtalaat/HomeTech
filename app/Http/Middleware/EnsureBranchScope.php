<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchScope
{
    /**
     * Confine branch-assigned managers to their branch area.
     *
     * Managers are one role: assignment decides scope. A manager running an
     * active branch works inside /branch/* only; unassigned managers keep
     * the legacy global staff access. Admins always pass through.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isBranchScoped()) {
            return redirect()->route('branch.dashboard');
        }

        return $next($request);
    }
}
