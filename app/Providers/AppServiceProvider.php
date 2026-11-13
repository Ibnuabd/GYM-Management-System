<?php

namespace App\Providers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Status error yang ditampilkan dengan halaman Error.vue di PRODUCTION.
     * 404 selalu memakai Error.vue (juga di lokal). Status lain di lokal tetap
     * memakai halaman debug Laravel supaya programmer bisa melihat detail error.
     *
     * @var list<int>
     */
    private const PRODUCTION_ERROR_PAGE_STATUSES = [403, 404, 419, 500, 503];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureErrorPages();
    }

    /**
     * Atur tampilan saat terjadi error (halaman tidak ditemukan, tidak punya akses, dll).
     *
     * PENTING: Laravel hanya menyimpan SATU pengatur ini. Jangan menambah
     * $exceptions->respond(...) di bootstrap/app.php, karena akan saling menimpa.
     */
    private function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $error): ExceptionResponse|RedirectResponse|null {
            $status = $error->statusCode();
            $request = $error->request;
            $isInertiaRequest = $request->hasHeader('X-Inertia');

            // Aturan 1: tidak punya akses (403) saat klik menu/link di DALAM aplikasi.
            // Jangan tampilkan halaman error: kembali ke halaman sebelumnya + pop-up
            // (flash "error", lihat AuthenticatedLayout.vue).
            if ($isInertiaRequest && $status === 403) {
                return back()->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            }

            // Aturan 2: request JSON (fetch dari halaman kasir/kiosk) tetap mendapat JSON.
            if (! $isInertiaRequest && $request->expectsJson()) {
                return null;
            }

            // Aturan 3: tampilkan halaman Error.vue.
            $useErrorPage = $status === 404
                || (! app()->environment(['local', 'testing']) && in_array($status, self::PRODUCTION_ERROR_PAGE_STATUSES, true));

            if ($useErrorPage) {
                return $error->render('Error', ['status' => $status]);
            }

            // Selain itu: pakai tampilan bawaan Laravel.
            return null;
        });
    }
}
