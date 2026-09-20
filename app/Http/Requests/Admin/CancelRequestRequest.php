<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;

class CancelRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null
            && in_array($this->user()->role, [UserRole::Admin, UserRole::Manager], true)
            && $this->route('maintenanceRequest') instanceof MaintenanceRequest;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
