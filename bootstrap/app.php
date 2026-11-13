<?php

use App\Http\Middleware\EnsureMemberIsActive;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('portal*') ? route('portal.login') : route('login'));

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            // Keluarkan member yang statusnya bukan "active" dari portal
            'member.active' => EnsureMemberIsActive::class,
            // Keluarkan staf yang akunnya dinonaktifkan admin (issue #27)
            'user.active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Jika user membuka halaman yang tidak boleh diakses (403) dari DALAM aplikasi
        // (klik menu/link Inertia), jangan tampilkan halaman error. Kembalikan ke halaman
        // sebelumnya dan tampilkan pop-up notifikasi (flash "error", lihat AuthenticatedLayout.vue).
        // Request biasa (mengetik URL langsung) dan request JSON tetap mendapat 403 seperti biasa.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $isInertiaRequest = $request->hasHeader('X-Inertia');

            if ($isInertiaRequest && $response->getStatusCode() === 403) {
                return back()->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            }

            return $response;
        });
    })->create();
