<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi filter laporan (halaman laporan, export PDF, dan export Excel).
 *
 * Sebelumnya tanggal yang tidak valid (misal "?start_date=abc") membuat error 500.
 * Sekarang filter yang salah dikembalikan ke halaman laporan dengan pesan error.
 */
class ReportFilterRequest extends FormRequest
{
    /**
     * Rentang tanggal kustom terpanjang yang diizinkan (dalam hari), supaya query tidak terlalu berat.
     */
    public const MAX_RANGE_DAYS = 366;

    /**
     * Jika validasi gagal, kembali ke halaman laporan (tanpa filter) dan tampilkan pesan error.
     *
     * @var string
     */
    protected $redirectRoute = 'reports';

    /**
     * Route laporan sudah dilindungi permission "view_reports"; dicek lagi di sini untuk keamanan.
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
            'interval' => ['nullable', 'in:today,week,month,year,custom'],
            'category' => ['nullable', 'in:all,gym_subscription,pt_package,combo,pos_retail'],
            'start_date' => ['nullable', 'date', 'required_with:end_date'],
            'end_date' => [
                'nullable',
                'date',
                'required_with:start_date',
                'after_or_equal:start_date',
                $this->maxRangeRule(),
            ],
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
            'interval.in' => 'Pilihan periode laporan tidak valid.',
            'category.in' => 'Kategori laporan tidak valid.',
            'start_date.date' => 'Tanggal mulai tidak valid.',
            'start_date.required_with' => 'Tanggal mulai wajib diisi jika tanggal akhir diisi.',
            'end_date.date' => 'Tanggal akhir tidak valid.',
            'end_date.required_with' => 'Tanggal akhir wajib diisi jika tanggal mulai diisi.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ];
    }

    /**
     * Aturan: rentang tanggal kustom maksimal MAX_RANGE_DAYS hari.
     */
    private function maxRangeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $startDate = strtotime((string) $this->input('start_date'));
            $endDate = strtotime((string) $value);

            // Jika salah satu tanggal tidak valid, biarkan aturan "date" yang memberi pesan error.
            if ($startDate === false || $endDate === false) {
                return;
            }

            $days = Carbon::createFromTimestamp($startDate)->diffInDays(Carbon::createFromTimestamp($endDate));

            if ($days > self::MAX_RANGE_DAYS) {
                $fail('Rentang tanggal laporan maksimal 1 tahun.');
            }
        };
    }
}
