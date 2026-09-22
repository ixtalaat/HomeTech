<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\ReportingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private ReportingService $reports) {}

    /**
     * Display the role-appropriate overview.
     *
     * Staff land on the operational dashboard; technicians and
     * customers get their personal overviews.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isBranchScoped()) {
            return redirect()->route('branch.dashboard');
        }

        if (in_array($user->role, [UserRole::Admin, UserRole::Manager], true)) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === UserRole::Technician) {
            return view('technician.dashboard', $this->reports->technicianOverview($user));
        }

        return view('dashboard', $this->reports->customerOverview($user));
    }
}
