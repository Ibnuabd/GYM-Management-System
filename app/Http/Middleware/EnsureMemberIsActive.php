<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keluarkan member dari portal jika statusnya bukan "active".
 *
 * Status member hanya dicek saat login. Tanpa middleware ini, member yang
 * ditangguhkan (suspended) SETELAH login tetap bisa memakai portal sampai sesinya habis.
 * Middleware ini mengecek ulang status di SETIAP request ke halaman portal.
 *
 * Dipasang di routes/web.php dengan alias "member.active" (lihat bootstrap/app.php).
 */
class EnsureMemberIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Member|null $member */
        $member = $request->user('member');

        if ($member !== null && $member->status !== 'active') {
            // Hanya keluarkan akun MEMBER. Login staf di browser yang sama tidak ikut keluar.
            Auth::guard('member')->logout();

            return redirect()->route('portal.login')->withErrors([
                'login' => Member::portalAccessDeniedMessage($member->status),
            ]);
        }

        return $next($request);
    }
}
