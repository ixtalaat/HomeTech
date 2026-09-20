<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('inventoryItem');

        return $this->user() !== null
            && $item instanceof InventoryItem
            && $this->user()->can('adjust', $item);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'delta' => ['required', 'integer', 'not_in:0', 'min:-1000000', 'max:1000000'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
