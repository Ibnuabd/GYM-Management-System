<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validasi form "Atur Password Portal Member" di halaman profil member (diisi staf).
 */
class UpdateMemberPortalPasswordRequest extends FormRequest
{
    /**
     * Hanya staf dengan permission "manage_members" (admin, supervisor, CS).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_members') ?? false;
    }

    /**
     * Password minimal 8 karakter dan harus diketik dua kali (field "password_confirmation").
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
            'password.required' => 'Password portal wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password portal minimal 8 karakter.',
        ];
    }
}
