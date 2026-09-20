<?php

namespace App\Http\Requests\Technician;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;

class UploadWorkPhotosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workOrder = $this->route('workOrder');

        return $this->user() !== null
            && $workOrder instanceof WorkOrder
            && $this->user()->can('update', $workOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'slot' => ['required', 'in:before,after'],
            'photos' => ['required', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
