<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateBranchRequest extends FormRequest
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
        $branch = $this->route('branch');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('branches', 'name')->ignore($branch instanceof Branch ? $branch->id : null)],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['sometimes', 'boolean'],
            'cities' => ['nullable', 'string', 'max:2000'],
            'manager_mode' => ['nullable', 'in:none,existing,new'],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id', 'required_if:manager_mode,existing', Rule::unique('branches', 'manager_user_id')->ignore($branch instanceof Branch ? $branch->id : null)],
            'manager_name' => ['nullable', 'string', 'max:255', 'required_if:manager_mode,new'],
            'manager_email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email', 'required_if:manager_mode,new'],
            'manager_password' => ['nullable', 'string', Password::defaults(), 'max:255', 'required_if:manager_mode,new'],
        ];
    }
}
