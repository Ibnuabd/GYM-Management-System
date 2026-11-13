<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    /**
     * Display a listing of packages.
     */
    public function index(): Response
    {
        $packages = Package::withCount(['subscriptions' => function ($q) {
            $q->where('status', 'active');
        }])->get();

        return Inertia::render('Packages/Index', [
            'packages' => $packages,
        ]);
    }

    /**
     * Store a newly created package in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:gym,pt,combo'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'session_count' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $package = Package::create($validated);

        ActivityLog::record('package.created', "Membuat paket {$package->name}", $package, [
            'type' => $package->type,
            'price' => (float) $package->price,
            'duration_days' => $package->duration_days,
        ]);

        return redirect()->route('packages.index')
            ->with('success', "Paket {$validated['name']} berhasil dibuat!");
    }

    /**
     * Update the specified package in storage.
     */
    public function update(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:gym,pt,combo'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'session_count' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $package->update($validated);

        ActivityLog::record('package.updated', "Mengubah paket {$package->name}", $package, ActivityLog::changesOf($package));

        return redirect()->route('packages.index')
            ->with('success', "Paket {$package->name} berhasil diperbarui.");
    }

    /**
     * Toggle active status or delete package.
     */
    public function destroy(Package $package): RedirectResponse
    {
        $name = $package->name;

        // Jika paket PERNAH dipakai (langganan status apa pun: active, queued, expired,
        // exhausted, cancelled), paket TIDAK dihapus, hanya dinonaktifkan.
        // Alasannya: riwayat langganan dipakai untuk laporan keuangan dan log sesi PT.
        // Paket nonaktif tidak muncul lagi saat mendaftarkan langganan baru.
        if ($package->subscriptions()->exists()) {
            $package->update(['is_active' => false]);

            ActivityLog::record('package.deactivated', "Menonaktifkan paket {$name} (punya riwayat langganan)", $package);

            return redirect()->route('packages.index')
                ->with('success', "Paket {$name} telah dinonaktifkan (tidak dihapus) karena memiliki riwayat langganan member.");
        }

        // Paket belum pernah dipakai sama sekali, jadi aman dihapus permanen.
        $package->delete();

        ActivityLog::record('package.deleted', "Menghapus paket {$name}", $package, ['name' => $name]);

        return redirect()->route('packages.index')
            ->with('success', "Paket {$name} berhasil dihapus.");
    }
}
