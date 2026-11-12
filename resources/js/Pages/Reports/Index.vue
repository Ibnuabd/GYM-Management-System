<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      
      <!-- ==================== PAGE HEADER & EXPORT ACTIONS ==================== -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <!-- Breadcrumb -->
          <div class="flex items-center gap-1.5 text-xs text-[#777587] mb-1">
            <span>FitCore GMS</span>
            <span class="material-symbols-outlined text-[13px]">chevron_right</span>
            <span>Laporan & Audit</span>
            <span class="material-symbols-outlined text-[13px]">chevron_right</span>
            <span class="text-[#6c5ffc] font-semibold">Analitik Operasional & Keuangan</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-[#121c2c] tracking-tight">Laporan Keuangan & Analitik Fasilitas</h1>
          <p class="text-xs text-[#777587] mt-0.5">Pantau pendapatan multi-channel, okupansi gerbang RFID, dan ekspor laporan sah.</p>
        </div>

        <!-- One-Click Export Buttons -->
        <div class="flex flex-wrap items-center gap-2.5">
          <a 
            :href="pdfExportUrl" 
            target="_blank"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#ba1a1a]/10 hover:bg-[#ba1a1a]/20 text-[#ba1a1a] border border-[#ba1a1a]/30 text-xs font-bold transition-all duration-150 shadow-2xs active:scale-[0.98]"
          >
            <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
            <span>Ekspor PDF</span>
          </a>

          <a 
            :href="excelExportUrl" 
            target="_blank"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#09ad95]/10 hover:bg-[#09ad95]/20 text-[#078e7a] border border-[#09ad95]/30 text-xs font-bold transition-all duration-150 shadow-2xs active:scale-[0.98]"
          >
            <span class="material-symbols-outlined text-[18px]">table_view</span>
            <span>Ekspor Excel (.xlsx)</span>
          </a>
        </div>
      </div>

      <!-- ==================== FILTER TOOLBAR ==================== -->
      <div class="bg-white rounded-2xl p-4 sm:p-5 border border-[#c8c4d8]/40 shadow-xs space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          
          <!-- Interval Quick Select Pills -->
          <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs font-bold text-[#474555] uppercase tracking-wider mr-1">Periode:</span>
            <button 
              type="button"
              v-for="item in intervals"
              :key="item.key"
              @click="setInterval(item.key)"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150"
              :class="selectedInterval === item.key ? 'bg-[#6c5ffc] text-white shadow-xs font-bold' : 'bg-[#f0f3fb] text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
            >
              {{ item.label }}
            </button>
          </div>

          <!-- Category Selector & Custom Date Inputs -->
          <div class="flex flex-wrap items-center gap-2.5">
            <!-- Revenue Category Dropdown -->
            <div class="flex items-center gap-1.5">
              <label for="categoryFilter" class="text-xs font-bold text-[#474555] uppercase tracking-wider">Kategori:</label>
              <select 
                id="categoryFilter"
                v-model="selectedCategory" 
                @change="applyFilter"
                class="h-9 px-3 text-xs font-medium rounded-lg bg-white border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-1 focus:ring-[#6c5ffc] text-[#121c2c] outline-none cursor-pointer"
              >
                <option value="all">Semua Kategori Pendapatan</option>
                <option value="gym_subscription">Langganan Gym Reguler</option>
                <option value="pt_package">Paket Personal Trainer</option>
                <option value="combo">Paket Combo Gym + PT</option>
                <option value="pos_retail">Penjualan Kasir POS Ritel</option>
              </select>
            </div>

            <!-- Custom Date Range (when selectedInterval === 'custom') -->
            <div v-if="selectedInterval === 'custom'" class="flex items-center gap-1.5">
              <input 
                type="date" 
                v-model="customStartDate" 
                class="h-9 px-2 text-xs rounded-lg border border-[#c8c4d8]/60 text-[#121c2c] focus:border-[#6c5ffc] outline-none"
              />
              <span class="text-xs text-[#777587]">s/d</span>
              <input 
                type="date" 
                v-model="customEndDate" 
                class="h-9 px-2 text-xs rounded-lg border border-[#c8c4d8]/60 text-[#121c2c] focus:border-[#6c5ffc] outline-none"
              />
              <button 
                type="button" 
                @click="applyFilter"
                class="h-9 px-3 rounded-lg bg-[#6c5ffc] text-white font-bold text-xs hover:bg-[#5242e2] transition-colors"
              >
                Terapkan
              </button>
            </div>
          </div>

        </div>
      </div>

      <!-- ==================== REAL-TIME KPI SUMMARY CARDS ==================== -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Members Card -->
        <div class="bg-white rounded-2xl p-5 border border-[#c8c4d8]/40 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-[#777587]">Total Anggota Terdaftar</p>
            <h3 class="text-2xl font-extrabold text-[#121c2c] mt-1">{{ kpis.total_members.toLocaleString('id-ID') }}</h3>
            <p class="text-[11px] text-[#09ad95] font-semibold mt-0.5 flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px]">check_circle</span>
              <span>{{ kpis.active_members }} Member Aktif ({{ kpis.active_percentage }}%)</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-[#6c5ffc]/10 text-[#6c5ffc] flex items-center justify-center">
            <span class="material-symbols-outlined text-[26px]">group</span>
          </div>
        </div>

        <!-- In-Gym Occupancy Card -->
        <div class="bg-white rounded-2xl p-5 border border-[#c8c4d8]/40 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-[#777587]">Okupansi Gerbang RFID</p>
            <h3 class="text-2xl font-extrabold text-[#121c2c] mt-1">
              {{ occupancy.current_in_gym }} <span class="text-xs font-normal text-[#777587]">/ {{ occupancy.gym_capacity }} orang</span>
            </h3>
            <p class="text-[11px] font-semibold mt-0.5" :class="occupancy.occupancy_rate > 85 ? 'text-[#ba1a1a]' : 'text-[#09ad95]'">
              {{ occupancy.occupancy_rate }}% Kapasitas Terisi
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-[#09ad95]/10 text-[#09ad95] flex items-center justify-center">
            <span class="material-symbols-outlined text-[26px]">sensor_door</span>
          </div>
        </div>

        <!-- PT Sessions Completed Card -->
        <div class="bg-white rounded-2xl p-5 border border-[#c8c4d8]/40 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-[#777587]">Sesi PT Selesai (Periode Ini)</p>
            <h3 class="text-2xl font-extrabold text-[#121c2c] mt-1">{{ kpis.pt_sessions_completed }} <span class="text-xs font-normal text-[#777587]">Sesi</span></h3>
            <p class="text-[11px] text-[#777587] mt-0.5">
              Total sepanjang masa: <strong>{{ kpis.pt_sessions_total }} sesi</strong>
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-[#05c3fb]/10 text-[#05c3fb] flex items-center justify-center">
            <span class="material-symbols-outlined text-[26px]">sports_gymnastics</span>
          </div>
        </div>

        <!-- Total Revenue Card -->
        <div class="bg-white rounded-2xl p-5 border border-[#c8c4d8]/40 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-[#777587]">Total Pendapatan Terhitung</p>
            <h3 class="text-xl sm:text-2xl font-extrabold text-[#6c5ffc] mt-1">
              Rp {{ revenueSummary.total_revenue.toLocaleString('id-ID') }}
            </h3>
            <p class="text-[11px] text-[#777587] mt-0.5">
              Dari <strong>{{ revenueSummary.total_transactions_count }} transaksi</strong>
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-[#6c5ffc]/10 text-[#6c5ffc] flex items-center justify-center">
            <span class="material-symbols-outlined text-[26px]">payments</span>
          </div>
        </div>

      </div>

      <!-- ==================== REVENUE COMPOSITION & OCCUPANCY GAUGE ==================== -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Revenue Composition Bar / Trend Visual (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-2xl p-5 sm:p-6 border border-[#c8c4d8]/40 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-extrabold uppercase tracking-wider text-[#121c2c]">Komposisi Pendapatan Multi-Channel</h3>
              <p class="text-xs text-[#777587] mt-0.5">Visualisasi perbandingan pendapatan Langganan Gym, Paket PT, dan Kasir POS.</p>
            </div>
            
            <!-- Legend Indicators -->
            <div class="flex items-center gap-3 text-xs">
              <span class="inline-flex items-center gap-1.5 text-[11px] text-[#474555]">
                <span class="w-3 h-3 rounded-sm bg-[#6c5ffc]"></span>
                <span>Gym</span>
              </span>
              <span class="inline-flex items-center gap-1.5 text-[11px] text-[#474555]">
                <span class="w-3 h-3 rounded-sm bg-[#05c3fb]"></span>
                <span>PT & Combo</span>
              </span>
              <span class="inline-flex items-center gap-1.5 text-[11px] text-[#474555]">
                <span class="w-3 h-3 rounded-sm bg-[#f7b924]"></span>
                <span>POS Ritel</span>
              </span>
            </div>
          </div>

          <!-- Channel Revenue Segmented Breakdown Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-3.5 rounded-xl bg-[#6c5ffc]/5 border border-[#6c5ffc]/20">
              <p class="text-[10px] uppercase font-bold text-[#6c5ffc]">Langganan Gym</p>
              <p class="text-base font-extrabold text-[#121c2c] mt-0.5">Rp {{ revenueSummary.gym_subscriptions.toLocaleString('id-ID') }}</p>
              <p class="text-[10px] text-[#777587] mt-0.5">{{ getPercentage(revenueSummary.gym_subscriptions, revenueSummary.total_revenue) }}% dari total</p>
            </div>

            <div class="p-3.5 rounded-xl bg-[#05c3fb]/5 border border-[#05c3fb]/20">
              <p class="text-[10px] uppercase font-bold text-[#0284c7]">Paket PT & Combo</p>
              <p class="text-base font-extrabold text-[#121c2c] mt-0.5">Rp {{ (revenueSummary.pt_packages + revenueSummary.combo_packages).toLocaleString('id-ID') }}</p>
              <p class="text-[10px] text-[#777587] mt-0.5">{{ getPercentage(revenueSummary.pt_packages + revenueSummary.combo_packages, revenueSummary.total_revenue) }}% dari total</p>
            </div>

            <div class="p-3.5 rounded-xl bg-[#f7b924]/10 border border-[#f7b924]/30">
              <p class="text-[10px] uppercase font-bold text-[#b58004]">POS Kasir Ritel</p>
              <p class="text-base font-extrabold text-[#121c2c] mt-0.5">Rp {{ revenueSummary.pos_retail.toLocaleString('id-ID') }}</p>
              <p class="text-[10px] text-[#777587] mt-0.5">{{ getPercentage(revenueSummary.pos_retail, revenueSummary.total_revenue) }}% dari total</p>
            </div>
          </div>

          <!-- CSS Multi-Bar Chart Visualizer -->
          <div class="mt-4 pt-4 border-t border-[#f0f3fb]">
            <p class="text-[11px] font-bold text-[#474555] uppercase tracking-wider mb-3">Tren Harian Pendapatan:</p>
            
            <div v-if="chartData.length" class="overflow-x-auto pb-2">
              <div class="min-w-[500px] flex items-end gap-2 h-44 pt-6 px-2 border-b border-[#e2e8f0]">
                <div 
                  v-for="(bar, i) in chartData" 
                  :key="i"
                  class="flex-1 flex flex-col items-center h-full justify-end group relative"
                >
                  <!-- Tooltip Hover Popover -->
                  <div class="absolute bottom-full mb-2 hidden group-hover:block z-20 w-40 p-2 rounded-lg bg-[#121c2c] text-white text-[10px] shadow-lg pointer-events-none">
                    <p class="font-bold border-b border-white/20 pb-1 mb-1">{{ bar.label }}</p>
                    <p class="text-[#8b7eff]">Gym: Rp {{ bar.gym.toLocaleString('id-ID') }}</p>
                    <p class="text-[#38bdf8]">PT: Rp {{ bar.pt.toLocaleString('id-ID') }}</p>
                    <p class="text-[#facc15]">POS: Rp {{ bar.pos.toLocaleString('id-ID') }}</p>
                    <p class="font-bold text-emerald-400 pt-1 border-t border-white/10 mt-1">Total: Rp {{ bar.total.toLocaleString('id-ID') }}</p>
                  </div>

                  <!-- Stacked Bar Column -->
                  <div class="w-full max-w-[28px] rounded-t flex flex-col-reverse overflow-hidden bg-slate-100" :style="{ height: getBarHeight(bar.total) }">
                    <div class="bg-[#6c5ffc] w-full" :style="{ height: getSegmentHeight(bar.gym, bar.total) }"></div>
                    <div class="bg-[#05c3fb] w-full" :style="{ height: getSegmentHeight(bar.pt, bar.total) }"></div>
                    <div class="bg-[#f7b924] w-full" :style="{ height: getSegmentHeight(bar.pos, bar.total) }"></div>
                  </div>

                  <!-- Date Label below bar -->
                  <span class="text-[9px] text-[#777587] mt-1.5 truncate max-w-full text-center">
                    {{ bar.label }}
                  </span>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-8 text-xs text-[#777587]">
              Tidak ada data transaksi pada rentang tanggal ini.
            </div>
          </div>

        </div>

        <!-- Real-Time Gate Occupancy Gauge & Gender Ratio (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- Occupancy Ring / Gauge Card -->
          <div class="bg-white rounded-2xl p-5 sm:p-6 border border-[#c8c4d8]/40 shadow-xs space-y-4 text-center">
            <div class="flex items-center justify-between text-left">
              <div>
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-[#121c2c]">Okupansi Fasilitas Gym</h3>
                <p class="text-xs text-[#777587]">Sensor Check-in Kios RFID</p>
              </div>
              <span class="w-2 h-2 rounded-full bg-[#09ad95] ring-2 ring-[#09ad95]/20 animate-pulse"></span>
            </div>

            <!-- Circular Gauge Visualization -->
            <div class="relative w-36 h-36 mx-auto flex items-center justify-center my-2">
              <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                <circle
                  cx="50"
                  cy="50"
                  r="40"
                  fill="transparent"
                  stroke="#f0f3fb"
                  stroke-width="12"
                />
                <circle
                  cx="50"
                  cy="50"
                  r="40"
                  fill="transparent"
                  :stroke="occupancy.occupancy_rate > 85 ? '#ba1a1a' : '#09ad95'"
                  stroke-width="12"
                  stroke-dasharray="251.2"
                  :stroke-dashoffset="251.2 - (251.2 * occupancy.occupancy_rate / 100)"
                  stroke-linecap="round"
                  class="transition-all duration-700"
                />
              </svg>
              <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-2xl font-black text-[#121c2c]">{{ occupancy.current_in_gym }}</span>
                <span class="text-[10px] uppercase font-bold text-[#777587]">Orang di Gym</span>
              </div>
            </div>

            <!-- Capacity Info & Live Status -->
            <div class="pt-2 border-t border-[#f0f3fb] space-y-1.5 text-xs">
              <div class="flex items-center justify-between text-[#474555]">
                <span>Total Kapasitas:</span>
                <strong>{{ occupancy.gym_capacity }} Orang</strong>
              </div>
              <div class="flex items-center justify-between text-[#474555]">
                <span>Check-in Hari Ini:</span>
                <strong class="text-emerald-600">+{{ occupancy.today_checkins }}</strong>
              </div>
              <div class="flex items-center justify-between text-[#474555]">
                <span>Check-out Hari Ini:</span>
                <strong class="text-slate-500">-{{ occupancy.today_checkouts }}</strong>
              </div>
            </div>
          </div>

          <!-- Gender Ratio Distribution Card -->
          <div class="bg-white rounded-2xl p-5 border border-[#c8c4d8]/40 shadow-xs space-y-3">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-[#474555] flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px] text-[#6c5ffc]">pie_chart</span>
              <span>Rasio Gender Anggota</span>
            </h3>

            <!-- Two-tone ratio bar -->
            <div class="w-full h-3 rounded-full bg-[#f0f3fb] overflow-hidden flex">
              <div class="bg-[#05c3fb] h-full" :style="{ width: `${genderRatio.male_percent}%` }"></div>
              <div class="bg-[#ec4899] h-full" :style="{ width: `${genderRatio.female_percent}%` }"></div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-[#05c3fb]"></span>
                <span class="text-[#474555]">Pria: <strong>{{ genderRatio.male_count }}</strong> ({{ genderRatio.male_percent }}%)</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-[#ec4899]"></span>
                <span class="text-[#474555]">Wanita: <strong>{{ genderRatio.female_count }}</strong> ({{ genderRatio.female_percent }}%)</span>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- ==================== TRANSACTION AUDIT & LOG TABLE ==================== -->
      <div class="bg-white rounded-2xl border border-[#c8c4d8]/40 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-[#e9edf4] flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <h3 class="font-extrabold text-sm text-[#121c2c] uppercase tracking-wider">Audit Catatan Transaksi Finansial</h3>
            <p class="text-xs text-[#777587]">Daftar 25 transaksi terakhir sesuai filter yang diterapkan.</p>
          </div>
          <span class="text-xs font-bold text-[#6c5ffc] bg-[#6c5ffc]/10 px-2.5 py-1 rounded-full">
            Total {{ revenueSummary.total_transactions_count }} Transaksi
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="border-b border-[#e9edf4] bg-[#f8faff] text-[11px] font-bold text-[#474555] uppercase tracking-wider">
                <th class="py-3 px-4">Waktu</th>
                <th class="py-3 px-4">No. Referensi</th>
                <th class="py-3 px-4">Kategori</th>
                <th class="py-3 px-4">Deskripsi Transaksi</th>
                <th class="py-3 px-4">Member / Pelanggan</th>
                <th class="py-3 px-4">Petugas / Kasir</th>
                <th class="py-3 px-4">Metode</th>
                <th class="py-3 px-4 text-right">Nominal (Rp)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#e9edf4]">
              <tr 
                v-for="trx in transactions" 
                :key="trx.id"
                class="hover:bg-[#f8faff] transition-colors"
              >
                <td class="py-3 px-4 text-[#777587] whitespace-nowrap">{{ trx.date_formatted }}</td>
                <td class="py-3 px-4 font-mono font-medium text-[#121c2c]">{{ trx.reference }}</td>
                <td class="py-3 px-4">
                  <span 
                    class="px-2 py-0.5 rounded text-[10px] font-bold"
                    :class="getCategoryBadgeClass(trx.category_key)"
                  >
                    {{ trx.category_label }}
                  </span>
                </td>
                <td class="py-3 px-4 font-medium text-[#121c2c] max-w-xs truncate">{{ trx.description }}</td>
                <td class="py-3 px-4 text-[#474555]">{{ trx.customer }}</td>
                <td class="py-3 px-4 text-[#777587]">{{ trx.staff }}</td>
                <td class="py-3 px-4 text-[#777587] uppercase text-[10px] font-bold">{{ trx.payment_method }}</td>
                <td class="py-3 px-4 text-right font-extrabold text-[#121c2c]">
                  Rp {{ trx.amount.toLocaleString('id-ID') }}
                </td>
              </tr>

              <tr v-if="!transactions.length">
                <td colspan="8" class="text-center py-8 text-xs text-[#777587]">
                  Tidak ada rekaman transaksi yang sesuai dengan filter yang dipilih.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
  kpis: {
    type: Object,
    required: true,
  },
  occupancy: {
    type: Object,
    required: true,
  },
  genderRatio: {
    type: Object,
    required: true,
  },
  revenueSummary: {
    type: Object,
    required: true,
  },
  chartData: {
    type: Array,
    default: () => [],
  },
  transactions: {
    type: Array,
    default: () => [],
  },
});

const intervals = [
  { key: 'today', label: 'Hari Ini' },
  { key: 'week', label: '7 Hari' },
  { key: 'month', label: 'Bulan Ini' },
  { key: 'year', label: 'Tahun Ini' },
  { key: 'custom', label: 'Kustom' },
];

const selectedInterval = ref(props.filters.interval || 'month');
const selectedCategory = ref(props.filters.category || 'all');
const customStartDate = ref(props.filters.start_date?.slice?.(0, 10) || '');
const customEndDate = ref(props.filters.end_date?.slice?.(0, 10) || '');

const setInterval = (intervalKey) => {
  selectedInterval.value = intervalKey;
  if (intervalKey !== 'custom') {
    applyFilter();
  }
};

const applyFilter = () => {
  const query = {
    interval: selectedInterval.value,
    category: selectedCategory.value,
  };

  if (selectedInterval.value === 'custom' && customStartDate.value && customEndDate.value) {
    query.start_date = customStartDate.value;
    query.end_date = customEndDate.value;
  }

  router.get('/reports', query, {
    preserveState: true,
    preserveScroll: true,
  });
};

const pdfExportUrl = computed(() => {
  const params = new URLSearchParams({
    interval: selectedInterval.value,
    category: selectedCategory.value,
  });
  if (selectedInterval.value === 'custom' && customStartDate.value && customEndDate.value) {
    params.set('start_date', customStartDate.value);
    params.set('end_date', customEndDate.value);
  }
  return `/reports/export/pdf?${params.toString()}`;
});

const excelExportUrl = computed(() => {
  const params = new URLSearchParams({
    interval: selectedInterval.value,
    category: selectedCategory.value,
  });
  if (selectedInterval.value === 'custom' && customStartDate.value && customEndDate.value) {
    params.set('start_date', customStartDate.value);
    params.set('end_date', customEndDate.value);
  }
  return `/reports/export/excel?${params.toString()}`;
});

const getPercentage = (value, total) => {
  if (!total) return 0;
  return Math.round((value / total) * 100);
};

const maxDailyTotal = computed(() => {
  const totals = props.chartData.map((d) => d.total);
  return Math.max(...totals, 1);
});

const getBarHeight = (total) => {
  if (!total) return '4px';
  const pct = Math.min(100, Math.max(12, Math.round((total / maxDailyTotal.value) * 100)));
  return `${pct}%`;
};

const getSegmentHeight = (segment, total) => {
  if (!total || !segment) return '0%';
  return `${Math.round((segment / total) * 100)}%`;
};

const getCategoryBadgeClass = (catKey) => {
  switch (catKey) {
    case 'gym':
      return 'bg-[#6c5ffc]/15 text-[#5242e2]';
    case 'pt':
    case 'combo':
      return 'bg-[#05c3fb]/15 text-[#0284c7]';
    case 'pos_retail':
      return 'bg-[#f7b924]/20 text-[#b58004]';
    default:
      return 'bg-slate-100 text-slate-700';
  }
};
</script>

