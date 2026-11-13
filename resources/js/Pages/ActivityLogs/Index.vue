<template>
  <AuthenticatedLayout>
    <div class="space-y-6">

      <!-- ==================== HEADER ==================== -->
      <section>
        <div class="flex items-center gap-1.5 text-xs text-[#777587] mb-1">
          <Link :href="route('dashboard')" class="hover:text-[#5242e2] transition-colors">Beranda</Link>
          <span class="material-symbols-outlined text-[13px]">chevron_right</span>
          <span>Pengaturan</span>
          <span class="material-symbols-outlined text-[13px]">chevron_right</span>
          <span class="text-[#5242e2] font-semibold">Log Aktivitas</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-[#121c2c] tracking-tight">Log Aktivitas Staf</h1>
        <p class="text-xs text-[#777587] mt-0.5">
          Riwayat siapa melakukan apa: login, perubahan data member, paket, produk, transaksi kasir, sesi PT, dan manajemen user.
        </p>
      </section>

      <!-- ==================== FILTER ==================== -->
      <form
        @submit.prevent="applyFilters"
        class="bg-white rounded-2xl border border-[#e9edf4] p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end"
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
          <label for="user_id" class="block text-[11px] font-semibold text-[#474555] mb-1">User</label>
          <select id="user_id" v-model="filterForm.user_id" class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc] bg-white">
            <option :value="null">Semua User</option>
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
        </div>
        <div>
          <label for="role" class="block text-[11px] font-semibold text-[#474555] mb-1">Role</label>
          <select id="role" v-model="filterForm.role" class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc] bg-white">
            <option :value="null">Semua Role</option>
            <option v-for="role in roles" :key="role" :value="role">{{ roleLabels[role] }}</option>
          </select>
        </div>
        <div>
          <label for="area" class="block text-[11px] font-semibold text-[#474555] mb-1">Jenis Aktivitas</label>
          <select id="area" v-model="filterForm.area" class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc] bg-white">
            <option :value="null">Semua Aktivitas</option>
            <option v-for="(label, key) in areas" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div>
          <label for="search" class="block text-[11px] font-semibold text-[#474555] mb-1">Cari Deskripsi</label>
          <input id="search" v-model="filterForm.search" type="text" placeholder="Contoh: Budi, TRX-..." class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc]" />
        </div>
        <div class="sm:col-span-2 lg:col-span-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <p class="text-[10px] text-[#777587]">Rentang tanggal maksimal {{ maxRangeDays }} hari. Default: 7 hari terakhir.</p>
          <div class="flex gap-2">
            <button type="button" @click="resetFilters" class="h-9 px-4 rounded-xl bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#474555] text-xs font-semibold">Reset</button>
            <button type="submit" class="h-9 px-5 rounded-xl bg-[#6c5ffc] hover:bg-[#5242e2] text-white text-xs font-bold">Terapkan</button>
          </div>
        </div>
      </form>

      <!-- ==================== RINGKASAN (sesuai rentang tanggal) ==================== -->
      <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-xs font-semibold text-[#777587]">Total Aktivitas</p>
          <p class="text-2xl font-black text-[#5242e2] tnum mt-1">{{ summary.total_activities }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-xs font-semibold text-[#777587]">User Aktif</p>
          <p class="text-2xl font-black text-[#09ad95] tnum mt-1">{{ summary.active_users }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-xs font-semibold text-[#777587]">Login Gagal</p>
          <p class="text-2xl font-black tnum mt-1" :class="summary.failed_logins > 0 ? 'text-[#ba1a1a]' : 'text-[#121c2c]'">{{ summary.failed_logins }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-xs font-semibold text-[#777587]">VOID Transaksi</p>
          <p class="text-2xl font-black tnum mt-1" :class="summary.pos_voids > 0 ? 'text-[#f7b924]' : 'text-[#121c2c]'">{{ summary.pos_voids }}</p>
        </div>
      </section>

      <!-- ==================== TABEL LOG ==================== -->
      <section class="bg-white rounded-2xl border border-[#e9edf4] p-5">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[860px]">
            <thead>
              <tr class="text-[10px] uppercase tracking-wider text-[#777587] bg-[#f8f9fc]">
                <th class="py-2.5 px-3 font-bold">Waktu</th>
                <th class="py-2.5 px-3 font-bold">User</th>
                <th class="py-2.5 px-3 font-bold">Aktivitas</th>
                <th class="py-2.5 px-3 font-bold">Deskripsi</th>
                <th class="py-2.5 px-3 font-bold">IP</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f0f3f8] text-xs">
              <!-- Klik baris untuk melihat detail -->
              <tr
                v-for="log in logs.data"
                :key="log.id"
                @click="selectedLog = log"
                class="hover:bg-[#fbfcfe] transition-colors cursor-pointer"
              >
                <td class="py-3 px-3 tnum whitespace-nowrap">
                  <span class="font-semibold text-[#121c2c]">{{ log.date }}</span>
                  <span class="block text-[10px] text-[#777587]">{{ log.time }} • {{ log.time_ago }}</span>
                </td>
                <td class="py-3 px-3">
                  <p class="font-bold text-[#121c2c] leading-tight">{{ log.user_name }}</p>
                  <span v-if="log.user_role" class="inline-flex mt-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold" :class="roleBadges[log.user_role] ?? 'bg-slate-100 text-slate-500'">
                    {{ roleLabels[log.user_role] ?? log.user_role }}
                  </span>
                </td>
                <td class="py-3 px-3 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold" :class="areaStyle(log).badge">
                    <span class="material-symbols-outlined text-[12px]">{{ areaStyle(log).icon }}</span>
                    {{ log.area_label }}
                  </span>
                  <span class="block text-[9px] text-[#777587] font-mono mt-0.5">{{ log.action }}</span>
                </td>
                <td class="py-3 px-3 text-[#474555]">{{ log.description }}</td>
                <td class="py-3 px-3 font-mono text-[10px] text-[#777587]">{{ log.ip_address ?? '-' }}</td>
              </tr>

              <!-- Tampilan kosong -->
              <tr v-if="logs.data.length === 0">
                <td colspan="5" class="py-10 text-center text-[#777587]">
                  <span class="material-symbols-outlined text-[32px] text-[#c8c4d8] block mb-1">manage_history</span>
                  Tidak ada aktivitas untuk filter ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginasi: filter tetap terbawa karena URL dari server sudah berisi query filter -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 pt-3 mt-2 border-t border-[#e9edf4] text-xs">
          <span class="text-[#777587] text-[11px]">
            <template v-if="logs.total > 0">Menampilkan {{ logs.from }}–{{ logs.to }} dari {{ logs.total }} aktivitas</template>
            <template v-else>0 aktivitas</template>
          </span>
          <div class="flex items-center gap-2">
            <Link v-if="logs.prev_page_url" :href="logs.prev_page_url" preserve-scroll class="px-3 py-1 rounded-lg border border-[#c8c4d8]/40 text-[#474555] hover:bg-[#e8eeff]">Sebelumnya</Link>
            <span class="text-[11px] text-[#777587]">Halaman {{ logs.current_page }} dari {{ logs.last_page }}</span>
            <Link v-if="logs.next_page_url" :href="logs.next_page_url" preserve-scroll class="px-3 py-1 rounded-lg border border-[#c8c4d8]/40 text-[#474555] hover:bg-[#e8eeff]">Berikutnya</Link>
          </div>
        </div>
      </section>
    </div>

    <!-- ==================== MODAL DETAIL LOG ==================== -->
    <div v-if="selectedLog" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" @click.self="selectedLog = null">
      <div class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-[#e9edf4]">
        <div class="p-5 border-b border-[#e9edf4] flex items-start justify-between gap-3 bg-slate-50">
          <div>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold" :class="areaStyle(selectedLog).badge">
              <span class="material-symbols-outlined text-[12px]">{{ areaStyle(selectedLog).icon }}</span>
              {{ selectedLog.area_label }}
            </span>
            <h3 class="text-sm font-bold text-[#121c2c] mt-1.5">{{ selectedLog.description }}</h3>
          </div>
          <button type="button" @click="selectedLog = null" class="text-slate-400 hover:text-slate-700" aria-label="Tutup">
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>

        <div class="p-5 space-y-4 text-xs">
          <!-- Info dasar -->
          <dl class="grid grid-cols-[110px_1fr] gap-y-1.5">
            <dt class="text-[#777587]">Waktu</dt>
            <dd class="text-[#121c2c] font-semibold tnum">{{ selectedLog.date }} {{ selectedLog.time }}</dd>
            <dt class="text-[#777587]">User</dt>
            <dd class="text-[#121c2c] font-semibold">{{ selectedLog.user_name }} <span v-if="selectedLog.user_role" class="text-[#777587] font-normal">({{ roleLabels[selectedLog.user_role] ?? selectedLog.user_role }})</span></dd>
            <dt class="text-[#777587]">Kode Aksi</dt>
            <dd class="font-mono text-[#474555]">{{ selectedLog.action }}</dd>
            <template v-if="selectedLog.subject">
              <dt class="text-[#777587]">Data</dt>
              <dd class="font-mono text-[#474555]">{{ selectedLog.subject }}</dd>
            </template>
            <dt class="text-[#777587]">IP</dt>
            <dd class="font-mono text-[#474555]">{{ selectedLog.ip_address ?? '-' }}</dd>
            <dt class="text-[#777587]">Browser</dt>
            <dd class="text-[10px] text-[#777587] break-all">{{ selectedLog.user_agent ?? '-' }}</dd>
          </dl>

          <!-- Perubahan data: Sebelum -> Sesudah -->
          <div v-if="changedFields.length > 0">
            <p class="font-bold text-[#121c2c] mb-1.5">Perubahan Data</p>
            <table class="w-full border border-[#e9edf4] rounded-lg overflow-hidden">
              <thead>
                <tr class="text-[10px] uppercase tracking-wider text-[#777587] bg-[#f8f9fc]">
                  <th class="py-2 px-2.5 text-left font-bold">Field</th>
                  <th class="py-2 px-2.5 text-left font-bold">Sebelum</th>
                  <th class="py-2 px-2.5 text-left font-bold">Sesudah</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#f0f3f8]">
                <tr v-for="field in changedFields" :key="field">
                  <td class="py-2 px-2.5 font-mono text-[10px] text-[#474555]">{{ field }}</td>
                  <td class="py-2 px-2.5 text-[#ba1a1a] break-all">{{ formatValue(selectedLog.properties.old?.[field]) }}</td>
                  <td class="py-2 px-2.5 text-[#09ad95] font-semibold break-all">{{ formatValue(selectedLog.properties.new?.[field]) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Data tambahan lain (selain old / new) -->
          <div v-if="extraProperties.length > 0">
            <p class="font-bold text-[#121c2c] mb-1.5">Detail Tambahan</p>
            <dl class="grid grid-cols-[140px_1fr] gap-y-1 rounded-lg bg-[#f8f9fc] p-3">
              <template v-for="[key, value] in extraProperties" :key="key">
                <dt class="font-mono text-[10px] text-[#777587]">{{ key }}</dt>
                <dd class="text-[#121c2c] break-all">{{ formatValue(value) }}</dd>
              </template>
            </dl>
          </div>

          <p v-if="changedFields.length === 0 && extraProperties.length === 0" class="text-[11px] text-[#777587]">Tidak ada detail tambahan untuk aktivitas ini.</p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from '@/route';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  // Data paginasi Laravel: { data: [...], total, from, to, current_page, last_page, prev_page_url, next_page_url }
  logs: Object,
  // Ringkasan rentang tanggal: { total_activities, active_users, failed_logins, pos_voids }
  summary: Object,
  // Filter aktif: { start_date, end_date, user_id, role, area, search }
  filters: Object,
  // Pilihan dropdown
  users: Array, // [{ id, name }]
  roles: Array, // ['admin', 'supervisor', 'cs', 'pt']
  areas: Object, // { auth: 'Login & Logout', member: 'Member', ... }
  maxRangeDays: Number,
});

// ---------------- Label & warna ----------------
const roleLabels = {
  admin: 'Admin',
  supervisor: 'Supervisor',
  cs: 'CS / Frontliner',
  pt: 'Personal Trainer',
};

const roleBadges = {
  admin: 'bg-[#5242e2]/10 text-[#5242e2]',
  supervisor: 'bg-[#f7b924]/15 text-[#b8860b]',
  cs: 'bg-[#09ad95]/10 text-[#09ad95]',
  pt: 'bg-[#05c3fb]/10 text-[#0596c2]',
};

// Warna & ikon per jenis aktivitas (area = awalan action, contoh "pos" dari "pos.voided")
const areaStyles = {
  auth: { badge: 'bg-slate-100 text-slate-600', icon: 'login' },
  member: { badge: 'bg-[#09ad95]/10 text-[#09ad95]', icon: 'group' },
  subscription: { badge: 'bg-[#6c5ffc]/10 text-[#6c5ffc]', icon: 'card_membership' },
  package: { badge: 'bg-[#6c5ffc]/10 text-[#5242e2]', icon: 'inventory_2' },
  product: { badge: 'bg-[#05c3fb]/10 text-[#0596c2]', icon: 'shopping_bag' },
  pos: { badge: 'bg-[#f7b924]/15 text-[#b8860b]', icon: 'point_of_sale' },
  pt: { badge: 'bg-[#f7b924]/15 text-[#b8860b]', icon: 'fitness_center' },
  report: { badge: 'bg-slate-100 text-slate-600', icon: 'download' },
  user: { badge: 'bg-[#5242e2]/10 text-[#5242e2]', icon: 'manage_accounts' },
};

// Aksi yang perlu perhatian (gagal / pembatalan / penonaktifan) diberi warna merah.
const isWarningAction = (action) => ['auth.login_failed', 'pos.voided', 'user.deactivated', 'member.deleted'].includes(action);

const areaStyle = (log) => {
  if (isWarningAction(log.action)) {
    return { badge: 'bg-[#ba1a1a]/10 text-[#ba1a1a]', icon: 'warning' };
  }
  return areaStyles[log.area] ?? { badge: 'bg-slate-100 text-slate-600', icon: 'history' };
};

// Tampilkan nilai apa pun sebagai teks yang mudah dibaca
const formatValue = (value) => {
  if (value === null || value === undefined || value === '') return '—';
  if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
};

// ---------------- Filter ----------------
const filterForm = ref({ ...props.filters });

const applyFilters = () => {
  // Hanya kirim filter yang terisi, supaya URL tetap rapi
  const query = Object.fromEntries(Object.entries(filterForm.value).filter(([, value]) => value !== null && value !== ''));
  router.get(route('activity-logs.index'), query, { preserveScroll: true, preserveState: true });
};

const resetFilters = () => {
  router.get(route('activity-logs.index'));
};

// ---------------- Modal detail ----------------
const selectedLog = ref(null);

// Daftar field yang berubah (gabungan kunci "old" dan "new")
const changedFields = computed(() => {
  const properties = selectedLog.value?.properties ?? {};
  return [...new Set([...Object.keys(properties.old ?? {}), ...Object.keys(properties.new ?? {})])];
});

// Data tambahan selain "old" & "new", contoh: { total_amount: 150000, void_reason: '...' }
const extraProperties = computed(() => {
  const properties = selectedLog.value?.properties ?? {};
  return Object.entries(properties).filter(([key]) => key !== 'old' && key !== 'new');
});
</script>
