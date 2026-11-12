<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MemberPortalAuthController extends Controller
{
    /**
     * Display the member portal login view.
     */
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return Inertia::render('Portal/Login');
    }

    /**
     * Batas login gagal sebelum identitas+IP dikunci sementara.
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Lama penguncian (detik) setelah batas login gagal tercapai.
     */
    private const LOCKOUT_SECONDS = 60;

    /**
     * Handle incoming member self-service portal authentication.
     *
     * Dilindungi rate limiting: setelah 5 kali gagal untuk identitas login + IP yang sama,
     * member harus menunggu 60 detik sebelum mencoba lagi.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $loginInput = trim($credentials['login']);

        // Kunci unik untuk menghitung percobaan gagal, contoh: "portal-login|081234567890|127.0.0.1"
        $throttleKey = 'portal-login|'.Str::lower($loginInput).'|'.$request->ip();

        // Langkah 1: tolak dulu jika sudah terlalu banyak gagal.
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $secondsLeft = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$secondsLeft} detik.",
            ]);
        }

        // Login bisa memakai Email, Nomor HP, atau Kode Member
        $member = Member::where('email', $loginInput)
            ->orWhere('phone', $loginInput)
            ->orWhere('member_code', $loginInput)
            ->first();

        // Langkah 2: cek password. Jika salah, tambah hitungan gagal.
        if (! $member || ! Hash::check($credentials['password'], $member->password)) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'login' => __('Kredensial member tidak cocok dengan catatan keanggotaan kami.'),
            ]);
        }

        // Langkah 3: password benar, hitungan gagal di-reset ke 0.
        RateLimiter::clear($throttleKey);

        // Hanya member berstatus "active" yang boleh masuk portal
        // (aturan yang sama dicek ulang di setiap halaman oleh middleware EnsureMemberIsActive).
        if ($member->status !== 'active') {
            throw ValidationException::withMessages([
                'login' => Member::portalAccessDeniedMessage($member->status),
            ]);
        }

        Auth::guard('member')->login($member, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    /**
     * Destroy an authenticated member portal session.
     *
     * Staf dan member memakai sesi (session) browser yang sama, tapi "guard" yang berbeda.
     * Jika di browser ini staf juga sedang login, kita HANYA mengeluarkan akun member,
     * supaya staf tidak ikut ter-logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();

        if (Auth::guard('web')->check()) {
            // Staf masih login: cukup ganti ID sesi (mencegah session fixation), data staf tetap ada.
            $request->session()->regenerate();
        } else {
            // Tidak ada staf yang login: hapus seluruh sesi seperti biasa.
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('portal.login');
    }
}
