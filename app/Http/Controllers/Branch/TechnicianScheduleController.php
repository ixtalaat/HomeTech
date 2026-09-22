<?php

namespace App\Http\Controllers\Branch;

use App\Exceptions\ScheduleException;
use App\Http\Requests\Branch\UpdateScheduleRequest;
use App\Models\Technician;
use App\Services\TechnicianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TechnicianScheduleController extends BaseController
{
    public function __construct(private TechnicianService $technicians) {}

    /**
     * Show the weekly schedule editor for a branch technician.
     */
    public function edit(Technician $technician): View
    {
        $branch = $this->managedBranch();

        abort_unless($technician->branch_id === $branch->id, 404);

        $technician->load(['user', 'schedules']);

        $days = collect(range(0, 6))->map(function (int $dayOfWeek) use ($technician): array {
            $row = $technician->schedules->firstWhere('day_of_week', $dayOfWeek);

            return [
                'day_of_week' => $dayOfWeek,
                'is_working' => $row !== null ? $row->is_working : true,
                'start_time' => $row?->start_time !== null ? substr((string) $row->start_time, 0, 5) : '08:00',
                'end_time' => $row?->end_time !== null ? substr((string) $row->end_time, 0, 5) : '17:00',
            ];
        })->all();

        return view('admin.technicians.schedule', compact('technician', 'days') + [
            'action' => route('branch.technicians.schedule.update', $technician),
            'backUrl' => route('branch.technicians.show', $technician),
        ]);
    }

    /**
     * Replace a branch technician's weekly schedule in storage.
     */
    public function update(UpdateScheduleRequest $request, Technician $technician): RedirectResponse
    {
        $branch = $this->managedBranch();

        abort_unless($technician->branch_id === $branch->id, 404);

        try {
            $this->technicians->syncSchedule($technician, $request->validated('days'));
        } catch (ScheduleException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('branch.technicians.show', $technician)
            ->with('success', __('Work schedule updated successfully.'));
    }
}
