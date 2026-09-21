<?php

namespace App\Http\Requests\Admin;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $request = $this->route('maintenanceRequest');

        return $this->user() !== null
            && in_array($this->user()->role, [UserRole::Admin, UserRole::Manager], true)
            && $request instanceof MaintenanceRequest
            && in_array($request->status, [RequestStatus::Scheduled, RequestStatus::TechnicianOnWay], true)
            && $request->appointment !== null
            && ! $request->appointment->isCancelled();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
