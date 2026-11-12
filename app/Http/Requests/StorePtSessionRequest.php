<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form "Catat Sesi Latihan" yang diisi Personal Trainer
 * sebelum 1 kuota sesi member dipotong.
 */
class StorePtSessionRequest extends FormRequest
{
    /**
     * Hanya user dengan permission "manage_pt_sessions" (PT, admin, supervisor) yang boleh mengirim form ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_pt_sessions') ?? false;
    }

    /**
     * Aturan validasi. Fokus otot dan catatan latihan WAJIB diisi,
     * sehingga tidak ada sesi yang terpotong tanpa catatan aktivitas.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_subscription_id' => ['required', 'integer', 'exists:member_subscriptions,id'],
            'session_date' => ['nullable', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'muscle_focus' => ['required', 'string', 'max:150'],
            'workout_notes' => ['required', 'string', 'min:5'],
            'member_feedback' => ['nullable', 'string'],
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
            'member_subscription_id.required' => 'Pilih paket langganan member yang valid.',
            'member_subscription_id.exists' => 'Paket langganan member tidak ditemukan.',
            'duration_minutes.required' => 'Durasi latihan wajib diisi.',
            'duration_minutes.min' => 'Durasi minimal 15 menit.',
            'duration_minutes.max' => 'Durasi maksimal 240 menit.',
            'muscle_focus.required' => 'Fokus otot / program latihan wajib diisi.',
            'workout_notes.required' => 'Catatan menu latihan, beban, dan repetisi wajib diisi.',
            'workout_notes.min' => 'Catatan latihan minimal 5 karakter.',
        ];
    }
}
