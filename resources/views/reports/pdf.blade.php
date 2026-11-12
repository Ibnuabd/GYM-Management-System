<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan & Operasional - FitCore GMS</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #121c2c;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #6c5ffc;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: bold;
            color: #121c2c;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .brand-subtitle {
            font-size: 10px;
            color: #777587;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 2px 0 0 0;
        }
        .report-meta {
            text-align: right;
            font-size: 10px;
            color: #474555;
        }
        .report-title-box {
            background-color: #f0f3fb;
            border: 1px solid #dcd8ea;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 15px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #121c2c;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .report-desc {
            font-size: 10px;
            color: #555366;
            margin: 0;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: separate;
            border-spacing: 6px;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: left;
        }
        .kpi-label {
            font-size: 9px;
            color: #777587;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .kpi-value {
            font-size: 13px;
            font-weight: bold;
            color: #121c2c;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            margin-top: 10px;
        }
        .data-table th {
            background-color: #121c2c;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #121c2c;
        }
        .data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .total-row td {
            background-color: #e8eeff !important;
            font-weight: bold;
            border-top: 2px solid #6c5ffc;
            font-size: 10.5px;
        }
        .signature-section {
            margin-top: 25px;
            width: 100%;
        }
        .signature-box {
            width: 200px;
            text-align: center;
            float: right;
        }
        .signature-line {
            margin-top: 45px;
            border-top: 1px solid #121c2c;
            padding-top: 4px;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8.5px;
            color: #8c8aa0;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Header Table -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: middle;">
                <h1 class="brand-title">FITCORE GYM</h1>
                <p class="brand-subtitle">Integrated Gym Suite &bull; Sistem Manajemen Terpadu</p>
            </td>
            <td class="report-meta" style="vertical-align: middle;">
                <strong>Dicetak Pada:</strong> {{ $generated_at }}<br>
                <strong>Oleh Staf:</strong> {{ $generated_by }}
            </td>
        </tr>
    </table>

    <!-- Title Box -->
    <div class="report-title-box">
        <h2 class="report-title">Laporan Rekapitulasi Keuangan & Operasional</h2>
        <p class="report-desc">
            <strong>Rentang Waktu:</strong> {{ $filters['start_date']->translatedFormat('d F Y') }} s/d {{ $filters['end_date']->translatedFormat('d F Y') }}
            &nbsp;&bull;&nbsp;
            <strong>Kategori:</strong> {{ strtoupper($filters['category']) }}
            &nbsp;&bull;&nbsp;
            <strong>Total Transaksi:</strong> {{ $revenueSummary['total_transactions_count'] }} Catatan
        </p>
    </div>

    <!-- KPI Summary Grid -->
    <table class="kpi-table" cellpadding="0" cellspacing="0">
        <tr>
            <td width="25%">
                <div class="kpi-card" style="border-left: 3px solid #6c5ffc;">
                    <div class="kpi-label">Total Pendapatan</div>
                    <div class="kpi-value" style="color: #6c5ffc;">Rp {{ number_format($revenueSummary['total_revenue'], 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card" style="border-left: 3px solid #09ad95;">
                    <div class="kpi-label">Langganan Gym</div>
                    <div class="kpi-value">Rp {{ number_format($revenueSummary['gym_subscriptions'], 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card" style="border-left: 3px solid #05c3fb;">
                    <div class="kpi-label">Paket PT & Combo</div>
                    <div class="kpi-value">Rp {{ number_format($revenueSummary['pt_packages'] + $revenueSummary['combo_packages'], 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card" style="border-left: 3px solid #f7b924;">
                    <div class="kpi-label">POS Kasir Ritel</div>
                    <div class="kpi-value">Rp {{ number_format($revenueSummary['pos_retail'], 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Operational Context Bar -->
    <table style="width: 100%; margin-bottom: 12px; font-size: 9.5px; background: #fafafa; border: 1px solid #ebebeb; border-radius: 4px; padding: 6px 10px;">
        <tr>
            <td width="25%"><strong>Total Anggota:</strong> {{ $kpis['total_members'] }} Member</td>
            <td width="25%"><strong>Anggota Aktif:</strong> {{ $kpis['active_members'] }} ({{ $kpis['active_percentage'] }}%)</td>
            <td width="25%"><strong>Okupansi Gym:</strong> {{ $occupancy['current_in_gym'] }} / {{ $occupancy['gym_capacity'] }} ({{ $occupancy['occupancy_rate'] }}%)</td>
            <td width="25%"><strong>Sesi PT Selesai:</strong> {{ $kpis['pt_sessions_completed'] }} Sesi</td>
        </tr>
    </table>

    <!-- Detailed Transactions Table -->
    <h3 style="font-size: 11px; font-weight: bold; text-transform: uppercase; margin: 10px 0 4px 0; color: #121c2c;">Rincian Transaksi Finansial</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">No</th>
                <th width="15%">Waktu</th>
                <th width="15%">No. Ref</th>
                <th width="14%">Kategori</th>
                <th width="20%">Deskripsi Transaksi</th>
                <th width="16%">Member / Pelanggan</th>
                <th width="16%" class="text-right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $trx)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $trx['date_formatted'] }}</td>
                <td style="font-family: monospace;">{{ $trx['reference'] }}</td>
                <td>{{ $trx['category_label'] }}</td>
                <td>{{ $trx['description'] }}</td>
                <td>{{ $trx['customer'] }}</td>
                <td class="text-right">{{ number_format($trx['amount'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 15px; color: #777587;">
                    Tidak ada catatan transaksi pada filter tanggal dan kategori yang dipilih.
                </td>
            </tr>
            @endforelse

            <!-- Total Row -->
            @if($transactions->isNotEmpty())
            <tr class="total-row">
                <td colspan="6" class="text-right">TOTAL PENDAPATAN TERCATAT:</td>
                <td class="text-right">Rp {{ number_format($revenueSummary['total_revenue'], 0, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <p style="margin: 0 0 4px 0;">Mengetahui & Menyetujui,</p>
            <p style="margin: 0; font-size: 9px; color: #777587;">Supervisor Operasional FitCore</p>
            <div class="signature-line">
                {{ $generated_by }}
            </div>
        </div>
    </div>

    <!-- Page Footer -->
    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh FitCore Gym Management System &bull; Dokumen Sah Keuangan & Audit Operasional
    </div>

</body>
</html>

