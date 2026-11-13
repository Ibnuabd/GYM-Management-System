<template>
  <AuthenticatedLayout>
    <div class="space-y-6">

      <!-- ==================== HEADER ==================== -->
      <section>
        <div class="flex items-center gap-1.5 text-xs text-[#777587] mb-1">
          <Link :href="route('dashboard')" class="hover:text-[#5242e2] transition-colors">Beranda</Link>
          <span class="material-symbols-outlined text-[13px]">chevron_right</span>
          <span>Laporan & Audit</span>
          <span class="material-symbols-outlined text-[13px]">chevron_right</span>
          <span class="text-[#5242e2] font-semibold">Log Kehadiran & Foto</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-[#121c2c] tracking-tight">Log Kehadiran & Foto</h1>
        <p class="text-xs text-[#777587] mt-0.5">
          Riwayat scan kartu RFID di gerbang beserta foto webcam saat member masuk dan keluar.
        </p>
      </section>

      <!-- ==================== FILTER ==================== -->
      <form
        @submit.prevent="applyFilters"
        class="bg-white rounded-2xl border border-[#e9edf4] p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end"
      >
        <div>
          <label for="start_date" class="block text-[11px] font-semibold text-[#474555] mb-1">Dari Tanggal</label>
          <input id="start_date" v-model="filterForm.start_date" type="date" class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc]" />
        </div>
        <div>
          <label for="end_date" class="block text-[11px] font-semibold text-[#474555] mb-1">Sampai Tanggal</label>
          <input id="end_date" v-model="filterForm.end_date" type="date" class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc]" />
        </div>
        <div>
          <label for="status" class="block text-[11px] font-semibold text-[#474555] mb-1">Status</label>
          <select id="status" v-model="filterForm.status" class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc] bg-white">
            <option value="">Semua</option>
            <option value="IN">IN (Masuk)</option>
            <option value="OUT">OUT (Keluar)</option>
          </select>
        </div>
        <div>
          <label for="search" class="block text-[11px] font-semibold text-[#474555] mb-1">Cari Member</label>
          <input id="search" v-model="filterForm.search" type="text" placeholder="Nama, kode member, atau RFID" class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc]" />
        </div>
        <div class="flex gap-2">
          <button type="submit" class="flex-1 h-9 rounded-xl bg-[#6c5ffc] hover:bg-[#5242e2] text-white text-xs font-bold">Terapkan</button>
          <button type="button" @click="resetFilters" class="h-9 px-3 rounded-xl bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#474555] text-xs font-semibold">Reset</button>
        </div>
        <p class="sm:col-span-2 lg:col-span-5 text-[10px] text-[#777587]">Rentang tanggal maksimal {{ maxRangeDays }} hari.</p>
      </form>

      <!-- ==================== RINGKASAN PERIODE ==================== -->
      <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-xs font-semibold text-[#777587]">Total Check-in (IN)</p>
          <p class="text-2xl font-black text-[#09ad95] tnum mt-1">{{ summary.total_checkins }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-xs font-semibold text-[#777587]">Total Check-out (OUT)</p>
          <p class="text-2xl font-black text-[#05c3fb] tnum mt-1">{{ summary.total_checkouts }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-xs font-semibold text-[#777587]">Member Unik</p>
          <p class="text-2xl font-black text-[#5242e2] tnum mt-1">{{ summary.unique_members }}</p>
        </div>
      </section>

      <!-- ==================== TABEL RIWAYAT ==================== -->
      <section class="bg-white rounded-2xl border border-[#e9edf4] p-5">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[720px]">
            <thead>
              <tr class="text-[10px] uppercase tracking-wider text-[#777587] bg-[#f8f9fc]">
                <th class="py-2.5 px-3 font-bold">Waktu Scan</th>
                <th class="py-2.5 px-3 font-bold">Foto & Member</th>
                <th class="py-2.5 px-3 font-bold">RFID</th>
                <th class="py-2.5 px-3 font-bold">Paket</th>
                <th class="py-2.5 px-3 font-bold">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f0f3f8] text-xs">
              <tr v-for="log in attendances.data" :key="log.id" class="hover:bg-[#fbfcfe] transition-colors">
                <td class="py-3 px-3 tnum">
                  <span class="font-semibold text-[#121c2c]">{{ log.scanned_date }}</span>
                  <span class="block text-[10px] text-[#777587]">{{ log.scanned_time }} • {{ log.time_ago }}</span>
                </td>
                <td class="py-3 px-3">
                  <div class="flex items-center gap-2.5">
                    <!-- Foto: klik untuk memperbesar. Jika tidak ada foto, tampilkan ikon. -->
                    <button
                      type="button"
                      class="w-10 h-10 rounded-lg overflow-hidden border border-[#e9edf4] bg-slate-100 flex-shrink-0"
                      :class="log.snapshot_url ? 'cursor-zoom-in' : 'cursor-default'"
                      :title="log.snapshot_url ? 'Lihat foto' : 'Tidak ada foto'"
                      @click="openSnapshot(log)"
                    >
                      <img v-if="log.snapshot_url" :src="log.snapshot_url" :alt="`Foto ${log.member_name}`" class="w-full h-full object-cover" loading="lazy" />
                      <span v-else class="material-symbols-outlined text-[18px] text-[#c8c4d8] leading-10">no_photography</span>
                    </button>
                    <div>
                      <p class="font-bold text-[#121c2c] leading-tight">{{ log.member_name }}</p>
                      <p class="text-[10px] text-[#777587]">{{ log.member_code }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3 font-mono text-[11px] text-[#474555]">{{ log.rfid_uid }}</td>
                <td class="py-3 px-3">
                  <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#6c5ffc]/10 text-[#6c5ffc]">{{ log.package_name }}</span>
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
              </tr>

              <!-- Tampilan kosong -->
              <tr v-if="attendances.data.length === 0">
                <td colspan="5" class="py-10 text-center text-[#777587]">
                  <span class="material-symbols-outlined text-[32px] text-[#c8c4d8] block mb-1">badge</span>
                  Tidak ada rekaman kehadiran untuk filter ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginasi: filter tetap terbawa karena URL dari server sudah berisi query filter -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 pt-3 mt-2 border-t border-[#e9edf4] text-xs">
          <span class="text-[#777587] text-[11px]">
            <template v-if="attendances.total > 0">
              Menampilkan {{ attendances.from }}–{{ attendances.to }} dari {{ attendances.total }} log
            </template>
            <template v-else>0 log</template>
          </span>
          <div class="flex items-center gap-2">
            <Link
              v-if="attendances.prev_page_url"
              :href="attendances.prev_page_url"
              preserve-scroll
              class="px-3 py-1 rounded-lg border border-[#c8c4d8]/40 text-[#474555] hover:bg-[#e8eeff]"
            >Sebelumnya</Link>
            <span class="text-[11px] text-[#777587]">Halaman {{ attendances.current_page }} dari {{ attendances.last_page }}</span>
            <Link
              v-if="attendances.next_page_url"
              :href="attendances.next_page_url"
              preserve-scroll
              class="px-3 py-1 rounded-lg border border-[#c8c4d8]/40 text-[#474555] hover:bg-[#e8eeff]"
            >Berikutnya</Link>
          </div>
        </div>
      </section>
    </div>

    <!-- ==================== MODAL PREVIEW FOTO ==================== -->
    <div
      v-if="selectedLog"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="selectedLog = null"
    >
      <div class="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl">
        <div class="p-4 border-b border-[#e9edf4] flex items-center justify-between">
          <div>
            <h4 class="text-xs font-bold text-[#121c2c]">{{ selectedLog.member_name }} ({{ selectedLog.member_code }})</h4>
            <p class="text-[10px] text-[#777587]">{{ selectedLog.scanned_date }} {{ selectedLog.scanned_time }} • {{ selectedLog.status }}</p>
          </div>
          <button type="button" @click="selectedLog = null" class="p-1 rounded-lg text-[#777587] hover:bg-slate-100" aria-label="Tutup">
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>
        <div class="p-4 bg-slate-900 flex items-center justify-center min-h-[260px]">
          <img :src="selectedLog.snapshot_url" :alt="`Foto ${selectedLog.member_name}`" class="max-w-full max-h-[400px] rounded-lg object-contain" />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  // Data paginasi Laravel: { data: [...], total, from, to, current_page, last_page, prev_page_url, next_page_url }
  attendances: Object,
  // Ringkasan periode: { total_checkins, total_checkouts, unique_members }
  summary: Object,
  // Filter yang sedang aktif: { start_date, end_date, status, search }
  filters: Object,
  maxRangeDays: Number,
});

// Isi form filter dimulai dari filter yang sedang aktif
const filterForm = ref({ ...props.filters });

// Kirim filter ke server (halaman kembali ke 1)
const applyFilters = () => {
  router.get(route('attendances.index'), filterForm.value, {
    preserveScroll: true,
    preserveState: true,
  });
};

// Kembali ke filter default (hari ini, semua status)
const resetFilters = () => {
  router.get(route('attendances.index'));
};

// Log yang fotonya sedang diperbesar (null = modal tertutup)
const selectedLog = ref(null);

const openSnapshot = (log) => {
  if (log.snapshot_url) {
    selectedLog.value = log;
  }
};
</script>
