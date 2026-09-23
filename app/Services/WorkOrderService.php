<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Exceptions\CancelledAppointmentException;
use App\Exceptions\CompletedWorkOrderException;
use App\Exceptions\WorkOrderException;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\JobCompleted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    public function __construct(
        private RequestStatusService $transitions,
        private SchedulingService $scheduling,
        private InventoryService $inventory,
        private WhatsAppService $whatsapp
    ) {}

    /**
     * Start the visit: refuse cancelled appointments, open the work order,
     * and move the request to in_progress.
     *
     * @throws WorkOrderException
     */
    public function startVisit(MaintenanceRequest $request, User $actor): WorkOrder
    {
        if (! in_array($request->status, [RequestStatus::Scheduled, RequestStatus::TechnicianOnWay], true)) {
            throw new WorkOrderException("Cannot start the visit for request #{$request->id} with status '{$request->status->value}'.");
        }

        if ($request->technician === null || ($request->technician->user_id !== $actor->id && ! $this->isStaff($actor))) {
            throw new WorkOrderException(__('Only the assigned technician (or staff) can start this visit.'));
        }

        $appointment = $request->appointment;

        if ($appointment === null) {
            throw new WorkOrderException(__('Cannot start the visit without a booked appointment.'));
        }

        try {
            $this->scheduling->start($appointment);
        } catch (CancelledAppointmentException $exception) {
            throw new WorkOrderException($exception->getMessage());
        }

        return DB::transaction(function () use ($request, $actor, $appointment): WorkOrder {
            $workOrder = WorkOrder::firstOrCreate(
                ['maintenance_request_id' => $request->id],
                [
                    'appointment_id' => $appointment->id,
                    'technician_id' => $request->technician_id,
                    'status' => WorkOrderStatus::InProgress,
                    'started_at' => now(),
                ]
            );

            if ($workOrder->status === WorkOrderStatus::Open) {
                $workOrder->update([
                    'status' => WorkOrderStatus::InProgress,
                    'started_at' => $workOrder->started_at ?? now(),
                ]);
            }

            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::InProgress,
                $actor,
                "Visit started by {$actor->name}."
            );

            return $workOrder->refresh();
        });
    }

    /**
     * Record the diagnosis.
     *
     * @throws CompletedWorkOrderException
     */
    public function recordDiagnosis(WorkOrder $workOrder, ?User $actor, string $diagnosis): WorkOrder
    {
        $this->guardEditable($workOrder, $actor);

        $workOrder->update(['diagnosis' => $diagnosis]);

        return $workOrder->refresh();
    }

    /**
     * Record work notes.
     *
     * @throws CompletedWorkOrderException
     */
    public function recordNotes(WorkOrder $workOrder, ?User $actor, string $notes): WorkOrder
    {
        $this->guardEditable($workOrder, $actor);

        $workOrder->update(['work_notes' => $notes]);

        return $workOrder->refresh();
    }

    /**
     * Add a labor item.
     *
     * @throws CompletedWorkOrderException
     */
    public function addLaborItem(WorkOrder $workOrder, ?User $actor, string $description, float $cost): WorkOrder
    {
        $this->guardEditable($workOrder, $actor);

        $workOrder->laborItems()->create([
            'description' => $description,
            'cost' => $cost,
        ]);

        return $workOrder->refresh();
    }

    /**
     * Record material usage, decrementing stock with a linked movement (BR-003, BR-004).
     *
     * @throws CompletedWorkOrderException
     */
    public function recordMaterialUsage(WorkOrder $workOrder, ?User $actor, InventoryItem $item, int $quantity): WorkOrder
    {
        $this->guardEditable($workOrder, $actor);

        $this->inventory->recordUsage($workOrder, $item, $quantity, $actor);

        return $workOrder->refresh();
    }

    /**
     * Upload before/after photos.
     *
     * @param  array<int, UploadedFile>  $photos
     *
     * @throws CompletedWorkOrderException
     */
    public function uploadPhotos(WorkOrder $workOrder, ?User $actor, string $slot, array $photos): WorkOrder
    {
        $this->guardEditable($workOrder, $actor);

        if (! in_array($slot, ['before', 'after'], true)) {
            throw new WorkOrderException(__('Photos must be uploaded to the before or after slot.'));
        }

        $column = $slot === 'before' ? 'before_photos' : 'after_photos';
        $paths = $workOrder->{$column} ?? [];

        foreach ($photos as $photo) {
            $paths[] = $photo->store("work-orders/{$workOrder->id}/{$slot}", 'local');
        }

        $workOrder->update([$column => $paths]);

        return $workOrder->refresh();
    }

    /**
     * Complete the work order once all requirements are met (PRD §22).
     *
     * @throws WorkOrderException
     */
    public function complete(WorkOrder $workOrder, ?User $actor): WorkOrder
    {
        $this->guardEditable($workOrder, $actor);

        $unmet = $workOrder->unmetCompletionRequirements();

        if ($unmet !== []) {
            throw new WorkOrderException('Cannot complete the work order: '.implode(' ', $unmet));
        }

        return DB::transaction(function () use ($workOrder, $actor): WorkOrder {
            $workOrder->update([
                'status' => WorkOrderStatus::Completed,
                'completed_at' => now(),
            ]);

            if ($workOrder->appointment !== null) {
                $workOrder->appointment->update(['status' => AppointmentStatus::Completed]);
            }

            $this->transitions->transition(
                $workOrder->request->refresh(),
                RequestStatus::Completed,
                $actor,
                'Work completed.'
            );

            $workOrder->request->user->notify(new JobCompleted($workOrder->request));
            $this->whatsapp->notifyUser(
                $workOrder->request->user,
                "The work for your request #{$workOrder->request->id} is completed."
            );

            return $workOrder->refresh();
        });
    }

    /**
     * Authorized staff correction of a work order, always logged (BR-007).
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws WorkOrderException
     */
    public function correct(WorkOrder $workOrder, User $actor, array $attributes, string $reason): WorkOrder
    {
        if (! $this->isStaff($actor)) {
            throw new WorkOrderException(__('Only admin or manager staff can correct a work order.'));
        }

        $allowed = array_intersect_key($attributes, array_flip(['diagnosis', 'work_notes']));
        $changes = [];

        foreach ($allowed as $key => $value) {
            if ($workOrder->getAttribute($key) !== $value) {
                $changes[$key] = ['old' => $workOrder->getAttribute($key), 'new' => $value];
            }
        }

        return DB::transaction(function () use ($workOrder, $actor, $allowed, $changes, $reason): WorkOrder {
            if ($allowed !== []) {
                $workOrder->update($allowed);
            }

            AuditLog::record($actor, 'work_order.corrected', $workOrder->refresh(), $changes, $reason);

            return $workOrder->refresh();
        });
    }

    /**
     * Guard that the work order can still be edited (BR-007).
     *
     * @throws CompletedWorkOrderException
     */
    private function guardEditable(WorkOrder $workOrder, ?User $actor): void
    {
        if ($workOrder->isCompleted() && ($actor === null || ! $this->isStaff($actor))) {
            throw new CompletedWorkOrderException(__('This work order is completed and cannot be edited. Contact staff for a correction.'));
        }
    }

    /**
     * Determine whether the user is admin or manager staff.
     */
    private function isStaff(User $actor): bool
    {
        return in_array($actor->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
