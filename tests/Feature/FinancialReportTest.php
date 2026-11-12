<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\PosTransaction;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $supervisor;

    protected User $csUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
        $this->supervisor = User::where('email', 'supervisor@fitcore.com')->first();
        $this->csUser = User::where('email', 'cs@fitcore.com')->first();
    }

    public function test_guest_cannot_access_reports(): void
    {
        $response = $this->get('/reports');
        $response->assertRedirect('/login');

        $pdfResponse = $this->get('/reports/export/pdf');
        $pdfResponse->assertRedirect('/login');

        $excelResponse = $this->get('/reports/export/excel');
        $excelResponse->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_reports(): void
    {
        $response = $this->actingAs($this->csUser)->get('/reports');
        $response->assertForbidden();

        $pdfResponse = $this->actingAs($this->csUser)->get('/reports/export/pdf');
        $pdfResponse->assertForbidden();

        $excelResponse = $this->actingAs($this->csUser)->get('/reports/export/excel');
        $excelResponse->assertForbidden();
    }

    public function test_supervisor_and_admin_can_access_reports_dashboard(): void
    {
        $adminResponse = $this->actingAs($this->admin)->get('/reports');
        $adminResponse->assertOk();
        $adminResponse->assertInertia(fn (Assert $page) => $page->component('Reports/Index'));

        $supervisorResponse = $this->actingAs($this->supervisor)->get('/reports');
        $supervisorResponse->assertOk();
        $supervisorResponse->assertInertia(fn (Assert $page) => $page->component('Reports/Index'));
    }

    public function test_reports_dashboard_contains_all_aggregated_metrics(): void
    {
        // 1. Seed Members
        Member::factory()->create(['status' => 'active', 'gender' => 'male']);
        Member::factory()->create(['status' => 'active', 'gender' => 'female']);
        Member::factory()->create(['status' => 'inactive', 'gender' => 'male']);

        // 2. Seed Subscriptions
        $gymPackage = Package::factory()->create(['type' => 'gym', 'price' => 350000]);
        $ptPackage = Package::factory()->create(['type' => 'pt', 'price' => 1200000]);

        $member1 = Member::first();
        MemberSubscription::factory()->create([
            'member_id' => $member1->id,
            'package_id' => $gymPackage->id,
            'price_paid' => 350000,
            'status' => 'active',
            'created_at' => Carbon::now(),
        ]);

        MemberSubscription::factory()->create([
            'member_id' => $member1->id,
            'package_id' => $ptPackage->id,
            'price_paid' => 1200000,
            'status' => 'active',
            'created_at' => Carbon::now(),
        ]);

        // 3. Seed POS Transaction
        PosTransaction::factory()->create([
            'user_id' => $this->admin->id,
            'member_id' => $member1->id,
            'total_amount' => 85000,
            'status' => 'COMPLETED',
            'created_at' => Carbon::now(),
        ]);

        // 4. Seed Attendance Gate Scan
        Attendance::create([
            'member_id' => $member1->id,
            'rfid_uid' => $member1->rfid_uid,
            'status' => 'IN',
            'scanned_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/reports');

        $response->assertOk();
        $response->assertInertia(function (Assert $page) {
            $page->component('Reports/Index')
                ->has('kpis', fn (Assert $kpi) => $kpi
                    ->where('total_members', 3)
                    ->where('active_members', 2)
                    ->etc()
                )
                ->has('occupancy', fn (Assert $occ) => $occ
                    ->where('current_in_gym', 1)
                    ->where('gym_capacity', 100)
                    ->where('occupancy_rate', 1)
                    ->etc()
                )
                ->has('revenueSummary', fn (Assert $rev) => $rev
                    ->where('gym_subscriptions', 350000)
                    ->where('pt_packages', 1200000)
                    ->where('pos_retail', 85000)
                    ->where('total_revenue', 1635000)
                    ->etc()
                )
                ->has('genderRatio', fn (Assert $gen) => $gen
                    ->where('male_count', 2)
                    ->where('female_count', 1)
                    ->etc()
                )
                ->has('chartData')
                ->has('transactions');
        });
    }

    public function test_reports_can_be_filtered_by_category(): void
    {
        $gymPackage = Package::factory()->create(['type' => 'gym', 'price' => 300000]);
        $ptPackage = Package::factory()->create(['type' => 'pt', 'price' => 900000]);

        $member = Member::factory()->create(['status' => 'active']);

        MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $gymPackage->id,
            'price_paid' => 300000,
            'status' => 'active',
            'created_at' => Carbon::now(),
        ]);

        MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $ptPackage->id,
            'price_paid' => 900000,
            'status' => 'active',
            'created_at' => Carbon::now(),
        ]);

        PosTransaction::factory()->create([
            'user_id' => $this->admin->id,
            'total_amount' => 50000,
            'status' => 'COMPLETED',
            'created_at' => Carbon::now(),
        ]);

        // Filter only gym subscription
        $gymResponse = $this->actingAs($this->admin)->get('/reports?category=gym_subscription');
        $gymResponse->assertOk();
        $gymResponse->assertInertia(fn (Assert $page) => $page
            ->where('revenueSummary.total_revenue', 300000)
            ->where('filters.category', 'gym_subscription')
        );

        // Filter only POS retail
        $posResponse = $this->actingAs($this->admin)->get('/reports?category=pos_retail');
        $posResponse->assertOk();
        $posResponse->assertInertia(fn (Assert $page) => $page
            ->where('revenueSummary.total_revenue', 50000)
            ->where('filters.category', 'pos_retail')
        );
    }

    public function test_one_click_export_to_pdf_succeeds(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports/export/pdf');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Laporan-Keuangan-FitCore-', $response->headers->get('Content-Disposition'));
    }

    public function test_excel_export_writes_text_starting_with_equals_sign_as_plain_text_not_formula(): void
    {
        $package = Package::factory()->create(['type' => 'gym', 'price' => 300000]);
        $member = Member::factory()->create(['full_name' => '=1+1']);
        MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'price_paid' => 300000,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/reports/export/excel');
        $response->assertOk();

        // Simpan file hasil download ke file sementara, lalu baca kembali dengan PhpSpreadsheet.
        $temporaryFile = tempnam(sys_get_temp_dir(), 'report').'.xlsx';
        file_put_contents($temporaryFile, $response->streamedContent());
        $sheet = IOFactory::load($temporaryFile)->getActiveSheet();
        unlink($temporaryFile);

        // Cari baris transaksi milik member "=1+1" di kolom F (Member / Pelanggan).
        $customerCell = null;
        for ($row = 14; $row <= $sheet->getHighestRow(); $row++) {
            if ($sheet->getCell("F{$row}")->getValue() === '=1+1') {
                $customerCell = $sheet->getCell("F{$row}");
                break;
            }
        }

        $this->assertNotNull($customerCell, 'Nama member "=1+1" harus muncul apa adanya di kolom F.');
        $this->assertEquals(DataType::TYPE_STRING, $customerCell->getDataType(), 'Harus teks biasa, bukan formula.');

        // Kolom nominal tetap angka, supaya rumus SUM di baris TOTAL tetap bekerja.
        $amountCell = $sheet->getCell('I'.$customerCell->getRow());
        $this->assertEquals(DataType::TYPE_NUMERIC, $amountCell->getDataType());
    }

    public function test_one_click_export_to_excel_succeeds(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports/export/excel');

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }
}
