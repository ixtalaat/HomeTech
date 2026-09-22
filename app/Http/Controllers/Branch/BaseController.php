<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Branch;

class BaseController extends Controller
{
    /**
     * Resolve the active branch managed by the authenticated user.
     *
     * Branch managers outside an active assignment see 404, so cross-branch
     * data stays unreachable instead of forbidden-but-visible.
     */
    protected function managedBranch(): Branch
    {
        $branch = auth()->user()?->managedBranch;

        abort_unless($branch !== null && $branch->is_active, 404);

        return $branch;
    }
}
