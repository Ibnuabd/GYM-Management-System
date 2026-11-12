<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\PosTransaction;
use App\Models\PtSessionLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display the operational analytics and financial reporting dashboard.
     */
    public function index(ReportFilterRequest $request): Response
    {
        $filters = $this->resolveFilters($request);
        $data = $this->aggregateReportData($filters['start_date'], $filters['end_date'], $filters['category']);

        return Inertia::render('Reports/Index', [
            'filters' => $filters,
            'kpis' => $data['kpis'],
            'occupancy' => $data['occupancy'],
            'genderRatio' => $data['gender_ratio'],
            'revenueSummary' => $data['revenue_summary'],
            'chartData' => $data['chart_data'],
            'transactions' => $data['transactions']->take(25)->values(),
        ]);
    }

    /**
     * Export filtered financial and transaction report to PDF format using DomPDF.
     */
    public function exportPdf(ReportFilterRequest $request): HttpResponse
    {
        $filters = $this->resolveFilters($request);
        $data = $this->aggregateReportData($filters['start_date'], $filters['end_date'], $filters['category']);

        $pdf = Pdf::loadView('reports.pdf', [
            'generated_at' => Carbon::now()->translatedFormat('d F Y, H:i:s'),
            'generated_by' => $request->user()->name ?? 'Administrator',
            'filters' => $filters,
            'kpis' => $data['kpis'],
            'occupancy' => $data['occupancy'],
            'revenueSummary' => $data['revenue_summary'],
            'transactions' => $data['transactions'],
        ]);

        $pdf->setPaper('a4', 'portrait');

        $fileName = 'Laporan-Keuangan-FitCore-'.Carbon::now()->format('Ymd-His').'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Export filtered transaction logs and financial audit breakdown to Excel (.xlsx) format.
     */
    public function exportExcel(ReportFilterRequest $request): StreamedResponse
    {
        $filters = $this->resolveFilters($request);
        $data = $this->aggregateReportData($filters['start_date'], $filters['end_date'], $filters['category']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Finansial');

        // Document Title Header
        $sheet->setCellValue('A1', 'FITCORE GYM MANAGEMENT SYSTEM');
        $sheet->setCellValue('A2', 'LAPORAN REKAPITULASI KEUANGAN & OPERASIONAL');
        $this->setTextCell($sheet, 'A3', 'Periode: '.$filters['start_date']->translatedFormat('d M Y').' s/d '.$filters['end_date']->translatedFormat('d M Y').' | Kategori: '.strtoupper($filters['category']));
        $this->setTextCell($sheet, 'A4', 'Dicetak: '.Carbon::now()->translatedFormat('d F Y, H:i').' | Oleh: '.($request->user()->name ?? 'Admin'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A4')->getFont()->setSize(10)->getColor()->setRGB('555555');

        // Summary KPI Section
        $sheet->setCellValue('A6', 'RINGKASAN PENDAPATAN');
        $sheet->getStyle('A6')->getFont()->setBold(true);

        $sheet->setCellValue('A7', 'Pendapatan Langganan Gym');
        $sheet->setCellValue('B7', $data['revenue_summary']['gym_subscriptions']);
        $sheet->setCellValue('A8', 'Pendapatan Paket PT');
        $sheet->setCellValue('B8', $data['revenue_summary']['pt_packages']);
        $sheet->setCellValue('A9', 'Pendapatan Paket Combo');
        $sheet->setCellValue('B9', $data['revenue_summary']['combo_packages']);
        $sheet->setCellValue('A10', 'Pendapatan Ritel POS');
        $sheet->setCellValue('B10', $data['revenue_summary']['pos_retail']);
        $sheet->setCellValue('A11', 'TOTAL PENDAPATAN');
        $sheet->setCellValue('B11', $data['revenue_summary']['total_revenue']);

        $sheet->getStyle('B7:B11')->getNumberFormat()->setFormatCode('"Rp "#,##0');
        $sheet->getStyle('A11:B11')->getFont()->setBold(true);

        // Transaction Table Header Row
        $row = 13;
        $headers = [
            'A' => 'No',
            'B' => 'Tanggal & Jam',
            'C' => 'No. Referensi',
            'D' => 'Kategori Pendapatan',
            'E' => 'Deskripsi Transaksi',
            'F' => 'Member / Pelanggan',
            'G' => 'Metode Bayar',
            'H' => 'Petugas / Kasir',
            'I' => 'Nominal (Rp)',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        // Header Styling
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '121C2C'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(24);

        // Fill Data Rows
        $startDataRow = $row + 1;
        $currRow = $startDataRow;
        $index = 1;

        // Kolom teks (nama member, deskripsi, dll.) ditulis dengan setTextCell() agar
        // teks yang diawali "=" tidak dijalankan sebagai formula oleh Excel.
        // Kolom nominal (I) tetap angka supaya rumus SUM di baris TOTAL bekerja.
        foreach ($data['transactions'] as $trx) {
            $sheet->setCellValue("A{$currRow}", $index++);
            $this->setTextCell($sheet, "B{$currRow}", $trx['date_formatted']);
            $this->setTextCell($sheet, "C{$currRow}", $trx['reference']);
            $this->setTextCell($sheet, "D{$currRow}", $trx['category_label']);
            $this->setTextCell($sheet, "E{$currRow}", $trx['description']);
            $this->setTextCell($sheet, "F{$currRow}", $trx['customer']);
            $this->setTextCell($sheet, "G{$currRow}", $trx['payment_method']);
            $this->setTextCell($sheet, "H{$currRow}", $trx['staff']);
            $sheet->setCellValue("I{$currRow}", $trx['amount']);

            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("A{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Alternating row background for readability
            if ($index % 2 === 0) {
                $sheet->getStyle("A{$currRow}:I{$currRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $currRow++;
        }

        // Total Row at Bottom
        if ($currRow > $startDataRow) {
            $sheet->setCellValue("A{$currRow}", 'TOTAL');
            $sheet->mergeCells("A{$currRow}:H{$currRow}");
            $sheet->setCellValue("I{$currRow}", "=SUM(I{$startDataRow}:I".($currRow - 1).')');

            $sheet->getStyle("A{$currRow}:I{$currRow}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ]);
            $sheet->getStyle("I{$currRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
            $sheet->getStyle("A{$currRow}:I{$currRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Laporan-Finansial-FitCore-'.Carbon::now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Resolve date range and category filters from request.
     *
     * @return array{start_date: Carbon, end_date: Carbon, category: string, interval: string}
     */
    protected function resolveFilters(ReportFilterRequest $request): array
    {
        $interval = $request->query('interval', 'month');
        $now = Carbon::now();

        if ($interval === 'today') {
            $startDate = $now->copy()->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($interval === 'week') {
            $startDate = $now->copy()->subDays(6)->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($interval === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfDay();
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->query('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->query('end_date'))->endOfDay();
            $interval = 'custom';
        } else {
            // Default current month (last 30 days)
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfDay();
            $interval = 'month';
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category' => $request->query('category', 'all'),
            'interval' => $interval,
        ];
    }

    /**
     * Aggregate all operational, occupancy, and multi-channel financial metrics.
     *
     * @return array<string, mixed>
     */
    protected function aggregateReportData(Carbon $startDate, Carbon $endDate, string $category): array
    {
        // 1. Core KPIs
        $totalMembers = Member::count();
        $activeMembers = Member::where('status', 'active')->count();

        // Real-time Gate Occupancy
        $todayIns = Attendance::today()->where('status', 'IN')->count();
        $todayOuts = Attendance::today()->where('status', 'OUT')->count();
        $currentOccupancy = max(0, $todayIns - $todayOuts);
        $gymCapacity = 100;
        $occupancyRate = min(100, (int) round(($currentOccupancy / $gymCapacity) * 100));

        // PT Sessions Completed
        $ptSessionsCompleted = PtSessionLog::whereBetween('session_date', [$startDate, $endDate])->count();
        $totalPtCompletedAllTime = PtSessionLog::count();

        // 2. Gender Ratio
        $maleMembers = Member::where('gender', 'male')->count();
        $femaleMembers = Member::where('gender', 'female')->count();
        $totalGender = max(1, $maleMembers + $femaleMembers);

        // 3. Multi-channel Revenue Calculation
        $subscriptionQuery = MemberSubscription::countedAsRevenue()->whereBetween('created_at', [$startDate, $endDate])
            ->with(['package', 'member']);

        $gymRevenue = (float) MemberSubscription::countedAsRevenue()->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('package', fn ($q) => $q->where('type', 'gym'))
            ->sum('price_paid');

        $ptRevenue = (float) MemberSubscription::countedAsRevenue()->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('package', fn ($q) => $q->where('type', 'pt'))
            ->sum('price_paid');

        $comboRevenue = (float) MemberSubscription::countedAsRevenue()->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('package', fn ($q) => $q->where('type', 'combo'))
            ->sum('price_paid');

        $posQuery = PosTransaction::completed()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['cashier', 'member', 'items.product']);

        $posRevenue = (float) PosTransaction::completed()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        // Total calculated according to category filter
        $totalRevenue = match ($category) {
            'gym_subscription' => $gymRevenue,
            'pt_package' => $ptRevenue,
            'combo' => $comboRevenue,
            'pos_retail' => $posRevenue,
            default => $gymRevenue + $ptRevenue + $comboRevenue + $posRevenue,
        };

        // 4. Unified Transactions Collection for Table & Export
        $transactions = collect();

        if (in_array($category, ['all', 'gym_subscription', 'pt_package', 'combo'])) {
            $subs = $subscriptionQuery->get();
            foreach ($subs as $sub) {
                $pkgType = $sub->package?->type;
                if ($category !== 'all' && $pkgType !== str_replace(['gym_subscription', 'pt_package'], ['gym', 'pt'], $category)) {
                    continue;
                }

                $catLabel = match ($pkgType) {
                    'gym' => 'Langganan Gym',
                    'pt' => 'Paket PT',
                    'combo' => 'Paket Combo',
                    default => 'Langganan',
                };

                $transactions->push([
                    'id' => 'SUB-'.$sub->id,
                    'created_at' => $sub->created_at,
                    'date_formatted' => $sub->created_at->translatedFormat('d M Y, H:i'),
                    'reference' => 'SUB-'.str_pad((string) $sub->id, 5, '0', STR_PAD_LEFT),
                    'category_key' => $pkgType,
                    'category_label' => $catLabel,
                    'description' => $sub->package?->name ?? 'Paket Gym',
                    'customer' => $sub->member?->full_name ?? 'Member',
                    'staff' => $sub->personalTrainer?->name ?? 'Frontliner',
                    'payment_method' => 'Cash/Transfer',
                    'amount' => (float) $sub->price_paid,
                ]);
            }
        }

        if (in_array($category, ['all', 'pos_retail'])) {
            $posTrxs = $posQuery->get();
            foreach ($posTrxs as $trx) {
                $itemNames = $trx->items->map(fn ($item) => $item->product?->name ?? 'Produk Ritel')->take(2)->implode(', ');
                if ($trx->items->count() > 2) {
                    $itemNames .= ' +'.($trx->items->count() - 2).' lainnya';
                }

                $transactions->push([
                    'id' => 'POS-'.$trx->id,
                    'created_at' => $trx->created_at,
                    'date_formatted' => $trx->created_at->translatedFormat('d M Y, H:i'),
                    'reference' => $trx->invoice_number,
                    'category_key' => 'pos_retail',
                    'category_label' => 'POS Ritel',
                    'description' => $itemNames ?: 'Transaksi Kasir Ritel',
                    'customer' => $trx->member?->full_name ?? 'Pelanggan Umum',
                    'staff' => $trx->cashier?->name ?? 'Kasir',
                    'payment_method' => strtoupper($trx->payment_method),
                    'amount' => (float) $trx->total_amount,
                ]);
            }
        }

        // Sort descending by date
        $transactions = $transactions->sortByDesc('created_at')->values();

        // 5. Daily Trend Breakdown for Charting
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        $chartData = [];

        foreach ($period as $date) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $dayGym = (float) MemberSubscription::countedAsRevenue()->whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereHas('package', fn ($q) => $q->where('type', 'gym'))
                ->sum('price_paid');

            $dayPt = (float) MemberSubscription::countedAsRevenue()->whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereHas('package', fn ($q) => $q->whereIn('type', ['pt', 'combo']))
                ->sum('price_paid');

            $dayPos = (float) PosTransaction::completed()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->sum('total_amount');

            $chartData[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->translatedFormat('d M'),
                'gym' => $dayGym,
                'pt' => $dayPt,
                'pos' => $dayPos,
                'total' => $dayGym + $dayPt + $dayPos,
            ];
        }

        return [
            'kpis' => [
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'active_percentage' => $totalMembers > 0 ? (int) round(($activeMembers / $totalMembers) * 100) : 0,
                'pt_sessions_completed' => $ptSessionsCompleted,
                'pt_sessions_total' => $totalPtCompletedAllTime,
            ],
            'occupancy' => [
                'current_in_gym' => $currentOccupancy,
                'gym_capacity' => $gymCapacity,
                'occupancy_rate' => $occupancyRate,
                'today_checkins' => $todayIns,
                'today_checkouts' => $todayOuts,
            ],
            'gender_ratio' => [
                'male_count' => $maleMembers,
                'female_count' => $femaleMembers,
                'male_percent' => (int) round(($maleMembers / $totalGender) * 100),
                'female_percent' => (int) round(($femaleMembers / $totalGender) * 100),
            ],
            'revenue_summary' => [
                'total_revenue' => $totalRevenue,
                'gym_subscriptions' => $gymRevenue,
                'pt_packages' => $ptRevenue,
                'combo_packages' => $comboRevenue,
                'pos_retail' => $posRevenue,
                'total_transactions_count' => $transactions->count(),
            ],
            'chart_data' => $chartData,
            'transactions' => $transactions,
        ];
    }

    /**
     * Tulis nilai ke sel Excel SELALU sebagai teks biasa.
     *
     * Kenapa perlu? setCellValue() menganggap teks yang diawali "=" sebagai FORMULA.
     * Jika ada member bernama "=HYPERLINK(...)" atau "=1+1", Excel akan menjalankannya
     * saat file dibuka (disebut "formula injection"). Dengan TYPE_STRING, teks itu
     * hanya ditampilkan apa adanya.
     */
    private function setTextCell(Worksheet $sheet, string $cell, ?string $value): void
    {
        $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
    }
}
