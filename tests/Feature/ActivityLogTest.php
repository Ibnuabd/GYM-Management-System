<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Issue #28: Log Aktivitas semua staf, hanya bisa dilihat admin.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $supervisor;

    protected User $cs;

    protected User $pt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
        $this->supervisor = User::where('email', 'supervisor@fitcore.com')->first();
        $this->cs = User::where('email', 'cs@fitcore.com')->first();
        $this->pt = User::where('email', 'pt@fitcore.com')->first();
    }

    // ===============================================================
    // Akses halaman
    // ===============================================================

    public function test_admin_can_open_activity_log_page(): void
    {
        $this->actingAs($this->admin)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ActivityLogs/Index')
                ->where('filters.start_date', Carbon::today()->subDays(6)->toDateString())
                ->where('filters.end_date', Carbon::today()->toDateString())
                ->has('areas')
            );
    }

    public function test_supervisor_cs_and_pt_cannot_open_activity_log_page(): void
    {
        foreach ([$this->supervisor, $this->cs, $this->pt] as $user) {
            $this->actingAs($user)->get(route('activity-logs.index'))->assertForbidden();
        }
    }

    public function test_there_is_no_route_to_edit_or_delete_a_log(): void
    {
        $log = ActivityLog::factory()->create();

        // URL untuk 1 log tidak ada sama sekali (404), jadi log tidak bisa diubah / dihapus.
        $this->actingAs($this->admin)->put('/activity-logs/'.$log->id)->assertNotFound();
        $this->actingAs($this->admin)->delete('/activity-logs/'.$log->id)->assertNotFound();
        $this->actingAs($this->admin)->delete('/activity-logs')->assertStatus(405);

        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    }

    // ===============================================================
    // Pencatatan: Login & Logout
    // ===============================================================

    public function test_successful_login_and_logout_are_logged(): void
    {
        $this->post('/login', ['email' => 'cs@fitcore.com', 'password' => 'password']);
        $this->post('/logout');

        $this->assertLogged('auth.login', $this->cs);
        $this->assertLogged('auth.logout', $this->cs);
    }

    public function test_failed_login_is_logged_without_user(): void
    {
        $this->post('/login', ['email' => 'cs@fitcore.com', 'password' => 'salah-password']);

        $log = ActivityLog::where('action', 'auth.login_failed')->sole();
        $this->assertNull($log->user_id);
        $this->assertSame('cs@fitcore.com', $log->properties['email']);
        $this->assertArrayNotHasKey('password', $log->properties);
    }

    // ===============================================================
    // Pencatatan: Member & Langganan
    // ===============================================================

    public function test_member_create_update_and_delete_are_logged(): void
    {
        $this->actingAs($this->cs)->post(route('members.store'), [
            'full_name' => 'Budi Santoso',
            'phone' => '081299998888',
            'gender' => 'male',
        ]);
        $member = Member::where('full_name', 'Budi Santoso')->sole();
        $this->assertLogged('member.created', $this->cs, $member);

        $this->actingAs($this->cs)->put(route('members.update', $member), [
            'full_name' => 'Budi Santoso',
            'phone' => '089900001111',
            'gender' => 'male',
            'status' => 'active',
        ]);
        $updateLog = $this->assertLogged('member.updated', $this->cs, $member);
        $this->assertSame('081299998888', $updateLog->properties['old']['phone']);
        $this->assertSame('089900001111', $updateLog->properties['new']['phone']);

        $this->actingAs($this->cs)->delete(route('members.destroy', $member));
        $this->assertLogged('member.deleted', $this->cs, $member);
    }

    public function test_portal_password_is_logged_without_the_password(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->cs)->put(route('members.portal-password.update', $member), [
            'password' => 'portal-rahasia-1',
            'password_confirmation' => 'portal-rahasia-1',
        ]);

        $log = $this->assertLogged('member.portal_password_set', $this->cs, $member);
        $this->assertStringNotContainsString('portal-rahasia-1', json_encode($log->toArray()));
    }

    public function test_subscription_created_and_cancelled_are_logged(): void
    {
        $member = Member::factory()->create();
        $package = Package::factory()->create(['type' => 'gym', 'session_count' => 0, 'duration_days' => 30]);

        $this->actingAs($this->cs)->post(route('subscriptions.store', $member), [
            'package_id' => $package->id,
            'start_date' => Carbon::today()->toDateString(),
        ]);
        $subscription = MemberSubscription::where('member_id', $member->id)->sole();
        $this->assertLogged('subscription.created', $this->cs, $subscription);

        $this->actingAs($this->cs)->post(route('subscriptions.cancel', $subscription));
        $cancelLog = $this->assertLogged('subscription.cancelled', $this->cs, $subscription);
        $this->assertSame('cancelled', $cancelLog->properties['new']['status']);
    }

    // ===============================================================
    // Pencatatan: Paket & Produk
    // ===============================================================

    public function test_package_create_and_price_change_are_logged(): void
    {
        $this->actingAs($this->supervisor)->post(route('packages.store'), [
            'name' => 'Gym Bulanan',
            'type' => 'gym',
            'duration_days' => 30,
            'session_count' => 0,
            'price' => 350000,
            'is_active' => true,
        ]);
        $package = Package::where('name', 'Gym Bulanan')->sole();
        $this->assertLogged('package.created', $this->supervisor, $package);

        $this->actingAs($this->supervisor)->put(route('packages.update', $package), [
            'name' => 'Gym Bulanan',
            'type' => 'gym',
            'duration_days' => 30,
            'session_count' => 0,
            'price' => 400000,
            'is_active' => true,
        ]);
        $updateLog = $this->assertLogged('package.updated', $this->supervisor, $package);
        $this->assertEquals(350000, $updateLog->properties['old']['price']);
        $this->assertEquals(400000, $updateLog->properties['new']['price']);
    }

    public function test_product_create_and_stock_change_are_logged(): void
    {
        $this->actingAs($this->admin)->post(route('products.store'), [
            'barcode' => '899000111222',
            'name' => 'Air Mineral',
            'category' => 'Minuman',
            'cost_price' => 3000,
            'selling_price' => 5000,
            'stock' => 10,
            'min_stock' => 2,
        ]);
        $product = Product::where('barcode', '899000111222')->sole();
        $this->assertLogged('product.created', $this->admin, $product);

        $this->actingAs($this->admin)->put(route('products.update', $product), [
            'barcode' => '899000111222',
            'name' => 'Air Mineral',
            'category' => 'Minuman',
            'cost_price' => 3000,
            'selling_price' => 5000,
            'stock' => 50,
            'min_stock' => 2,
        ]);
        $updateLog = $this->assertLogged('product.updated', $this->admin, $product);
        $this->assertSame(['stock' => 10], $updateLog->properties['old']);
        $this->assertSame(['stock' => 50], $updateLog->properties['new']);
    }

    // ===============================================================
    // Pencatatan: Kasir POS
    // ===============================================================

    public function test_pos_checkout_is_logged(): void
    {
        $product = Product::factory()->create(['selling_price' => 20000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($this->cs)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'cash_tendered' => 50000,
        ])->assertCreated();

        $transaction = PosTransaction::sole();
        $log = $this->assertLogged('pos.checkout', $this->cs, $transaction);
        $this->assertEquals(40000, $log->properties['total_amount']);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'pos.discount_approved']);
    }

    public function test_large_discount_approval_is_logged_with_supervisor_name(): void
    {
        $product = Product::factory()->create(['selling_price' => 100000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($this->cs)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'cash_tendered' => 100000,
            'discount_amount' => 50000,
            'supervisor_email' => 'supervisor@fitcore.com',
            'supervisor_pin' => '123456',
        ])->assertCreated();

        $log = $this->assertLogged('pos.discount_approved', $this->cs, PosTransaction::sole());
        $this->assertSame($this->supervisor->id, $log->properties['approved_by_id']);
        $this->assertArrayNotHasKey('supervisor_pin', $log->properties);
    }

    public function test_pos_void_is_logged_with_reason_and_supervisor(): void
    {
        $transaction = $this->completedTransaction();

        $this->actingAs($this->cs)->postJson(route('pos.void', $transaction->id), [
            'supervisor_email' => 'supervisor@fitcore.com',
            'supervisor_pin' => '123456',
            'void_reason' => 'Salah input barang',
        ])->assertOk();

        $log = $this->assertLogged('pos.voided', $this->cs, $transaction);
        $this->assertSame('Salah input barang', $log->properties['void_reason']);
        $this->assertSame($this->supervisor->id, $log->properties['approved_by_id']);
    }

    // ===============================================================
    // Pencatatan: Sesi PT & Laporan
    // ===============================================================

    public function test_pt_session_is_logged(): void
    {
        $member = Member::factory()->create(['full_name' => 'Rina PT']);
        $package = Package::factory()->create(['type' => 'pt', 'session_count' => 10, 'duration_days' => 60]);
        $subscription = MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'pt_user_id' => $this->pt->id,
            'total_sessions' => 10,
            'used_sessions' => 2,
            'status' => 'active',
            'start_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(50),
        ]);

        $this->actingAs($this->pt)->post(route('pt.sessions.store'), [
            'member_subscription_id' => $subscription->id,
            'duration_minutes' => 60,
            'muscle_focus' => 'Kaki',
            'workout_notes' => 'Squat 4x10',
        ])->assertSessionHas('success');

        $log = ActivityLog::where('action', 'pt.session_recorded')->sole();
        $this->assertSame($this->pt->id, $log->user_id);
        $this->assertSame('pt', $log->user_role);
        $this->assertSame(3, $log->properties['used_sessions']);
        $this->assertStringContainsString('Rina PT', $log->description);
    }

    public function test_report_exports_are_logged(): void
    {
        $this->actingAs($this->admin)->get(route('reports.export.pdf'))->assertOk();
        $this->actingAs($this->admin)->get(route('reports.export.excel'))->assertOk();

        $this->assertLogged('report.exported_pdf', $this->admin);
        $this->assertLogged('report.exported_excel', $this->admin);
    }

    // ===============================================================
    // Pencatatan: Manajemen User (#27)
    // ===============================================================

    public function test_user_management_actions_are_logged_without_secrets(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Kasir Baru',
            'email' => 'kasir.baru@fitcore.com',
            'role' => 'cs',
            'password' => 'rahasia-user-1',
            'password_confirmation' => 'rahasia-user-1',
        ]);
        $newUser = User::where('email', 'kasir.baru@fitcore.com')->sole();
        $this->assertLogged('user.created', $this->admin, $newUser);

        $this->actingAs($this->admin)->put(route('users.update', $newUser), [
            'name' => 'Kasir Baru',
            'email' => 'kasir.baru@fitcore.com',
            'role' => 'supervisor',
            'supervisor_pin' => '987654',
        ]);
        $roleLog = $this->assertLogged('user.role_changed', $this->admin, $newUser);
        $this->assertSame('cs', $roleLog->properties['old']['role']);
        $this->assertSame('supervisor', $roleLog->properties['new']['role']);
        $this->assertTrue($roleLog->properties['supervisor_pin_changed']);

        $this->actingAs($this->admin)->put(route('users.password.update', $newUser), [
            'password' => 'rahasia-user-2',
            'password_confirmation' => 'rahasia-user-2',
        ]);
        $this->assertLogged('user.password_reset', $this->admin, $newUser);

        $this->actingAs($this->admin)->post(route('users.toggle-active', $newUser));
        $this->assertLogged('user.deactivated', $this->admin, $newUser);

        // Tidak ada password / PIN yang tersimpan di log mana pun
        $allLogs = json_encode(ActivityLog::all()->toArray());
        foreach (['rahasia-user-1', 'rahasia-user-2', '987654'] as $secret) {
            $this->assertStringNotContainsString($secret, $allLogs);
        }
    }

    public function test_secret_keys_are_removed_from_properties(): void
    {
        $this->actingAs($this->admin);

        $log = ActivityLog::record('member.updated', 'Uji', null, [
            'password' => 'x',
            'supervisor_pin' => '123456',
            'old' => ['phone' => '1', 'photo' => 'data:image/png;base64,AAA'],
            'new' => ['phone' => '2'],
        ]);

        $this->assertSame(['old' => ['phone' => '1'], 'new' => ['phone' => '2']], $log->properties);
    }

    // ===============================================================
    // Aksi gagal tidak dicatat
    // ===============================================================

    public function test_failed_actions_write_no_log(): void
    {
        // Validasi gagal
        $this->actingAs($this->cs)->post(route('members.store'), ['full_name' => '']);

        // PIN supervisor salah saat void
        $transaction = $this->completedTransaction();
        $this->actingAs($this->cs)->postJson(route('pos.void', $transaction->id), [
            'supervisor_email' => 'supervisor@fitcore.com',
            'supervisor_pin' => '000000',
            'void_reason' => 'Coba',
        ])->assertStatus(422);

        // Checkout dibatalkan (stok kurang) -> rollback, log ikut batal
        $product = Product::factory()->create(['selling_price' => 10000, 'stock' => 1, 'is_active' => true]);
        $this->actingAs($this->cs)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
            'payment_method' => 'cash',
            'cash_tendered' => 100000,
        ])->assertStatus(422);

        $this->assertSame(0, ActivityLog::whereIn('action', ['member.created', 'pos.voided', 'pos.checkout'])->count());
    }

    // ===============================================================
    // Filter, ringkasan, dan paginasi
    // ===============================================================

    public function test_filters_by_user_role_area_and_search(): void
    {
        $this->makeLog($this->cs, 'cs', 'member.created', 'Mendaftarkan member baru Budi');
        $this->makeLog($this->pt, 'pt', 'pt.session_recorded', 'Mencatat sesi PT untuk Rina');
        $this->makeLog($this->supervisor, 'supervisor', 'pos.voided', 'VOID transaksi TRX-1');

        $this->assertFilterReturns(['user_id' => $this->cs->id], 'member.created');
        $this->assertFilterReturns(['role' => 'pt'], 'pt.session_recorded');
        $this->assertFilterReturns(['area' => 'pos'], 'pos.voided');
        $this->assertFilterReturns(['search' => 'Budi'], 'member.created');
    }

    public function test_date_range_filter_and_summary(): void
    {
        $this->makeLog($this->cs, 'cs', 'member.created', 'Hari ini');
        $this->makeLog(null, null, 'auth.login_failed', 'Login gagal');
        $this->makeLog($this->supervisor, 'supervisor', 'pos.voided', 'VOID');
        $this->makeLog($this->cs, 'cs', 'member.updated', 'Sebulan lalu', Carbon::today()->subDays(40));

        $this->actingAs($this->admin)->get(route('activity-logs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 3)
                ->where('summary.total_activities', 3)
                ->where('summary.active_users', 2)
                ->where('summary.failed_logins', 1)
                ->where('summary.pos_voids', 1)
            );

        $this->actingAs($this->admin)->get(route('activity-logs.index', [
            'start_date' => Carbon::today()->subDays(45)->toDateString(),
            'end_date' => Carbon::today()->subDays(30)->toDateString(),
        ]))->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.description', 'Sebulan lalu')
        );
    }

    public function test_pagination_keeps_filters(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->makeLog($this->cs, 'cs', 'member.updated', "Ubah member {$i}");
        }

        $this->actingAs($this->admin)->get(route('activity-logs.index', ['role' => 'cs']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 25)
                ->where('logs.total', 30)
                ->where('logs.next_page_url', fn ($url) => str_contains($url, 'role=cs') && str_contains($url, 'page=2'))
            );
    }

    public function test_invalid_filters_redirect_back_with_errors(): void
    {
        $invalidFilters = [
            'start_date' => ['start_date' => 'bukan-tanggal'],
            'end_date' => ['start_date' => '2026-03-31', 'end_date' => '2026-01-01'],
            'role' => ['role' => 'superadmin'],
            'area' => ['area' => 'hacking'],
            'user_id' => ['user_id' => 999999],
        ];

        foreach ($invalidFilters as $field => $query) {
            $this->actingAs($this->admin)->get(route('activity-logs.index', $query))
                ->assertRedirect(route('activity-logs.index'))
                ->assertSessionHasErrors($field);
        }

        // Lebih dari 31 hari
        $this->actingAs($this->admin)->get(route('activity-logs.index', ['start_date' => '2026-01-01', 'end_date' => '2026-03-01']))
            ->assertRedirect(route('activity-logs.index'))
            ->assertSessionHasErrors('start_date');
    }

    // ===============================================================
    // Helper
    // ===============================================================

    /**
     * Pastikan ada TEPAT SATU log dengan action ini, dari user ini, (opsional) untuk data ini.
     */
    private function assertLogged(string $action, User $actor, ?object $subject = null): ActivityLog
    {
        $log = ActivityLog::where('action', $action)->sole();

        $this->assertSame($actor->id, $log->user_id, "Pelaku log {$action} salah.");
        $this->assertSame($actor->getRoleNames()->first(), $log->user_role);

        if ($subject !== null) {
            $this->assertSame($subject->getMorphClass(), $log->subject_type);
            $this->assertEquals($subject->getKey(), $log->subject_id);
        }

        return $log;
    }

    /**
     * Pastikan filter $query hanya mengembalikan 1 log dengan action $expectedAction.
     *
     * @param  array<string, mixed>  $query
     */
    private function assertFilterReturns(array $query, string $expectedAction): void
    {
        $this->actingAs($this->admin)->get(route('activity-logs.index', $query))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.action', $expectedAction)
            );
    }

    private function makeLog(?User $user, ?string $role, string $action, string $description, ?Carbon $createdAt = null): ActivityLog
    {
        $log = ActivityLog::factory()->create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_role' => $role,
            'action' => $action,
            'description' => $description,
        ]);

        // created_at diatur manual agar bisa menguji filter tanggal
        $log->forceFill(['created_at' => $createdAt ?? Carbon::now()])->save();

        return $log;
    }

    private function completedTransaction(): PosTransaction
    {
        return PosTransaction::create([
            'invoice_number' => PosTransaction::generateInvoiceNumber(),
            'user_id' => $this->cs->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'status' => 'COMPLETED',
        ]);
    }
}
