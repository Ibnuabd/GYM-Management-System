<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Aturan validasi untuk foto yang dikirim dalam bentuk teks base64
 * (contoh: hasil jepretan webcam "data:image/jpeg;base64,/9j/4AAQ...").
 *
 * Kenapa perlu aturan khusus?
 * Teks base64 bisa berisi file APA SAJA, bukan hanya gambar. Jika kita percaya
 * begitu saja pada tulisan "image/xxx" dari user, orang jahat bisa menyimpan
 * file berbahaya ke folder publik. Karena itu kita:
 *   1. Membatasi ukuran teks.
 *   2. Men-decode isinya lalu MEMERIKSA ISI FILE-nya, apakah benar gambar.
 *   3. Hanya menerima jenis gambar yang ada di daftar ALLOWED_MIME_TYPES.
 *   4. Menentukan ekstensi file dari hasil pemeriksaan server, BUKAN dari input user.
 */
class Base64Image implements ValidationRule
{
    /**
     * Batas panjang teks base64 (sekitar 3 MB).
     */
    public const MAX_ENCODED_LENGTH = 3 * 1024 * 1024;

    /**
     * Jenis gambar yang diizinkan, beserta ekstensi file yang akan dipakai server.
     *
     * @var array<string, string>
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Dijalankan otomatis oleh Laravel saat validasi Form Request.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Foto webcam harus berupa teks base64.');

            return;
        }

        if (strlen($value) > self::MAX_ENCODED_LENGTH) {
            $fail('Ukuran foto webcam maksimal 3MB.');

            return;
        }

        if (self::decode($value) === null) {
            $fail('Foto webcam harus berupa gambar JPG, PNG, atau WEBP yang valid.');
        }
    }

    /**
     * Ubah teks base64 menjadi isi file gambar + ekstensi yang aman.
     *
     * Mengembalikan null jika teks bukan gambar yang diizinkan.
     * Fungsi ini juga dipakai controller saat menyimpan file,
     * sehingga aturan pemeriksaannya hanya ditulis di satu tempat.
     *
     * @return array{contents: string, extension: string}|null
     */
    public static function decode(string $dataUri): ?array
    {
        // Format wajib: "data:image/<jenis>;base64,<isi>"
        if (! preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $dataUri)) {
            return null;
        }

        $base64Text = substr($dataUri, strpos($dataUri, ',') + 1);

        // Parameter "true" = mode ketat: gagal jika ada karakter yang bukan base64.
        $contents = base64_decode($base64Text, true);
        if ($contents === false || $contents === '') {
            return null;
        }

        // Periksa ISI file (bukan namanya). Jika bukan gambar, hasilnya false.
        $imageInfo = @getimagesizefromstring($contents);
        if ($imageInfo === false) {
            return null;
        }

        $detectedMimeType = $imageInfo['mime'];
        if (! array_key_exists($detectedMimeType, self::ALLOWED_MIME_TYPES)) {
            return null;
        }

        return [
            'contents' => $contents,
            'extension' => self::ALLOWED_MIME_TYPES[$detectedMimeType],
        ];
    }
}
