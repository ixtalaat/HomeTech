<?php

namespace App\Http\Requests\Branch;

use App\Enums\UserRole;
use App\Models\Technician;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Branch managers edit only their own branch's technicians.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $technician = $this->route('technician');

        if ($user === null || $user->role !== UserRole::Manager || ! $technician instanceof Technician) {
            return false;
        }

        $branch = $user->managedBranch;

        return $branch !== null && $branch->is_active && $technician->branch_id === $branch->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Expects one entry per weekday (0 = Sunday … 6 = Saturday).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'size:7'],
            'days.*.day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'days.*.is_working' => ['sometimes', 'boolean'],
            'days.*.start_time' => ['nullable', 'date_format:H:i'],
            'days.*.end_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
