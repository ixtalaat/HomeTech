<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Technician;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnicianScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $technician = $this->route('technician');

        return $this->user() !== null
            && $technician instanceof Technician
            && in_array($this->user()->role, [UserRole::Admin, UserRole::Manager], true);
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
