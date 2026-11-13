<?php

namespace Tests\Feature;

use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Issue #27: halaman Manajemen User (RBAC) untuk akun staf.
 */
class UserManagementTest extends TestCase
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

    // ---------------------------------------------------------------
    // Akses
    // ---------------------------------------------------------------

    public function test_admin_can_open_user_management_page(): void
    {
        $this->actingAs($this->admin)->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data', 4)
                ->where('summary.total', 4)
                ->where('summary.per_role.admin', 1)
                ->where('summary.inactive', 0)
                ->where('roles', User::STAFF_ROLES)
            );
    }

    public function test_page_never_sends_password_or_pin_to_the_browser(): void
    {
        $this->actingAs($this->admin)->get(route('users.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('users.data.0.password')
                ->missing('users.data.0.supervisor_pin')
                ->has('users.data.0.has_supervisor_pin')
            );
    }

    public function test_non_admin_roles_cannot_use_any_user_management_route(): void
    {
        foreach ([$this->supervisor, $this->cs, $this->pt] as $user) {
            $this->actingAs($user)->get(route('users.index'))->assertForbidden();
            $this->actingAs($user)->post(route('users.store'), $this->validNewUser())->assertForbidden();
            $this->actingAs($user)->put(route('users.update', $this->cs), ['name' => 'X', 'email' => 'x@fitcore.com', 'role' => 'admin'])->assertForbidden();
            $this->actingAs($user)->put(route('users.password.update', $this->cs), ['password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru'])->assertForbidden();
            $this->actingAs($user)->post(route('users.toggle-active', $this->cs))->assertForbidden();
        }

        $this->assertDatabaseMissing('users', ['email' => 'kasir.baru@fitcore.com']);
    }

    // ---------------------------------------------------------------
    // Filter
    // ---------------------------------------------------------------

    public function test_search_and_role_filter(): void
    {
        $this->actingAs($this->admin)->get(route('users.index', ['search' => 'Siti']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.email', 'cs@fitcore.com')
            );

        $this->actingAs($this->admin)->get(route('users.index', ['role' => 'pt']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.role', 'pt')
                ->where('filters.role', 'pt')
            );
    }

    // ---------------------------------------------------------------
    // Tambah user
    // ---------------------------------------------------------------

    public function test_admin_can_create_user_with_role(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), $this->validNewUser())
            ->assertRedirect()
            ->assertSessionHas('success');

        $newUser = User::where('email', 'kasir.baru@fitcore.com')->firstOrFail();
        $this->assertTrue($newUser->hasRole('cs'));
        $this->assertTrue($newUser->is_active);
        $this->assertTrue(Hash::check('rahasia123', $newUser->password));
        $this->assertNull($newUser->supervisor_pin);
    }

    public function test_new_supervisor_gets_hashed_pin_but_new_cs_pin_is_ignored(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), $this->validNewUser([
            'email' => 'spv.baru@fitcore.com',
            'role' => 'supervisor',
            'supervisor_pin' => '654321',
        ]));

        $newSupervisor = User::where('email', 'spv.baru@fitcore.com')->firstOrFail();
        $this->assertTrue(Hash::check('654321', $newSupervisor->supervisor_pin));

        // PIN untuk role CS diabaikan (CS tidak boleh menyetujui void / diskon)
        $this->actingAs($this->admin)->post(route('users.store'), $this->validNewUser([
            'email' => 'cs.baru@fitcore.com',
            'role' => 'cs',
            'supervisor_pin' => '111111',
        ]));

        $this->assertNull(User::where('email', 'cs.baru@fitcore.com')->firstOrFail()->supervisor_pin);
    }

    public function test_create_user_validation_errors(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), $this->validNewUser(['email' => 'CS@fitcore.com']))
            ->assertSessionHasErrors('email');

        $this->actingAs($this->admin)->post(route('users.store'), $this->validNewUser(['role' => 'superadmin']))
            ->assertSessionHasErrors('role');

        $this->actingAs($this->admin)->post(route('users.store'), $this->validNewUser(['password' => 'pendek', 'password_confirmation' => 'pendek']))
            ->assertSessionHasErrors('password');

        $this->actingAs($this->admin)->post(route('users.store'), $this->validNewUser(['role' => 'supervisor', 'supervisor_pin' => '12ab']))
            ->assertSessionHasErrors('supervisor_pin');

        $this->assertDatabaseMissing('users', ['email' => 'kasir.baru@fitcore.com']);
    }

    // ---------------------------------------------------------------
    // Edit user
    // ---------------------------------------------------------------

    public function test_admin_can_edit_name_email_and_role(): void
    {
        $this->actingAs($this->admin)->put(route('users.update', $this->cs), [
            'name' => 'Siti Supervisor',
            'email' => 'siti.spv@fitcore.com',
            'role' => 'supervisor',
            'supervisor_pin' => '222333',
        ])->assertSessionHas('success');

        $this->cs->refresh();
        $this->assertSame('Siti Supervisor', $this->cs->name);
        $this->assertSame('siti.spv@fitcore.com', $this->cs->email);
        $this->assertSame(['supervisor'], $this->cs->getRoleNames()->all());
        $this->assertTrue(Hash::check('222333', $this->cs->supervisor_pin));
    }

    public function test_empty_pin_on_edit_keeps_old_pin(): void
    {
        $this->actingAs($this->admin)->put(route('users.update', $this->supervisor), [
            'name' => 'Bambang Baru',
            'email' => 'supervisor@fitcore.com',
            'role' => 'supervisor',
            'supervisor_pin' => '',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('123456', $this->supervisor->fresh()->supervisor_pin));
    }

    public function test_changing_role_to_cs_clears_supervisor_pin(): void
    {
        $this->actingAs($this->admin)->put(route('users.update', $this->supervisor), [
            'name' => $this->supervisor->name,
            'email' => $this->supervisor->email,
            'role' => 'cs',
        ])->assertSessionHas('success');

        $this->supervisor->refresh();
        $this->assertNull($this->supervisor->supervisor_pin);
        $this->assertSame(['cs'], $this->supervisor->getRoleNames()->all());
    }

    // ---------------------------------------------------------------
    // Reset password
    // ---------------------------------------------------------------

    public function test_admin_can_reset_user_password(): void
    {
        $this->actingAs($this->admin)->put(route('users.password.update', $this->pt), [
            'password' => 'passwordbaru99',
            'password_confirmation' => 'passwordbaru99',
        ])->assertSessionHas('success', "Password {$this->pt->name} berhasil direset.");

        $this->assertTrue(Hash::check('passwordbaru99', $this->pt->fresh()->password));
    }

    public function test_reset_password_requires_confirmation(): void
    {
        $this->actingAs($this->admin)->put(route('users.password.update', $this->pt), [
            'password' => 'passwordbaru99',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $this->pt->fresh()->password));
    }

    // ---------------------------------------------------------------
    // Aktif / nonaktif
    // ---------------------------------------------------------------

    public function test_admin_can_deactivate_and_reactivate_user(): void
    {
        $this->actingAs($this->admin)->post(route('users.toggle-active', $this->cs))->assertSessionHas('success');
        $this->assertFalse($this->cs->fresh()->is_active);

        $this->actingAs($this->admin)->post(route('users.toggle-active', $this->cs))->assertSessionHas('success');
        $this->assertTrue($this->cs->fresh()->is_active);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->cs->update(['is_active' => false]);

        $this->post('/login', ['email' => 'cs@fitcore.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi admin.']);

        $this->assertGuest('web');
    }

    public function test_logged_in_user_is_logged_out_after_being_deactivated(): void
    {
        $this->actingAs($this->cs)->get(route('dashboard'))->assertOk();

        $this->cs->update(['is_active' => false]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_inactive_supervisor_pin_is_rejected_for_pos_void(): void
    {
        $this->supervisor->update(['is_active' => false]);

        $transaction = PosTransaction::create([
            'invoice_number' => PosTransaction::generateInvoiceNumber(),
            'user_id' => $this->cs->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'status' => 'COMPLETED',
        ]);

        $this->actingAs($this->cs)->postJson(route('pos.void', $transaction->id), [
            'supervisor_email' => 'supervisor@fitcore.com',
            'supervisor_pin' => '123456',
            'void_reason' => 'Salah input barang',
        ])->assertForbidden()->assertJson(['success' => false]);

        $this->assertSame('COMPLETED', $transaction->fresh()->status);
    }

    // ---------------------------------------------------------------
    // Aturan pengaman
    // ---------------------------------------------------------------

    public function test_admin_cannot_deactivate_or_change_own_role(): void
    {
        $this->actingAs($this->admin)->post(route('users.toggle-active', $this->admin))
            ->assertSessionHas('error', 'Anda tidak bisa menonaktifkan akun Anda sendiri.');

        $this->actingAs($this->admin)->put(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'cs',
        ])->assertSessionHas('error', 'Anda tidak bisa mengubah role akun Anda sendiri.');

        $this->admin->refresh();
        $this->assertTrue($this->admin->is_active);
        $this->assertTrue($this->admin->hasRole('admin'));
    }

    public function test_admin_can_still_edit_own_name_without_changing_role(): void
    {
        $this->actingAs($this->admin)->put(route('users.update', $this->admin), [
            'name' => 'Admin Baru',
            'email' => $this->admin->email,
            'role' => 'admin',
        ])->assertSessionHas('success');

        $this->assertSame('Admin Baru', $this->admin->fresh()->name);
    }

    public function test_last_active_admin_cannot_be_deactivated_or_demoted(): void
    {
        // Simulasi: supervisor diberi izin manage_users, lalu mencoba mengganggu satu-satunya admin.
        Role::findByName('supervisor', 'web')->givePermissionTo('manage_users');

        $this->actingAs($this->supervisor)->post(route('users.toggle-active', $this->admin))
            ->assertSessionHas('error', 'Tidak bisa menonaktifkan satu-satunya admin aktif.');

        $this->actingAs($this->supervisor)->put(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'pt',
        ])->assertSessionHas('error');

        $this->admin->refresh();
        $this->assertTrue($this->admin->is_active);
        $this->assertTrue($this->admin->hasRole('admin'));
    }

    public function test_admin_can_be_deactivated_when_another_active_admin_exists(): void
    {
        $secondAdmin = User::factory()->create();
        $secondAdmin->assignRole('admin');

        $this->actingAs($this->admin)->post(route('users.toggle-active', $secondAdmin))->assertSessionHas('success');

        $this->assertFalse($secondAdmin->fresh()->is_active);
    }

    public function test_there_is_no_route_to_delete_a_user(): void
    {
        $this->actingAs($this->admin)->delete('/users/'.$this->cs->id)->assertStatus(405);

        $this->assertDatabaseHas('users', ['id' => $this->cs->id]);
    }

    /**
     * Data form "Tambah User" yang valid. $overrides untuk mengganti sebagian field.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validNewUser(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kasir Baru',
            'email' => 'kasir.baru@fitcore.com',
            'role' => 'cs',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }
}
