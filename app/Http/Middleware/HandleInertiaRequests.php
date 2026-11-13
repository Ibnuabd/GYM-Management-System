<?php

namespace App\Http\Middleware;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Kios dianggap "aktif" jika ada scan kartu dalam sekian menit terakhir.
     */
    public const KIOSK_ACTIVE_MINUTES = 30;

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name ?? $user->full_name,
                    'email' => $user->email,
                    'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : ['member'],
                    'permissions' => method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [],
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                // Informasi netral (misal: fitur belum tersedia), tampil sebagai pop-up biru
                'info' => fn () => $request->session()->get('info'),
            ],
            // Kartu status kios di sidebar (bawah). Null jika user tidak memakai kios.
            'kioskStatus' => fn () => $this->kioskStatus($request),
        ];
    }

    /**
     * Status kios kehadiran berdasarkan data NYATA: scan kartu terakhir hari ini.
     *
     * Browser admin tidak bisa mendeteksi RFID reader / webcam di komputer kios,
     * jadi bukti terbaik bahwa kios bekerja adalah: kapan terakhir ada scan.
     *
     * @return array{is_active: bool, last_scan_time: string|null, last_scan_ago: string|null, today_checkins: int}|null
     */
    private function kioskStatus(Request $request): ?array
    {
        $user = $request->user();

        // Hanya untuk staf yang memakai kios (admin, supervisor, CS). PT & member tidak perlu.
        if (! $user instanceof User || ! $user->can('scan_attendance')) {
            return null;
        }

        $lastScan = Attendance::today()->latest('scanned_at')->first();

        return [
            // true = ada scan dalam 30 menit terakhir (titik hijau), false = sepi (titik abu-abu)
            'is_active' => $lastScan !== null && $lastScan->scanned_at->gte(now()->subMinutes(self::KIOSK_ACTIVE_MINUTES)),
            'last_scan_time' => $lastScan?->scanned_at->format('H:i'),
            'last_scan_ago' => $lastScan?->scanned_at->diffForHumans(),
            'today_checkins' => Attendance::today()->where('status', 'IN')->count(),
        ];
    }
}
