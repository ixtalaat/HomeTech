<?php

namespace App\Http\Requests\Admin;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;

class ReviewMaintenanceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $request = $this->route('maintenanceRequest');

        return $this->user() !== null
            && $request instanceof MaintenanceRequest
            && $this->user()->can('review', $request);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $action = $this->route('action') ?? match (true) {
            $this->routeIs('admin.requests.approve') => 'approve',
            $this->routeIs('admin.requests.reject') => 'reject',
            $this->routeIs('admin.requests.request-info') => 'request-info',
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
