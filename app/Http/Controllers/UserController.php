<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen User (RBAC) untuk akun STAF (tabel users, guard "web"). Issue #27.
 *
 * Aturan penting:
 * - Hanya admin (permission "manage_users") yang bisa membuka halaman ini (lihat routes/web.php).
 * - User TIDAK PERNAH dihapus, hanya dinonaktifkan. Menghapus user akan ikut menghapus
 *   riwayat transaksi kasir, audit void, dan sesi PT miliknya (cascadeOnDelete).
 * - Admin tidak boleh menonaktifkan / mengubah role dirinya sendiri.
 * - Admin aktif TERAKHIR tidak boleh dinonaktifkan / diturunkan role-nya.
 */
class UserController extends Controller
{
    /**
     * Jumlah user per halaman tabel.
     */
    private const PER_PAGE = 15;

    /**
     * Halaman daftar user staf + filter + ringkasan.
     */
    public function index(Request $request): Response
    {
        // Langkah 1: baca filter. Role yang tidak dikenal diabaikan (dianggap "semua role").
        $search = trim((string) $request->input('search', ''));
        $role = in_array($request->input('role'), User::STAFF_ROLES, true) ? $request->input('role') : '';

        // Langkah 2: ambil user sesuai filter, terbaru di atas.
        $users = User::query()
            ->with('roles')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($role !== '', fn (Builder $query) => $query->role($role))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (User $user) => $this->formatRow($user, $request->user()));

