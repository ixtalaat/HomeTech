<?php

namespace App\Http\Controllers\Branch;

use App\Services\BranchService;
use Illuminate\View\View;

class DashboardController extends BaseController
{
    public function __construct(private BranchService $branches) {}

    /**
     * Display the branch performance overview.
     */
    public function index(): View
    {
        $branch = $this->managedBranch();

        return view('branch.dashboard', $this->branches->overview($branch));
    }
}
