<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validasi form "Reset Password" staf di halaman Manajemen User (issue #27).
 */
class ResetUserPasswordRequest extends FormRequest
{
    /**
     * Hanya user dengan permission "manage_users" (role admin).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_users') ?? false;
    }

    /**
     * Password baru minimal 8 karakter dan harus diketik dua kali (field "password_confirmation").
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
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
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
