<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Memastikan login staf, login portal member, dan PIN supervisor (void POS)
 * tidak bisa ditebak berkali-kali tanpa batas.
 */
class LoginAndVoidRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected User $cs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->cs = User::where('email', 'cs@fitcore.com')->first();
    }

    public function test_staff_login_is_locked_after_five_failed_attempts(): void
    {
        $this->failStaffLogin(times: 5);

        // Percobaan ke-6 memakai password BENAR, tapi tetap ditolak karena sedang dikunci.
        $response = $this->post(route('login'), [
            'email' => 'admin@fitcore.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_successful_staff_login_resets_the_failed_attempt_counter(): void
    {
        $this->failStaffLogin(times: 4);

        $this->post(route('login'), ['email' => 'admin@fitcore.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->post(route('logout'));

        // Hitungan sudah kembali 0, jadi 4 kali gagal lagi belum membuat akun terkunci.
        $this->failStaffLogin(times: 4);

        $this->post(route('login'), ['email' => 'admin@fitcore.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_member_portal_login_is_locked_after_five_failed_attempts(): void
    {
        Member::factory()->create([
            'email' => 'member.ratelimit@fitcore.com',
            'password' => 'password-benar',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('portal.login'), [
                'login' => 'member.ratelimit@fitcore.com',
                'password' => 'password-salah',
            ]);
        }

        $response = $this->post(route('portal.login'), [
            'login' => 'member.ratelimit@fitcore.com',
            'password' => 'password-benar',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('login'));
        $this->assertFalse(Auth::guard('member')->check());
    }

    public function test_supervisor_pin_is_locked_for_the_cashier_after_five_wrong_attempts(): void
    {
        $transaction = $this->createCompletedTransaction();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($this->cs)
                ->postJson(route('pos.void', $transaction), $this->voidPayload(pin: '000000'))
                ->assertStatus(422);
        }

        // Percobaan ke-6 memakai PIN BENAR, tapi tetap ditolak karena sedang dikunci.
        $response = $this->actingAs($this->cs)
            ->postJson(route('pos.void', $transaction), $this->voidPayload(pin: '123456'));

        $response->assertStatus(429);
        $response->assertJson(['success' => false]);
        $this->assertEquals('COMPLETED', $transaction->fresh()->status);
    }

    public function test_supervisor_cannot_be_identified_by_name_only_by_email(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->actingAs($this->cs)->postJson(route('pos.void', $transaction), [
            'supervisor_email' => 'Bambang Supriyadi (Supervisor)',
            'supervisor_pin' => '123456',
            'void_reason' => 'Customer batalkan pembelian',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('supervisor_email');
        $this->assertEquals('COMPLETED', $transaction->fresh()->status);
    }

    /**
     * Kirim login staf dengan password salah sebanyak $times kali.
     */
    private function failStaffLogin(int $times): void
    {
        for ($attempt = 1; $attempt <= $times; $attempt++) {
            $this->post(route('login'), [
                'email' => 'admin@fitcore.com',
                'password' => 'password-salah',
            ])->assertSessionHasErrors('email');
        }
    }

    /**
     * Buat transaksi POS berstatus COMPLETED milik kasir CS.
     */
    private function createCompletedTransaction(): PosTransaction
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

    /**
     * Data form void dengan PIN tertentu (PIN supervisor seed yang benar adalah 123456).
     *
     * @return array<string, string>
     */
    private function voidPayload(string $pin): array
    {
        return [
            'supervisor_email' => 'supervisor@fitcore.com',
            'supervisor_pin' => $pin,
            'void_reason' => 'Customer batalkan pembelian',
        ];
    }
}
