<?php

namespace App\Http\Requests\Branch;

use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use App\Services\BranchService;
use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Branch managers act only on requests served by their own branch.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $request = $this->route('maintenanceRequest');

        if ($user === null || $user->role !== UserRole::Manager || ! $request instanceof MaintenanceRequest) {
            return false;
        }

        $branch = app(BranchService::class)->managedBranch($user);

        return $branch !== null && app(BranchService::class)->ownsRequest($branch, $request);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $action = match (true) {
            $this->routeIs('branch.requests.approve') => 'approve',
            $this->routeIs('branch.requests.reject') => 'reject',
            $this->routeIs('branch.requests.request-info') => 'request-info',
            default => null,
        };

        return match ($action) {
            'approve' => [
                'admin_note' => ['nullable', 'string', 'max:2000'],
                'preferred_date' => ['nullable', 'date', 'after:today'],
                'preferred_time' => ['nullable', 'date_format:H:i'],
            ],
            'reject' => [
                'rejection_reason' => ['required', 'string', 'max:2000'],
            ],
            'request-info' => [
                'admin_note' => ['required', 'string', 'max:2000'],
            ],
            default => [],
        };
    }
}
