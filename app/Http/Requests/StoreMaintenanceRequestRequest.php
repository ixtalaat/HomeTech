<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where('is_active', true),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $service = Service::with('category')->find($value);

                    if ($service === null) {
                        return;
                    }

                    if (! $service->is_active || $service->category === null || ! $service->category->is_active) {
                        $fail('The selected service is not currently available.');
                    }
                },
            ],
            'address_id' => [
                'required',
                'integer',
                Rule::exists('addresses', 'id')->where('user_id', $this->user()?->id),
            ],
            'description' => ['required', 'string', 'max:3000'],
            'preferred_date' => ['required', 'date', 'after:today'],
            'preferred_time' => ['required', 'date_format:H:i'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address_id.exists' => 'The selected address is invalid or does not belong to you.',
        ];
    }
}
