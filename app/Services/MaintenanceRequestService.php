<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Address;
use App\Models\Branch;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\User;
use App\Notifications\RequestSubmitted;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceRequestService
{
    /**
     * Paginate the customer's requests with an optional status filter.
     *
     * @return LengthAwarePaginator<int, MaintenanceRequest>
     */
    public function paginateForUser(User $user, ?string $status): LengthAwarePaginator
    {
        $query = $user->maintenanceRequests()->with(['service', 'address'])->latest();

        $status = $status !== null ? RequestStatus::tryFrom($status) : null;

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Paginate all requests for staff review with optional filters.
     *
     * Pass a branch to scope the listing to its served cities.
     *
     * @param  array{search?: ?string, status?: ?string}  $filters
     * @return LengthAwarePaginator<int, MaintenanceRequest>
     */
    public function paginateForAdmin(array $filters, ?Branch $branch = null): LengthAwarePaginator
    {
        $query = MaintenanceRequest::with(['user', 'service', 'address'])->latest();

        if ($branch !== null) {
            $cities = $branch->cities()->pluck('name');
            $query->whereHas('address', fn ($address) => $address->whereIn('city', $cities->all()));
        }

        if (! empty($filters['status'])) {
            $status = RequestStatus::tryFrom($filters['status']);

            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($inner) use ($search): void {
                $inner->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Get the form data for creating a request: bookable services and own addresses.
     *
     * @return array{services: Collection<int, Service>, addresses: Collection<int, Address>}
     */
    public function createFormData(User $user): array
    {
        return [
            'services' => Service::active()
                ->whereHas('category', fn ($query): Builder => $query->where('is_active', true))
                ->orderBy('name')
                ->get(),
            'addresses' => $user->addresses()->orderByDesc('is_default')->latest()->get(),
        ];
    }

    /**
     * Create a maintenance request with photo uploads and the initial history row.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $photos
     */
    public function create(User $user, array $attributes, array $photos = []): MaintenanceRequest
    {
        return DB::transaction(function () use ($user, $attributes, $photos): MaintenanceRequest {
            $maintenanceRequest = $user->maintenanceRequests()->create([
                ...$attributes,
                'status' => RequestStatus::PendingReview,
                'photos' => null,
            ]);

            $photoPaths = [];
            foreach ($photos as $photo) {
                $photoPaths[] = $photo->store("request-photos/{$maintenanceRequest->id}", 'local');
            }

            if ($photoPaths !== []) {
                $maintenanceRequest->update(['photos' => $photoPaths]);
            }

            $maintenanceRequest->statusHistories()->create([
                'from_status' => null,
                'status' => RequestStatus::PendingReview->value,
                'changed_by' => $user->id,
                'reason' => null,
            ]);

            $user->notify(new RequestSubmitted($maintenanceRequest));

            return $maintenanceRequest;
        });
    }

    /**
     * Move the preferred slot of an approved, unassigned request.
     *
     * Owners use this after a failed automatic assignment to propose a new
     * time; assignment itself stays with the caller so it can flash the
     * outcome right away.
     *
     * @param  array{preferred_date: string, preferred_time: string}  $attributes
     */
    public function rescheduleByCustomer(MaintenanceRequest $request, User $user, array $attributes): MaintenanceRequest
    {
        return DB::transaction(function () use ($request, $user, $attributes): MaintenanceRequest {
            $request->update([
                'preferred_date' => $attributes['preferred_date'],
                'preferred_time' => $attributes['preferred_time'],
            ]);

            $request->statusHistories()->create([
                'from_status' => RequestStatus::Approved->value,
                'status' => RequestStatus::Approved->value,
                'changed_by' => $user->id,
                'reason' => "Customer rescheduled to {$attributes['preferred_date']} {$attributes['preferred_time']}.",
            ]);

            return $request->refresh();
        });
    }
}
