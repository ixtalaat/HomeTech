<?php

namespace App\Http\Requests\Admin;

use App\Models\Technician;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnicianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $technician = $this->route('technician');

        return $this->user() !== null
            && $technician instanceof Technician
            && $this->user()->can('update', $technician);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'hired_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:service_categories,id'],
        ];
    }
}