        return Inertia::render('Users/Index', [
            'users' => $users,
            'summary' => $this->summary(),
            'filters' => ['search' => $search, 'role' => $role],
            'roles' => User::STAFF_ROLES,
            'rolesWithSupervisorPin' => User::ROLES_WITH_SUPERVISOR_PIN,
        ]);
    }

    /**
     * Simpan user staf baru beserta role-nya.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'], // otomatis di-hash (cast "hashed" di model)
                'supervisor_pin' => $validated['supervisor_pin'] ?? null, // null untuk CS & PT
                'is_active' => true,
            ]);

            $user->syncRoles([$validated['role']]);

            ActivityLog::record('user.created', "Menambahkan user {$user->name} ({$user->email}) dengan role {$validated['role']}", $user, [
                'role' => $validated['role'],
            ]);
        });

        return back()->with('success', "User {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Ubah nama, email, role, dan (opsional) PIN supervisor.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $newRole = $validated['role'];
        $isRoleChanged = $user->primaryRole() !== $newRole;

        // Langkah 1: admin tidak boleh mengubah role dirinya sendiri (bisa terkunci keluar).
        if ($isRoleChanged && $user->is($request->user())) {
            return back()->with('error', 'Anda tidak bisa mengubah role akun Anda sendiri.');
        }

        $errorMessage = DB::transaction(function () use ($user, $validated, $newRole, $isRoleChanged) {
            // Langkah 2: admin aktif terakhir tidak boleh diturunkan role-nya.
            if ($isRoleChanged && $this->isLastActiveAdmin($user)) {
                return 'Tidak bisa mengubah role: ini satu-satunya admin aktif. Tambahkan admin lain terlebih dahulu.';
            }

            // Langkah 3: simpan nama & email.
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Langkah 4: atur PIN supervisor.
            if (! in_array($newRole, User::ROLES_WITH_SUPERVISOR_PIN, true)) {
                // CS & PT tidak boleh punya PIN (agar tidak bisa menyetujui void / diskon).
                $user->supervisor_pin = null;
            } elseif (filled($validated['supervisor_pin'] ?? null)) {
                // PIN hanya diganti jika diisi. Kosong = PIN lama tetap dipakai.
                $user->supervisor_pin = $validated['supervisor_pin'];
            }

            $oldRole = $user->primaryRole();
            $isPinChanged = $user->isDirty('supervisor_pin');
            $user->save();

            // Langkah 5: ganti role (selalu tepat SATU role).
            if ($isRoleChanged) {
                $user->syncRoles([$newRole]);
            }

            // Langkah 6: Log Aktivitas. PIN-nya sendiri tidak pernah dicatat, hanya "berubah / tidak".
            $properties = ActivityLog::changesOf($user);
            if ($isRoleChanged) {
                $properties['old']['role'] = $oldRole;
                $properties['new']['role'] = $newRole;
            }
            if ($isPinChanged) {
                $properties['supervisor_pin_changed'] = true;
            }

            ActivityLog::record(
                $isRoleChanged ? 'user.role_changed' : 'user.updated',
                $isRoleChanged
                    ? "Mengubah role {$user->name} dari {$oldRole} menjadi {$newRole}"
                    : "Mengubah data user {$user->name}",
                $user,
                $properties,
            );

            return null;
        });

        if ($errorMessage !== null) {
            return back()->with('error', $errorMessage);
        }

        return back()->with('success', "Data user {$user->name} berhasil diperbarui.");
    }

    /**
     * Reset password user staf (misalnya karena lupa password).
     */
    public function updatePassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => $request->validated('password')]);

        ActivityLog::record('user.password_reset', "Mereset password user {$user->name} ({$user->email})", $user);

        return back()->with('success', "Password {$user->name} berhasil direset.");
    }

    /**
     * Aktifkan / nonaktifkan akun staf. User yang nonaktif tidak bisa login,
     * langsung keluar di request berikutnya (middleware "user.active"),
     * dan PIN-nya ditolak untuk otorisasi void / diskon di kasir.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        // Langkah 1: admin tidak boleh menonaktifkan dirinya sendiri.
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak bisa menonaktifkan akun Anda sendiri.');
        }

        $errorMessage = DB::transaction(function () use ($user) {
            // Langkah 2: admin aktif terakhir tidak boleh dinonaktifkan.
            if ($user->is_active && $this->isLastActiveAdmin($user)) {
                return 'Tidak bisa menonaktifkan satu-satunya admin aktif.';
            }

            // Langkah 3: balik statusnya (aktif -> nonaktif, nonaktif -> aktif).
            $user->update(['is_active' => ! $user->is_active]);

            ActivityLog::record(
                $user->is_active ? 'user.activated' : 'user.deactivated',
                ($user->is_active ? 'Mengaktifkan kembali' : 'Menonaktifkan')." akun {$user->name} ({$user->email})",
                $user,
            );

            return null;
        });

        if ($errorMessage !== null) {
            return back()->with('error', $errorMessage);
        }

        $statusText = $user->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return back()->with('success', "Akun {$user->name} berhasil {$statusText}.");
    }

    /**
     * Apakah $user adalah satu-satunya admin yang masih aktif?
     *
     * Baris admin dikunci (lockForUpdate) selama transaksi, supaya dua admin yang
     * saling menurunkan role pada detik yang sama tidak membuat sistem tanpa admin.
     * Harus dipanggil DI DALAM DB::transaction.
     */
    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->is_active || ! $user->hasRole('admin')) {
            return false;
        }

        $activeAdminIds = User::role('admin')
            ->where('is_active', true)
            ->lockForUpdate()
            ->pluck('id');

        return $activeAdminIds->count() === 1 && $activeAdminIds->first() === $user->id;
    }

    /**
     * Ringkasan untuk kartu di atas tabel.
     *
     * @return array{total: int, inactive: int, per_role: array<string, int>}
     */
    private function summary(): array
    {
        $perRole = [];
        foreach (User::STAFF_ROLES as $role) {
            $perRole[$role] = User::role($role)->count();
        }

        return [
            'total' => User::count(),
            'inactive' => User::where('is_active', false)->count(),
            'per_role' => $perRole,
        ];
    }

    /**
     * Ubah 1 user menjadi data baris tabel. PIN & password TIDAK pernah dikirim ke browser.
     *
     * @return array<string, mixed>
     */
    private function formatRow(User $user, User $currentUser): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->primaryRole(),
            'is_active' => $user->is_active,
            'has_supervisor_pin' => $user->supervisor_pin !== null,
            'created_at' => $user->created_at?->translatedFormat('d M Y'),
            'is_current_user' => $user->is($currentUser),
        ];
    }
}
