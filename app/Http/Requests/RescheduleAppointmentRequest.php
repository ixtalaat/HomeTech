<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Ownership and eligibility are enforced in the controller (404 for
     * foreign requests, 403 past the assignment point).
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
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
