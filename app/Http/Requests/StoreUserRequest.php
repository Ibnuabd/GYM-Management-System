<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validasi form "Tambah User" di halaman Manajemen User (issue #27).
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Hanya user dengan permission "manage_users" (role admin).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_users') ?? false;
    }

    /**
     * Aturan validasi.
     *
     * "exclude_unless:role,admin,supervisor" artinya: PIN supervisor HANYA ikut
     * disimpan jika role-nya admin/supervisor. Untuk CS & PT, PIN diabaikan.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'string', Rule::in(User::STAFF_ROLES)],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
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
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
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
