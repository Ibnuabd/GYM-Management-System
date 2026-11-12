<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Dijalankan sebelum trait seperti RefreshDatabase bekerja.
     *
     * RefreshDatabase MENGHAPUS semua tabel lalu membuatnya ulang. Karena itu
     * kita pastikan dulu bahwa test memakai database khusus test,
     * supaya data development tidak ikut terhapus.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $this->ensureTestDatabaseIsUsed();

        return parent::setUpTraits();
    }

    /**
     * Hentikan test jika nama database tidak berakhiran "_test".
     *
     * Penyebab umum jika error ini muncul:
     * - DB_DATABASE di phpunit.xml bukan database test, atau
     * - config sedang di-cache. Jalankan: php artisan config:clear
     */
    private function ensureTestDatabaseIsUsed(): void
    {
        $connection = config('database.default');
        $databaseName = (string) config("database.connections.{$connection}.database");

        if (! str_ends_with($databaseName, '_test')) {
            throw new RuntimeException(
                "Test DIHENTIKAN: database yang dipakai adalah \"{$databaseName}\". ".
                'Test hanya boleh memakai database yang namanya berakhiran "_test" (lihat DB_DATABASE di phpunit.xml). '.
                'Jika phpunit.xml sudah benar, jalankan "php artisan config:clear".'
            );
        }
    }
}
