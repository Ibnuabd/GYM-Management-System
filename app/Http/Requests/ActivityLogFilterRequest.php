<?php

namespace App\Http\Requests;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi filter halaman Log Aktivitas (/activity-logs). Issue #28.
 *
 * Filter yang salah (misal tanggal "abc") dikembalikan ke halaman log
 * dengan pop-up pesan error, bukan error 500.
 */
class ActivityLogFilterRequest extends FormRequest
{
    /**
     * Rentang tanggal terpanjang yang boleh dipilih (dalam hari).
     */
    public const MAX_RANGE_DAYS = 31;

    /**
     * Default rentang tanggal: 7 hari terakhir (termasuk hari ini).
     */
    public const DEFAULT_RANGE_DAYS = 7;

    /**
     * Jika validasi gagal, kembali ke halaman log (tanpa filter).
     *
     * @var string
     */
    protected $redirectRoute = 'activity-logs.index';

    /**
     * Route sudah dilindungi permission "view_activity_logs"; dicek lagi di sini untuk keamanan.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view_activity_logs') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date', $this->maxRangeRule()],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'role' => ['nullable', Rule::in(User::STAFF_ROLES)],
            'area' => ['nullable', Rule::in(array_keys(ActivityLog::AREAS))],
            'search' => ['nullable', 'string', 'max:100'],
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
            'start_date.date' => 'Tanggal mulai tidak valid.',
            'end_date.date' => 'Tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            'user_id.exists' => 'User yang dipilih tidak ditemukan.',
            'user_id.integer' => 'User yang dipilih tidak valid.',
            'role.in' => 'Role tidak valid.',
            'area.in' => 'Jenis aktivitas tidak valid.',
            'search.max' => 'Kata pencarian maksimal 100 karakter.',
        ];
    }

    /**
     * Ambil filter yang sudah valid, lengkap dengan nilai default.
     *
     * Default: 7 hari terakhir, semua user / role / jenis aktivitas, tanpa pencarian.
     *
     * @return array{start_date: Carbon, end_date: Carbon, user_id: int|null, role: string|null, area: string|null, search: string}
     */
    public function filters(): array
    {
        $endDate = $this->filled('end_date') ? Carbon::parse($this->input('end_date')) : Carbon::today();
        $startDate = $this->filled('start_date')
            ? Carbon::parse($this->input('start_date'))
            : $endDate->copy()->subDays(self::DEFAULT_RANGE_DAYS - 1);

        return [
            'start_date' => $startDate->startOfDay(),
            'end_date' => $endDate->endOfDay(),
            'user_id' => $this->filled('user_id') ? (int) $this->input('user_id') : null,
            'role' => $this->input('role') ?: null,
            'area' => $this->input('area') ?: null,
            'search' => trim((string) $this->input('search', '')),
        ];
    }

    /**
     * Aturan: rentang tanggal maksimal MAX_RANGE_DAYS hari.
     * Jika tanggal akhir kosong, dianggap hari ini.
     */
    private function maxRangeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $startDate = strtotime((string) $value);
            $endDate = strtotime((string) ($this->input('end_date') ?: 'today'));

            // Jika tanggal tidak valid, biarkan aturan "date" yang memberi pesan error.
            if ($startDate === false || $endDate === false) {
                return;
            }

            $days = Carbon::createFromTimestamp($startDate)->diffInDays(Carbon::createFromTimestamp($endDate));

            if ($days > self::MAX_RANGE_DAYS) {
                $fail('Rentang tanggal maksimal '.self::MAX_RANGE_DAYS.' hari.');
            }
        };
    }
}
