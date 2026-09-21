<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private ReportingService $reports) {}

    /**
     * Display the operational overview dashboard.
     */
    public function index(): View
    {
        return view('admin.dashboard', $this->reports->dashboard());
    }
}
