<?php

namespace App\Http\Controllers\Branch;

use App\Models\Technician;
use App\Services\TechnicianService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TechnicianController extends BaseController
{
    public function __construct(private TechnicianService $technicians) {}

    /**
     * Display the branch technicians.
     */
    public function index(Request $request): View
    {
        $branch = $this->managedBranch();

        $technicians = $this->technicians->paginate($request->only(['search', 'status']), $branch);

        return view('branch.technicians.index', compact('branch', 'technicians'));
    }

    /**
     * Display a branch technician with their schedule.
     */
    public function show(Technician $technician): View
    {
        $branch = $this->managedBranch();

        abort_unless($technician->branch_id === $branch->id, 404);

        $technician->load(['user', 'categories', 'branch', 'schedules']);

        return view('branch.technicians.show', compact('branch', 'technician'));
    }
}
