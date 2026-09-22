<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportingService $reports) {}

    /**
     * Display revenue reports.
     */
    public function revenue(): View
    {
        return view('admin.reports.revenue', [
            'daily' => $this->reports->revenueDaily(),
            'monthly' => $this->reports->revenueMonthly(),
            'byService' => $this->reports->revenueByService(),
            'byTechnician' => $this->reports->revenueByTechnician(),
            'outstanding' => $this->reports->outstandingInvoices(),
        ]);
    }

    /**
     * Display job reports.
     */
    public function jobs(): View
    {
        return view('admin.reports.jobs', [
            'byStatus' => $this->reports->jobsByStatus(),
            'byService' => $this->reports->jobsByService(),
            'averageCompletionHours' => $this->reports->averageCompletionHours(),
        ]);
    }

    /**
     * Display technician performance reports.
     */
    public function technicians(): View
    {
        return view('admin.reports.technicians', [
            'technicians' => $this->reports->technicianStats(),
        ]);
    }

    /**
     * Display the dispatcher calendar.
     */
    public function calendar(Request $request): View
    {
        return view('admin.reports.calendar', $this->reports->calendarWeek(
            $request->query('week'),
            $request->query('branch_id') !== null ? (int) $request->query('branch_id') : null,
        ));
    }

    /**
     * Display inventory reports.
     */
    public function inventory(): View
    {
        return view('admin.reports.inventory', $this->reports->inventoryReport());
    }
}
