<?php

namespace App\Http\Requests;

use App\Models\AdditionalWork;
use Illuminate\Foundation\Http\FormRequest;

class DecideAdditionalWorkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only the owning customer of a pending item may decide.
     */
    public function authorize(): bool
    {
        $item = $this->route('additionalWork');

        return $this->user() !== null
            && $item instanceof AdditionalWork
            && $item->isPending()
            && $item->workOrder->request->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
