<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi filter halaman Log Kehadiran & Foto (/attendances).
 *
 * Filter yang salah (misal tanggal "abc") dikembalikan ke halaman log
 * dengan pop-up pesan error, bukan error 500.
 */
class AttendanceLogFilterRequest extends FormRequest
{
    /**
     * Rentang tanggal terpanjang yang boleh dipilih (dalam hari).
     */
    public const MAX_RANGE_DAYS = 31;

    /**
     * Jika validasi gagal, kembali ke halaman log (tanpa filter).
     *
     * @var string
     */
    protected $redirectRoute = 'attendances.index';

    /**
     * Route sudah dilindungi permission "view_reports"; dicek lagi di sini untuk keamanan.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view_reports') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', $this->maxRangeRule()],
            'status' => ['nullable', 'in:IN,OUT'],
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
            'status.in' => 'Status hanya boleh IN atau OUT.',
            'search.max' => 'Kata pencarian maksimal 100 karakter.',
        ];
    }

    /**
     * Ambil filter yang sudah valid, lengkap dengan nilai default.
     *
     * Default: hari ini sampai hari ini, semua status, tanpa pencarian.
     *
     * @return array{start_date: Carbon, end_date: Carbon, status: string|null, search: string}
     */
    public function filters(): array
    {
        $startDate = $this->filled('start_date') ? Carbon::parse($this->input('start_date')) : Carbon::today();
        $endDate = $this->filled('end_date') ? Carbon::parse($this->input('end_date')) : $startDate->copy();

        return [
            'start_date' => $startDate->startOfDay(),
            'end_date' => $endDate->endOfDay(),
            'status' => $this->input('status'),
            'search' => trim((string) $this->input('search', '')),
        ];
    }

    /**
     * Aturan: rentang tanggal maksimal MAX_RANGE_DAYS hari.
     */
    private function maxRangeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $startDate = strtotime((string) ($this->input('start_date') ?: 'today'));
            $endDate = strtotime((string) $value);

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
