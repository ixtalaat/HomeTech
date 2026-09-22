<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateManagerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $manager = $this->route('manager');

        return $this->user() !== null
            && $manager instanceof User
            && $manager->role === UserRole::Manager
            && $this->user()->can('update', $manager);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $manager = $this->route('manager');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($manager instanceof User ? $manager->id : null)],
            'password' => ['nullable', 'string', Password::defaults(), 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $manager = $this->route('manager');
                    $branch = Branch::find($value);

                    if ($branch !== null && $branch->manager_user_id !== null && ! ($manager instanceof User && $branch->manager_user_id === $manager->id)) {
                        $fail(__('This branch already has a manager.'));
                    }
                },
            ],
        ];
    }
}
