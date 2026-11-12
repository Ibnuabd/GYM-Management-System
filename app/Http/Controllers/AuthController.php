<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Batas login gagal sebelum akun+IP dikunci sementara.
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Lama penguncian (detik) setelah batas login gagal tercapai.
     */
    private const LOCKOUT_SECONDS = 60;

    /**
     * Handle an incoming authentication request.
     *
     * Dilindungi rate limiting: setelah 5 kali gagal untuk email + IP yang sama,
     * user harus menunggu 60 detik. Ini mencegah orang menebak password berkali-kali.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Kunci unik untuk menghitung percobaan gagal, contoh: "login|admin@fitcore.com|127.0.0.1"
        $throttleKey = 'login|'.Str::lower($credentials['email']).'|'.$request->ip();

        // Langkah 1: tolak dulu jika sudah terlalu banyak gagal.
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $secondsLeft = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$secondsLeft} detik.",
            ]);
        }

        // Langkah 2: cek email & password. Jika salah, tambah hitungan gagal.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'email' => __('Kredensial yang dimasukkan tidak cocok dengan catatan kami.'),
            ]);
        }

        // Langkah 3: login berhasil, hitungan gagal di-reset ke 0.
        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
