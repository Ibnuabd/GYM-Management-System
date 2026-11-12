<?php

namespace App\Http\Requests;

use App\Rules\Base64Image;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_members') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nik' => ['nullable', 'string', 'max:20', 'unique:members,nik'],
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'rfid_uid' => ['nullable', 'string', 'max:100', 'unique:members,rfid_uid'],
            'gender' => ['required', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'photo_data' => ['nullable', 'string', new Base64Image],

            // Initial subscription fields (optional)
            'package_id' => ['nullable', 'exists:packages,id'],
            'pt_user_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap member wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'nik.unique' => 'NIK ini sudah terdaftar untuk member lain.',
            'rfid_uid.unique' => 'Kartu RFID ini sudah terdaftar untuk member lain.',
            'photo.image' => 'File foto harus berformat gambar valid.',
            'photo.max' => 'Ukuran file foto maksimal 2MB.',
        ];
    }
}
