<?php

namespace App\Http\Requests\Admin;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('inventoryItem');

        return $this->user() !== null
            && $item instanceof InventoryItem
            && $this->user()->can('update', $item);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $item = $this->route('inventoryItem');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('inventory_items', 'name')->ignore($item->id)],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('inventory_items', 'sku')->ignore($item->id)],
            'unit' => ['required', 'string', 'max:20'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
