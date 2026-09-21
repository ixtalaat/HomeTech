<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Stream a stored photo after an ownership check.
     *
     * Uploads live on the private disk — never directly URL-guessable.
     */
    public function show(Request $request, string $path): StreamedResponse
    {
        $segments = explode('/', $path);
        $user = $request->user();

        $allowed = match ($segments[0] ?? null) {
            'request-photos' => $this->canViewRequestPhoto($user, (int) ($segments[1] ?? 0)),
            'work-orders' => $this->canViewWorkOrderPhoto($user, (int) ($segments[1] ?? 0)),
            default => false,
        };

        abort_unless($allowed, 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    /**
     * Determine whether the user may view a request photo.
     */
    private function canViewRequestPhoto(User $user, int $requestId): bool
    {
        $maintenanceRequest = MaintenanceRequest::find($requestId);

        return $maintenanceRequest !== null
            && ($maintenanceRequest->user_id === $user->id || $this->isStaff($user));
    }

    /**
     * Determine whether the user may view a work-order photo.
     */
    private function canViewWorkOrderPhoto(User $user, int $workOrderId): bool
    {
        $workOrder = WorkOrder::find($workOrderId);

        return $workOrder !== null
            && ($workOrder->technician?->user_id === $user->id
                || $workOrder->request->user_id === $user->id
                || $this->isStaff($user));
    }

    /**
     * Determine whether the user is admin or manager staff.
     */
    private function isStaff(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
