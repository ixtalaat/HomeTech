<?php

namespace App\Http\Requests\Branch;

use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use App\Services\BranchService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTechnicianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Branch managers assign only their own branch's requests.
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
     * The technician must belong to the manager's branch.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $branchId = app(BranchService::class)->managedBranch($this->user())?->id;

        return [
            'technician_id' => [
                'required',
                'integer',
                Rule::exists('technicians', 'id')->where('branch_id', $branchId)->where('is_active', true),
            ],
        ];
    }
}
