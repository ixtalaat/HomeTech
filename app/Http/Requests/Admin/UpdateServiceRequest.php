<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && in_array($this->user()->role, [UserRole::Admin, UserRole::Manager], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Service|null $service */
        $service = $this->route('service');
        $serviceId = $service instanceof Service ? $service->id : $service;

        return [
            'service_category_id' => ['required', 'exists:service_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('services', 'slug')->ignore($serviceId),
            ],
            'description' => ['nullable', 'string', 'max:3000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'estimated_duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
