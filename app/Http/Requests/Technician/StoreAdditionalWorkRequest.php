<?php

namespace App\Http\Requests\Technician;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdditionalWorkRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:2000'],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
