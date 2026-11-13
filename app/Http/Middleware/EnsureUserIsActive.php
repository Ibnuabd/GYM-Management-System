<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keluarkan staf yang akunnya sudah DINONAKTIFKAN admin (issue #27).
 *
 * Status akun dicek saat login. Tanpa middleware ini, staf yang dinonaktifkan
 * SETELAH login tetap bisa memakai aplikasi sampai sesinya habis.
 * Middleware ini mengecek ulang status di SETIAP request halaman staf.
 *
 * Dipasang di routes/web.php dengan alias "user.active" (lihat bootstrap/app.php).
 */
class EnsureUserIsActive
{
    /**
     * Pesan yang tampil di halaman login.
     */
    public const INACTIVE_MESSAGE = 'Akun Anda dinonaktifkan. Hubungi admin.';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user('web');

        if ($user !== null && ! $user->is_active) {
            // Hanya keluarkan akun STAF. Login portal member di browser yang sama tidak ikut keluar.
            Auth::guard('web')->logout();
            $request->session()->regenerate();

            // Request JSON (fetch dari halaman kasir/kiosk) mendapat 401, bukan halaman login.
            if ($request->expectsJson()) {
                return response()->json(['message' => self::INACTIVE_MESSAGE], 401);
            }

            return redirect()->route('login')->withErrors(['email' => self::INACTIVE_MESSAGE]);
        }

        return $next($request);
    }
}
