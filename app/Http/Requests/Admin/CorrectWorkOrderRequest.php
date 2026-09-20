<?php

namespace App\Http\Requests\Admin;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;

class CorrectWorkOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workOrder = $this->route('workOrder');

        return $this->user() !== null
            && $workOrder instanceof WorkOrder
            && $this->user()->can('correct', $workOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'work_notes' => ['nullable', 'string', 'max:5000'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
