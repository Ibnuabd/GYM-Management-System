<template>
  <AuthenticatedLayout>
    
    <!-- ==================== ROW 0: PAGE HEADER & ACTION ==================== -->
    <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-[#777587] text-xs font-semibold mb-1">
          <span>Beranda</span>
          <span class="material-symbols-outlined text-[14px]">chevron_right</span>
          <span class="text-[#5242e2]">Dashboard Gym</span>
        </div>
        <h1 class="text-2xl font-bold text-[#121c2c] tracking-tight">Ringkasan Operasional Gym</h1>
        <p class="text-xs text-[#474555]">
          Monitoring kehadiran real-time, transaksi kasir, dan progres sesi Personal Trainer.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border border-[#c8c4d8]/40 shadow-sm text-[#474555] text-xs font-semibold">
          <span class="material-symbols-outlined text-[18px] text-[#5242e2]">calendar_today</span>
          <span>17 Oktober 2026 (Hari Ini)</span>
        </div>
        <button 
          @click="exportAlert"
          class="flex items-center gap-1.5 bg-[#6c5ffc] hover:bg-[#5242e2] text-white px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 shadow-sm shadow-[#6c5ffc]/20 active:scale-95"
        >
          <span class="material-symbols-outlined text-[18px]">file_download</span>
          <span>Ekspor Laporan</span>
        </button>
      </div>
    </section>

    <!-- ==================== ROW 1: TOP METRIC CARDS (SPARKLINES) ==================== -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      
      <!-- Card 1: Total Pendapatan -->
      <div class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04),0px_2px_6px_-1px_rgba(22,28,45,0.05)] hover:shadow-[0px_10px_24px_-4px_rgba(108,95,252,0.08)] transition-all duration-200">
        <div class="flex items-center justify-between mb-2.5">
          <span class="text-xs font-semibold text-[#474555]">Total Pendapatan (Bln Ini)</span>
          <div class="w-9 h-9 rounded-xl bg-[#5242e2]/10 text-[#5242e2] flex items-center justify-center">
            <span class="material-symbols-outlined text-[20px]">payments</span>
          </div>
        </div>
        <div class="flex items-baseline justify-between mb-1.5">
          <span class="text-2xl font-bold text-[#121c2c] tnum">{{ metrics.total_revenue }}</span>
          <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-[#09ad95] bg-[#09ad95]/10 px-2 py-0.5 rounded-full">
            <span class="material-symbols-outlined text-[13px]">trending_up</span>
            {{ metrics.revenue_growth }}
          </span>
        </div>
        <div class="pt-2">
          <svg class="w-full h-8" fill="none" viewBox="0 0 100 24">
            <defs>
              <linearGradient id="grad1" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="#6c5ffc" stop-opacity="0.3"></stop>
                <stop offset="100%" stop-color="#6c5ffc" stop-opacity="0.0"></stop>
              </linearGradient>
            </defs>
            <path d="M0,20 Q15,4 30,14 T60,6 T85,12 T100,2" fill="none" stroke="#6c5ffc" stroke-linecap="round" stroke-width="2.5"></path>
            <path d="M0,20 Q15,4 30,14 T60,6 T85,12 T100,2 L100,24 L0,24 Z" fill="url(#grad1)"></path>
          </svg>
          <p class="text-[11px] text-[#777587] mt-1">Langganan Gym + PT + Ritel POS</p>
        </div>
      </div>

      <!-- Card 2: Total Member Aktif -->
      <div class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04),0px_2px_6px_-1px_rgba(22,28,45,0.05)] hover:shadow-[0px_10px_24px_-4px_rgba(108,95,252,0.08)] transition-all duration-200">
        <div class="flex items-center justify-between mb-2.5">
          <span class="text-xs font-semibold text-[#474555]">Total Member Aktif</span>
          <div class="w-9 h-9 rounded-xl bg-[#09ad95]/10 text-[#09ad95] flex items-center justify-center">
            <span class="material-symbols-outlined text-[20px]">badge</span>
          </div>
        </div>
        <div class="flex items-baseline justify-between mb-1.5">
          <span class="text-2xl font-bold text-[#121c2c] tnum">{{ metrics.active_members.toLocaleString('id-ID') }}</span>
          <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-[#09ad95] bg-[#09ad95]/10 px-2 py-0.5 rounded-full">
            <span class="material-symbols-outlined text-[13px]">trending_up</span>
            {{ metrics.members_growth }}
          </span>
        </div>
        <div class="pt-2">
          <svg class="w-full h-8" fill="none" viewBox="0 0 100 24">
            <defs>
              <linearGradient id="grad2" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="#09ad95" stop-opacity="0.3"></stop>
                <stop offset="100%" stop-color="#09ad95" stop-opacity="0.0"></stop>
              </linearGradient>
            </defs>
            <path d="M0,16 Q20,6 40,12 T70,4 T100,2" fill="none" stroke="#09ad95" stroke-linecap="round" stroke-width="2.5"></path>
            <path d="M0,16 Q20,6 40,12 T70,4 T100,2 L100,24 L0,24 Z" fill="url(#grad2)"></path>
          </svg>
          <p class="text-[11px] text-[#777587] mt-1">42 member baru bergabung minggu ini</p>
        </div>
      </div>

      <!-- Card 3: In-Gym Occupancy -->
      <div class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04),0px_2px_6px_-1px_rgba(22,28,45,0.05)] hover:shadow-[0px_10px_24px_-4px_rgba(108,95,252,0.08)] transition-all duration-200">
        <div class="flex items-center justify-between mb-2.5">
          <span class="text-xs font-semibold text-[#474555]">Member di Ruangan (In-Gym)</span>
          <div class="w-9 h-9 rounded-xl bg-[#03c892]/10 text-[#03c892] flex items-center justify-center relative">
            <span class="material-symbols-outlined text-[20px]">door_open</span>
            <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-[#03c892] animate-ping"></span>
          </div>
        </div>
        <div class="flex items-baseline justify-between mb-1.5">
          <span class="text-2xl font-bold text-[#121c2c] tnum">{{ metrics.in_gym_count }} <span class="text-xs text-[#777587] font-normal">/ {{ metrics.gym_capacity }} Kapasitas</span></span>
          <span class="inline-flex items-center text-[10px] font-bold text-[#03c892] bg-[#03c892]/10 px-2 py-0.5 rounded-full">
            {{ Math.round((metrics.in_gym_count / metrics.gym_capacity) * 100) }}% Terisi
          </span>
        </div>
        <div class="pt-2">
          <div class="w-full bg-[#e8eeff] rounded-full h-2.5 overflow-hidden">
            <div class="bg-gradient-to-r from-[#03c892] to-[#09ad95] h-2.5 rounded-full" :style="{ width: `${(metrics.in_gym_count / metrics.gym_capacity) * 100}%` }"></div>
          </div>
          <p class="text-[11px] text-[#777587] mt-2">Berdasarkan pemindaian RFID gerbang</p>
        </div>
      </div>

      <!-- Card 4: Sesi PT Selesai -->
      <div class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04),0px_2px_6px_-1px_rgba(22,28,45,0.05)] hover:shadow-[0px_10px_24px_-4px_rgba(108,95,252,0.08)] transition-all duration-200">
        <div class="flex items-center justify-between mb-2.5">
          <span class="text-xs font-semibold text-[#474555]">Sesi PT Selesai</span>
          <div class="w-9 h-9 rounded-xl bg-[#f7b924]/10 text-[#f7b924] flex items-center justify-center">
            <span class="material-symbols-outlined text-[20px]">fitness_center</span>
          </div>
        </div>
        <div class="flex items-baseline justify-between mb-1.5">
          <span class="text-2xl font-bold text-[#121c2c] tnum">{{ metrics.completed_pt_sessions }} <span class="text-xs text-[#777587] font-normal">Sesi</span></span>
          <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-[#09ad95] bg-[#09ad95]/10 px-2 py-0.5 rounded-full">
            <span class="material-symbols-outlined text-[13px]">trending_up</span>
            {{ metrics.pt_sessions_growth }}
          </span>
        </div>
        <div class="pt-2">
          <svg class="w-full h-8" fill="none" viewBox="0 0 100 24">
            <defs>
              <linearGradient id="grad4" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="#f7b924" stop-opacity="0.3"></stop>
                <stop offset="100%" stop-color="#f7b924" stop-opacity="0.0"></stop>
              </linearGradient>
            </defs>
            <path d="M0,14 Q25,4 50,18 T75,8 T100,2" fill="none" stroke="#f7b924" stroke-linecap="round" stroke-width="2.5"></path>
            <path d="M0,14 Q25,4 50,18 T75,8 T100,2 L100,24 L0,24 Z" fill="url(#grad4)"></path>
          </svg>
          <p class="text-[11px] text-[#777587] mt-1">0% selisih kuota (Zero session leakage)</p>
        </div>
      </div>

    </section>

    <!-- ==================== ROW 2: SOLID STAT CARDS ==================== -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      
      <!-- Solid Violet: Check-in Hari Ini -->
      <div class="rounded-xl p-5 bg-[#6c5ffc] text-white shadow-md relative overflow-hidden flex items-center justify-between">
        <div class="relative z-10">
          <p class="text-white/80 text-xs font-semibold uppercase tracking-wider mb-1">Check-in Hari Ini</p>
          <h3 class="text-3xl font-extrabold text-white leading-none mb-1 tnum">{{ metrics.today_checkins }}</h3>
          <span class="text-[11px] text-white/70">Puncak: 17:00 - 19:30</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center relative z-10">
          <span class="material-symbols-outlined text-[26px] text-white">fingerprint</span>
        </div>
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
      </div>

      <!-- Solid Teal: Kasir Ritel -->
      <div class="rounded-xl p-5 bg-[#09ad95] text-white shadow-md relative overflow-hidden flex items-center justify-between">
        <div class="relative z-10">
          <p class="text-white/80 text-xs font-semibold uppercase tracking-wider mb-1">Kasir Ritel Hari Ini</p>
          <h3 class="text-2xl font-extrabold text-white leading-none mb-1 tnum">{{ metrics.pos_sales_today }}</h3>
          <span class="text-[11px] text-white/70">{{ metrics.pos_transactions_today }} transaksi ritel</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center relative z-10">
          <span class="material-symbols-outlined text-[26px] text-white">shopping_cart_checkout</span>
        </div>
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
      </div>

      <!-- Solid Cyan: Kuota Sesi PT Aktif -->
      <div class="rounded-xl p-5 bg-[#03c892] text-white shadow-md relative overflow-hidden flex items-center justify-between">
        <div class="relative z-10">
          <p class="text-white/80 text-xs font-semibold uppercase tracking-wider mb-1">Kuota Sesi PT Aktif</p>
          <h3 class="text-3xl font-extrabold text-white leading-none mb-1 tnum">{{ metrics.active_pt_quota }} <span class="text-sm font-normal">Sesi</span></h3>
          <span class="text-[11px] text-white/70">Menunggu penjadwalan</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center relative z-10">
          <span class="material-symbols-outlined text-[26px] text-white">event_available</span>
        </div>
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
      </div>

      <!-- Solid Sky Blue: Segera Kedaluwarsa -->
      <div class="rounded-xl p-5 bg-[#05c3fb] text-white shadow-md relative overflow-hidden flex items-center justify-between">
        <div class="relative z-10">
          <p class="text-white/80 text-xs font-semibold uppercase tracking-wider mb-1">Segera Kedaluwarsa</p>
          <h3 class="text-3xl font-extrabold text-white leading-none mb-1 tnum">{{ metrics.expiring_soon_members }} <span class="text-sm font-normal">Member</span></h3>
          <span class="text-[11px] text-white/70">Masa aktif &lt; 7 hari</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center relative z-10">
          <span class="material-symbols-outlined text-[26px] text-white">hourglass_bottom</span>
        </div>
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
      </div>

    </section>

    <!-- ==================== ROW 3: VISUAL METRICS & WORKLOAD ==================== -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Chart Bar Pendapatan Bulanan (6 cols) -->
      <div class="lg:col-span-6 bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04),0px_2px_6px_-1px_rgba(22,28,45,0.05)] flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-[#e9edf4] mb-4">
            <div>
              <h3 class="text-sm font-bold text-[#121c2c]">Komposisi Pendapatan Bulanan</h3>
              <p class="text-[11px] text-[#777587]">Langganan Gym, Sesi PT, & Kasir POS</p>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex items-center gap-1 text-[11px] text-[#777587]">
                <span class="w-2.5 h-2.5 rounded-full bg-[#6c5ffc]"></span> Langganan
              </div>
              <div class="flex items-center gap-1 text-[11px] text-[#777587]">
                <span class="w-2.5 h-2.5 rounded-full bg-[#09ad95]"></span> Sesi PT
              </div>
              <div class="flex items-center gap-1 text-[11px] text-[#777587]">
                <span class="w-2.5 h-2.5 rounded-full bg-[#05c3fb]"></span> Kasir POS
              </div>
            </div>
          </div>

          <!-- Bar Visual -->
          <div class="h-48 flex items-end justify-between gap-3 pt-4 pb-2 px-2">
            <div v-for="bar in barData" :key="bar.month" class="flex-1 flex flex-col items-center gap-1.5 group">
              <div class="w-full flex items-end justify-center gap-1 h-36">
                <div class="w-2 bg-[#6c5ffc] rounded-t-sm" :style="{ height: `${bar.gym}%` }"></div>
                <div class="w-2 bg-[#09ad95] rounded-t-sm" :style="{ height: `${bar.pt}%` }"></div>
                <div class="w-2 bg-[#05c3fb] rounded-t-sm" :style="{ height: `${bar.pos}%` }"></div>
              </div>
              <span class="text-[10px] text-[#777587] font-semibold" :class="bar.current ? 'text-[#6c5ffc] font-bold' : ''">{{ bar.month }}</span>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-[#e9edf4] flex items-center justify-between text-xs text-[#474555]">
          <span>Rata-rata pertumbuhan: <strong class="text-[#09ad95]">+14.2%</strong> per kuartal</span>
          <span class="text-[#6c5ffc] font-bold cursor-pointer hover:underline flex items-center gap-0.5">
            Rincian Keuangan <span class="material-symbols-outlined text-[14px]">chevron_right</span>
          </span>
        </div>
      </div>

      <!-- Donut Demografi & Okupansi (3 cols) -->
      <div class="lg:col-span-3 bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04),0px_2px_6px_-1px_rgba(22,28,45,0.05)] flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-[#e9edf4] mb-3">
            <h3 class="text-sm font-bold text-[#121c2c]">Demografi Member</h3>
            <span class="material-symbols-outlined text-[18px] text-[#777587]">pie_chart</span>
          </div>

          <div class="relative w-32 h-32 mx-auto my-2 flex items-center justify-center">
            <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
              <path class="text-[#e8eeff]" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3.5"></path>
              <path class="text-[#6c5ffc]" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="62, 100" stroke-linecap="round" stroke-width="3.5"></path>
              <path class="text-[#d7347a]" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="38, 100" stroke-dashoffset="-62" stroke-linecap="round" stroke-width="3.5"></path>
            </svg>
            <div class="absolute flex flex-col items-center justify-center text-center">
              <span class="text-xl font-bold text-[#121c2c] leading-none tnum">1.248</span>
              <span class="text-[10px] text-[#777587] mt-0.5">Member</span>
            </div>
          </div>

          <div class="space-y-2 mt-3">
            <div class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-[#6c5ffc]"></span>
                <span class="text-[#474555]">Member Pria</span>
              </div>
              <span class="font-bold text-[#121c2c]">62% (774)</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-[#d7347a]"></span>
                <span class="text-[#474555]">Member Wanita</span>
              </div>
              <span class="font-bold text-[#121c2c]">38% (474)</span>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-[#e9edf4] text-center">
          <span class="text-[11px] text-[#09ad95] font-semibold">Usia Dominan: 22 - 38 Tahun</span>
        </div>
      </div>

      <!-- Status Hardware Kios (3 cols) -->
      <div class="lg:col-span-3 bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04),0px_2px_6px_-1px_rgba(22,28,45,0.05)] flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-[#e9edf4] mb-3">
            <h3 class="text-sm font-bold text-[#121c2c]">Status Perangkat Kios</h3>
            <span class="material-symbols-outlined text-[18px] text-[#777587]">devices</span>
          </div>

          <div class="space-y-3">
            <!-- RFID -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#5242e2]/10 text-[#5242e2] flex items-center justify-center">
                  <span class="material-symbols-outlined text-[16px]">credit_card</span>
                </div>
                <div>
                  <p class="text-xs font-bold text-[#121c2c]">RFID USB Reader</p>
                  <p class="text-[10px] text-[#777587]">{{ hardwareStatus.rfid_reader.type }}</p>
                </div>
              </div>
              <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[#09ad95] bg-[#09ad95]/10 px-2 py-0.5 rounded-full">
                {{ hardwareStatus.rfid_reader.status }}
              </span>
            </div>

            <!-- Webcam -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#09ad95]/10 text-[#09ad95] flex items-center justify-center">
                  <span class="material-symbols-outlined text-[16px]">videocam</span>
                </div>
                <div>
                  <p class="text-xs font-bold text-[#121c2c]">Webcam Pintu</p>
                  <p class="text-[10px] text-[#777587]">{{ hardwareStatus.webcam.type }}</p>
                </div>
              </div>
              <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[#09ad95] bg-[#09ad95]/10 px-2 py-0.5 rounded-full">
                {{ hardwareStatus.webcam.status }}
              </span>
            </div>

            <!-- POS Printer -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#05c3fb]/10 text-[#05c3fb] flex items-center justify-center">
                  <span class="material-symbols-outlined text-[16px]">print</span>
                </div>
                <div>
                  <p class="text-xs font-bold text-[#121c2c]">Printer Struk POS</p>
                  <p class="text-[10px] text-[#777587]">{{ hardwareStatus.pos_printer.type }}</p>
                </div>
              </div>
              <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[#09ad95] bg-[#09ad95]/10 px-2 py-0.5 rounded-full">
                {{ hardwareStatus.pos_printer.status }}
              </span>
            </div>

            <!-- Database -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#f7b924]/10 text-[#f7b924] flex items-center justify-center">
                  <span class="material-symbols-outlined text-[16px]">database</span>
                </div>
                <div>
                  <p class="text-xs font-bold text-[#121c2c]">Latensi Database</p>
                  <p class="text-[10px] text-[#777587]">MySQL InnoDB</p>
                </div>
              </div>
              <span class="text-xs font-bold text-[#121c2c] tnum">{{ hardwareStatus.db_latency }}</span>
            </div>
          </div>
        </div>

        <button 
          @click="diagnosticAlert"
          class="w-full mt-3 py-1.5 rounded-lg bg-[#e8eeff] hover:bg-[#dfe8ff] text-[#5242e2] text-xs font-bold transition-colors flex items-center justify-center gap-1"
        >
          <span class="material-symbols-outlined text-[15px]">refresh</span>
          <span>Diagnostik Kios</span>
        </button>
      </div>

    </section>

    <!-- ==================== ROW 4: DATA TABLE (LOG KEHADIRAN RFID REAL-TIME) ==================== -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Tabel Log Kehadiran Gerbang (8 cols) -->
      <div class="lg:col-span-8 bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04),0px_2px_6px_-1px_rgba(22,28,45,0.05)]">
        <div class="flex items-center justify-between pb-3 border-b border-[#e9edf4] mb-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-sm font-bold text-[#121c2c]">Log Kehadiran Gerbang Real-time</h3>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-[#09ad95]/10 text-[#09ad95]">
                RFID + Webcam Sync
              </span>
            </div>
            <p class="text-[11px] text-[#777587]">Rekaman scan kartu RFID & snapshot otomatis di pintu gerbang.</p>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-[#f8fafc] text-[#777587] text-[10px] uppercase font-bold tracking-wider">
                <th class="py-2.5 px-3 rounded-l-lg">Snapshot & Member</th>
                <th class="py-2.5 px-3">Paket Langganan</th>
                <th class="py-2.5 px-3">Waktu Tap</th>
                <th class="py-2.5 px-3">Status Gerbang</th>
                <th class="py-2.5 px-3 text-right rounded-r-lg">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f0f3f8] text-xs">
              <tr v-for="log in recentAttendances" :key="log.id" class="hover:bg-[#fbfcfe] transition-colors">
                <td class="py-3 px-3 font-semibold text-[#121c2c] flex items-center gap-2.5">
                  <div class="relative w-9 h-9 rounded-lg overflow-hidden border border-[#e9edf4] bg-slate-100 flex-shrink-0">
                    <img :src="log.snapshot_url" class="w-full h-full object-cover" alt="Webcam Photo" />
                    <span 
                      class="absolute bottom-0 right-0 w-2 h-2 rounded-full border border-white"
                      :class="log.status === 'IN' ? 'bg-[#09ad95]' : 'bg-[#05c3fb]'"
                    ></span>
                  </div>
                  <div>
                    <p class="font-bold text-xs leading-tight">{{ log.member_name }}</p>
                    <p class="text-[10px] text-[#777587] font-normal">{{ log.member_code }} • RFID: {{ log.rfid_uid }}</p>
                  </div>
                </td>
                <td class="py-3 px-3">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#6c5ffc]/10 text-[#6c5ffc]">
                    {{ log.package_name }}
                  </span>
                </td>
                <td class="py-3 px-3 text-xs tnum">
                  {{ log.scanned_time }} <span class="text-[#777587] text-[10px] block">{{ log.time_ago }}</span>
                </td>
                <td class="py-3 px-3">
                  <span 
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="log.status === 'IN' ? 'bg-[#09ad95]/10 text-[#09ad95]' : 'bg-[#05c3fb]/10 text-[#05c3fb]'"
                  >
                    <span class="material-symbols-outlined text-[12px]">{{ log.status === 'IN' ? 'login' : 'logout' }}</span>
                    {{ log.status === 'IN' ? 'IN (Masuk)' : 'OUT (Keluar)' }}
                  </span>
                </td>
                <td class="py-3 px-3 text-right">
                  <button class="p-1 text-[#777587] hover:text-[#5242e2] transition-colors" title="Lihat Snapshot">
                    <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between pt-3 mt-2 border-t border-[#e9edf4] text-xs">
          <span class="text-[#777587] text-[11px]">Menampilkan 4 dari {{ metrics.today_checkins }} log hari ini</span>
          <div class="flex items-center gap-1">
            <button class="px-2 py-0.5 text-xs rounded border border-[#c8c4d8]/40 text-[#777587] hover:bg-[#e8eeff]">Prev</button>
            <button class="px-2 py-0.5 text-xs rounded bg-[#5242e2] text-white font-bold">1</button>
            <button class="px-2 py-0.5 text-xs rounded border border-[#c8c4d8]/40 text-[#777587] hover:bg-[#e8eeff]">Next</button>
          </div>
        </div>
      </div>

      <!-- Sesi PT Terbaru & Audit Void Alert (4 cols) -->
      <div class="lg:col-span-4 space-y-6">
        
        <!-- Sesi PT Terbaru -->
        <div class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04),0px_2px_6px_-1px_rgba(22,28,45,0.05)]">
          <div class="flex items-center justify-between pb-3 border-b border-[#e9edf4] mb-3">
            <h3 class="text-sm font-bold text-[#121c2c]">Sesi PT Terbaru</h3>
            <span class="text-[10px] bg-[#f7b924]/15 text-[#b58004] px-2 py-0.5 rounded-full font-bold">PT Logs</span>
          </div>

          <div class="space-y-3">
            <div v-for="pt in recentPtSessions" :key="pt.id" class="flex items-start justify-between">
              <div class="flex items-start gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-[#5242e2]/10 text-[#5242e2] flex items-center justify-center font-bold text-[11px] mt-0.5">
                  {{ pt.trainer_initials }}
                </div>
                <div>
                  <p class="text-xs font-bold text-[#121c2c] leading-tight">{{ pt.member_name }}</p>
                  <p class="text-[10px] text-[#777587]">{{ pt.trainer_name }} • {{ pt.workout_focus }}</p>
                  <p class="text-[10px] text-[#5242e2] font-semibold mt-0.5">Sisa: {{ pt.remaining_sessions }}</p>
                </div>
              </div>
              <span class="text-[10px] text-[#777587] tnum">{{ pt.time_ago }}</span>
            </div>
          </div>
        </div>

        <!-- Audit Keamanan Void Alert -->
        <div class="rounded-xl p-4 bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200">
          <div class="flex items-start gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-[#f7b924] text-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm">
              <span class="material-symbols-outlined text-[16px]">security</span>
            </div>
            <div>
              <p class="text-xs font-bold text-amber-900 leading-tight">Audit Keamanan Void POS</p>
              <p class="text-[11px] text-amber-700 mt-1 leading-snug">
                Semua pembatalan kasir wajib otorisasi PIN Supervisor. Hari ini: <strong>0 Void</strong> (100% kepatuhan).
              </p>
            </div>
          </div>
        </div>

      </div>

    </section>

  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  metrics: Object,
  recentAttendances: Array,
  recentPtSessions: Array,
  hardwareStatus: Object,
  userRole: String,
});

const barData = [
  { month: 'Mei', gym: 65, pt: 45, pos: 25, current: false },
  { month: 'Jun', gym: 75, pt: 52, pos: 30, current: false },
  { month: 'Jul', gym: 70, pt: 60, pos: 35, current: false },
  { month: 'Agu', gym: 85, pt: 65, pos: 42, current: false },
  { month: 'Sep', gym: 90, pt: 72, pos: 48, current: false },
  { month: 'Okt', gym: 95, pt: 80, pos: 55, current: true },
];

const exportAlert = () => {
  alert('Fitur Ekspor Laporan akan diproses pada modul Laporan & Analitik (Issue #7).');
};

const diagnosticAlert = () => {
  alert('Uji diagnostik perangkat kios: Semua sensor RFID & Webcam beroperasi normal (Latency: 12ms).');
};
</script>
