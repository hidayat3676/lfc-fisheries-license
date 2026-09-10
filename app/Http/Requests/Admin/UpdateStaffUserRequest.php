<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModuleAction('users', 'edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');
        $isSuper = $this->input('user_type') === User::TYPE_SUPER_ADMIN;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'mobile' => ['nullable', 'string', 'max:30'],
            'cnic' => ['nullable', 'string', 'max:20'],
            'father_name' => ['nullable', 'string', 'max:120'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:80'],
            'designation_label' => ['nullable', 'string', 'max:120'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'user_type' => ['required', Rule::in([User::TYPE_ADMIN, User::TYPE_SUPER_ADMIN, User::TYPE_EXECUTIVE])],
            'is_active' => ['sometimes', 'boolean'],
            'all_districts' => ['sometimes', 'boolean'],
            'districts' => [
                Rule::requiredIf(! $isSuper && ! $this->boolean('all_districts')),
                'array',
            ],
            'districts.*' => ['integer', 'exists:districts,id'],
            'offices' => ['nullable', 'array'],
            'offices.*' => ['integer', 'exists:offices,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['sometimes'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->user()?->isSuperAdmin() && $this->input('user_type') === User::TYPE_SUPER_ADMIN) {
            $this->merge(['user_type' => User::TYPE_ADMIN]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'all_districts' => $this->boolean('all_districts'),
        ]);
    }
}
