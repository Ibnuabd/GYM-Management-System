<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form "Edit User" di halaman Manajemen User (issue #27).
 *
 * Password TIDAK diubah di sini (ada form "Reset Password" sendiri).
 * PIN supervisor dikosongkan = PIN lama tetap dipakai.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Hanya user dengan permission "manage_users" (role admin).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_users') ?? false;
    }

    /**
     * Aturan validasi. Email boleh sama dengan email user ini sendiri (ignore).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $editedUser */
        $editedUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editedUser->id)],
            'role' => ['required', 'string', Rule::in(User::STAFF_ROLES)],
            'supervisor_pin' => ['exclude_unless:role,'.implode(',', User::ROLES_WITH_SUPERVISOR_PIN), 'nullable', 'digits:6'],
        ];
    }

    /**
     * Pesan error dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah dipakai user lain.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid. Pilih admin, supervisor, cs, atau pt.',
            'supervisor_pin.digits' => 'PIN supervisor harus 6 digit angka.',
        ];
    }

    /**
     * Rapikan input sebelum divalidasi: email disimpan huruf kecil tanpa spasi.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
