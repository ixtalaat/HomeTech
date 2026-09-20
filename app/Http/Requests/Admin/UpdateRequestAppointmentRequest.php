<?php

namespace App\Http\Requests\Admin;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $request = $this->route('maintenanceRequest');

        return $this->user() !== null
            && $request instanceof MaintenanceRequest
            && $this->user()->can('updateAppointment', $request);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'preferred_date' => ['required', 'date', 'after:today'],
            'preferred_time' => ['required', 'date_format:H:i'],
        ];
    }
}
