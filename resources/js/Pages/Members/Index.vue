<template>
  <AuthenticatedLayout>
    
    <!-- ==================== HEADER & ACTION BUTTONS ==================== -->
    <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-[#777587] text-xs font-semibold mb-1">
          <Link :href="route('dashboard')" class="hover:text-[#5242e2] transition-colors">Beranda</Link>
          <span class="material-symbols-outlined text-[14px]">chevron_right</span>
          <span>Keanggotaan</span>
          <span class="material-symbols-outlined text-[14px]">chevron_right</span>
          <span class="text-[#5242e2]">Data Member</span>
        </div>
        <h1 class="text-2xl font-bold text-[#121c2c] tracking-tight">Direktori & Manajemen Member</h1>
        <p class="text-xs text-[#474555]">
          Pencarian instan kartu RFID, nomor telepon, dan status multi-langganan aktif gym & sesi PT.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <Link 
          :href="route('members.create')"
          class="flex items-center gap-2 bg-[#6c5ffc] hover:bg-[#5242e2] text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 shadow-sm shadow-[#6c5ffc]/25 active:scale-95"
        >
          <span class="material-symbols-outlined text-[18px]">person_add</span>
          <span>Daftar Member Baru</span>
        </Link>
      </div>
    </section>

    <!-- ==================== SUMMARY KPI STATS ==================== -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-5">
      <!-- Total Member -->
      <div class="bg-white rounded-xl p-4 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)] flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-[#5242e2]/10 text-[#5242e2] flex items-center justify-center">
          <span class="material-symbols-outlined text-[24px]">group</span>
        </div>
        <div>
          <span class="text-xs font-semibold text-[#777587] block">Total Member Terdaftar</span>
          <span class="text-2xl font-bold text-[#121c2c] tnum">{{ stats.total }}</span>
        </div>
      </div>

      <!-- Member Aktif -->
      <div class="bg-white rounded-xl p-4 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)] flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-[#09ad95]/10 text-[#09ad95] flex items-center justify-center">
          <span class="material-symbols-outlined text-[24px]">how_to_reg</span>
        </div>
        <div>
          <span class="text-xs font-semibold text-[#777587] block">Member Aktif</span>
          <span class="text-2xl font-bold text-[#121c2c] tnum">{{ stats.active }}</span>
        </div>
      </div>

      <!-- Member Tidak Aktif / Suspended -->
      <div class="bg-white rounded-xl p-4 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)] flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-[#f7b924]/10 text-[#f7b924] flex items-center justify-center">
          <span class="material-symbols-outlined text-[24px]">person_off</span>
        </div>
        <div>
          <span class="text-xs font-semibold text-[#777587] block">Tidak Aktif / Non-aktif</span>
          <span class="text-2xl font-bold text-[#121c2c] tnum">{{ stats.inactive }}</span>
        </div>
      </div>
    </section>

    <!-- ==================== SEARCH & FILTERS TOOLBAR ==================== -->
    <section class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
      <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        
        <!-- Search Input with Live Debounce (< 300ms) -->
        <div class="relative flex-1 max-w-xl">
          <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[20px] text-[#777587]">search</span>
          <input 
            v-model="searchTerm"
            @input="handleSearchInput"
            type="text" 
            placeholder="Cari instan: Ketik nama, nomor HP, atau scan kartu RFID..."
            class="w-full h-11 pl-11 pr-24 text-xs font-medium rounded-xl bg-[#f0f3fb]/80 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white focus:ring-2 focus:ring-[#6c5ffc]/20 transition-all text-[#121c2c] placeholder:text-[#777587] outline-none"
            autofocus
          />
          <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5 text-[10px] text-[#09ad95] bg-[#09ad95]/10 px-2 py-1 rounded-md font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-[#09ad95] animate-pulse"></span>
            <span>RFID Ready</span>
          </div>
        </div>

        <!-- Filter Status & Reset -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-[#777587] whitespace-nowrap">Status:</span>
            <select 
              v-model="selectedStatus"
              @change="applyFilter"
              class="h-10 px-3 text-xs font-semibold rounded-xl bg-white border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none text-[#121c2c] cursor-pointer"
            >
              <option value="all">Semua Status</option>
              <option value="active">Aktif</option>
              <option value="inactive">Tidak Aktif</option>
              <option value="suspended">Ditangguhkan</option>
            </select>
          </div>

          <button 
            v-if="searchTerm || selectedStatus !== 'all'"
            @click="resetFilter"
            class="h-10 px-3 text-xs font-bold text-[#777587] hover:text-[#ba1a1a] hover:bg-[#ba1a1a]/10 rounded-xl transition-colors flex items-center gap-1"
          >
            <span class="material-symbols-outlined text-[16px]">close</span>
            <span>Reset</span>
          </button>
        </div>
      </div>
    </section>

    <!-- ==================== MEMBER TABLE ==================== -->
    <section class="bg-white rounded-xl border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)] overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-[#f9f9ff] border-b border-[#e9edf4] text-[11px] font-bold text-[#777587] uppercase tracking-wider">
              <th class="py-3.5 px-4">Member Info</th>
              <th class="py-3.5 px-4">Kontak</th>
              <th class="py-3.5 px-4">Kartu RFID</th>
              <th class="py-3.5 px-4">Paket Aktif</th>
              <th class="py-3.5 px-4 text-center">Sisa Hari</th>
              <th class="py-3.5 px-4 text-center">Sesi PT</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#e9edf4] text-xs">
            <tr 
              v-for="member in members.data" 
              :key="member.id"
              class="hover:bg-[#f0f3fb]/60 transition-colors"
            >
              <!-- Member Info -->
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl overflow-hidden bg-gradient-to-br from-[#6c5ffc]/20 to-[#09ad95]/20 flex items-center justify-center shrink-0 border border-[#e9edf4]">
                    <img 
                      v-if="member.photo_asset_url" 
                      :src="member.photo_asset_url" 
                      :alt="member.full_name"
                      class="w-full h-full object-cover"
                    />
                    <span v-else class="text-xs font-bold text-[#5242e2]">
                      {{ getInitials(member.full_name) }}
                    </span>
                  </div>

                  <div>
                    <Link 
                      :href="route('members.show', member.id)" 
                      class="font-bold text-[#121c2c] hover:text-[#5242e2] transition-colors block text-xs"
                    >
                      {{ member.full_name }}
                    </Link>
                    <div class="flex items-center gap-1.5 mt-0.5">
                      <span class="text-[10px] font-bold bg-[#6c5ffc]/10 text-[#6c5ffc] px-1.5 py-0.5 rounded font-mono">
                        {{ member.member_code }}
                      </span>
                      <span v-if="member.gender" class="text-[10px] text-[#777587]">
                        ({{ member.gender === 'male' ? 'L' : 'P' }})
                      </span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Kontak -->
              <td class="py-3.5 px-4">
                <p class="font-medium text-[#121c2c] tnum">{{ member.phone }}</p>
                <p class="text-[11px] text-[#777587] truncate max-w-[150px]">{{ member.email || '-' }}</p>
              </td>

              <!-- Kartu RFID -->
              <td class="py-3.5 px-4">
                <div v-if="member.rfid_uid" class="inline-flex items-center gap-1 text-[11px] font-mono font-bold bg-[#e8eeff] text-[#5242e2] px-2.5 py-1 rounded-lg border border-[#c8c4d8]/40">
                  <span class="material-symbols-outlined text-[14px]">contactless</span>
                  <span>{{ member.rfid_uid }}</span>
                </div>
                <span v-else class="text-[11px] text-[#777587] italic">Belum dipautkan</span>
              </td>

              <!-- Paket Aktif -->
              <td class="py-3.5 px-4">
                <div v-if="getActivePackages(member).length > 0" class="flex flex-wrap gap-1 max-w-[200px]">
                  <span 
                    v-for="sub in getActivePackages(member)" 
                    :key="sub.id"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                    :class="getPackageBadgeClass(sub.package?.type)"
                  >
                    {{ sub.package?.name }}
                  </span>
                </div>
                <span v-else class="text-[11px] text-[#ba1a1a] font-semibold bg-[#ba1a1a]/10 px-2 py-0.5 rounded-md">
                  Tidak Ada Paket
                </span>
              </td>

              <!-- Sisa Hari -->
              <td class="py-3.5 px-4 text-center">
                <span 
                  class="inline-block text-xs font-bold px-2.5 py-1 rounded-full tnum"
                  :class="getValidityBadgeClass(member.remaining_validity_days)"
                >
                  {{ member.remaining_validity_days > 0 ? member.remaining_validity_days + ' Hari' : 'Habis' }}
                </span>
              </td>

              <!-- Sesi PT -->
              <td class="py-3.5 px-4 text-center">
                <div v-if="member.pt_session_balance > 0" class="inline-flex items-center gap-1 text-xs font-bold text-[#b58004] bg-[#f7b924]/15 px-2.5 py-1 rounded-full tnum">
                  <span class="material-symbols-outlined text-[14px]">sports_gymnastics</span>
                  <span>{{ member.pt_session_balance }} Sesi</span>
                </div>
                <span v-else class="text-[11px] text-[#777587]">-</span>
              </td>

              <!-- Status -->
              <td class="py-3.5 px-4 text-center">
                <span 
                  class="text-[10px] uppercase font-bold px-2.5 py-0.5 rounded-full"
                  :class="getStatusBadgeClass(member.status)"
                >
                  {{ member.status }}
                </span>
              </td>

              <!-- Aksi -->
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <Link 
                    :href="route('members.show', member.id)"
                    class="p-1.5 text-[#5242e2] hover:bg-[#5242e2]/10 rounded-lg transition-colors"
                    title="Lihat Profil & Langganan"
                  >
                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                  </Link>

                  <Link 
                    :href="route('members.edit', member.id)"
                    class="p-1.5 text-[#09ad95] hover:bg-[#09ad95]/10 rounded-lg transition-colors"
                    title="Edit Profil"
                  >
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                  </Link>
                </div>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-if="members.data.length === 0">
              <td colspan="8" class="py-12 text-center">
                <div class="flex flex-col items-center justify-center">
                  <div class="w-14 h-14 rounded-2xl bg-[#f0f3fb] flex items-center justify-center text-[#777587] mb-3">
                    <span class="material-symbols-outlined text-[32px]">person_search</span>
                  </div>
                  <h3 class="text-sm font-bold text-[#121c2c]">Tidak Ada Member Ditemukan</h3>
                  <p class="text-xs text-[#777587] mt-1 max-w-sm">
                    Silakan ubah kata kunci pencarian nama, nomor HP, scan RFID, atau daftarkan member baru.
                  </p>
                  <Link 
                    :href="route('members.create')"
                    class="mt-4 bg-[#6c5ffc] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#5242e2] transition-colors"
                  >
                    + Daftarkan Member Baru
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ==================== PAGINATION ==================== -->
      <div v-if="members.links && members.links.length > 3" class="px-5 py-4 border-t border-[#e9edf4] flex items-center justify-between">
        <p class="text-xs text-[#777587]">
          Menampilkan <span class="font-bold text-[#121c2c]">{{ members.from || 0 }}</span> sampai <span class="font-bold text-[#121c2c]">{{ members.to || 0 }}</span> dari <span class="font-bold text-[#121c2c]">{{ members.total }}</span> member
        </p>

        <div class="flex items-center gap-1">
          <template v-for="(link, index) in members.links" :key="index">
            <Link 
              v-if="link.url"
              :href="link.url"
              class="h-8 min-w-[32px] px-2.5 rounded-lg text-xs font-semibold flex items-center justify-center transition-colors"
              :class="link.active ? 'bg-[#6c5ffc] text-white shadow-sm' : 'text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              v-html="link.label"
            />
            <span 
              v-else 
              class="h-8 min-w-[32px] px-2.5 rounded-lg text-xs text-[#777587]/50 flex items-center justify-center"
              v-html="link.label"
            />
          </template>
        </div>
      </div>
    </section>

  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  members: Object,
  filters: Object,
  stats: Object,
});

const searchTerm = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');

let debounceTimer = null;

const handleSearchInput = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    applyFilter();
  }, 250);
};

const applyFilter = () => {
  router.get(
    route('members.index'),
    {
      search: searchTerm.value || undefined,
      status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

const resetFilter = () => {
  searchTerm.value = '';
  selectedStatus.value = 'all';
  applyFilter();
};

const getInitials = (name) => {
  if (!name) return 'MB';
  return name
    .split(' ')
    .map(n => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
};

const getActivePackages = (member) => {
  if (!member.subscriptions) return [];
  return member.subscriptions.filter(s => s.status === 'active');
};

const getPackageBadgeClass = (type) => {
  switch (type) {
    case 'gym':
      return 'bg-[#09ad95]/10 text-[#09ad95] border border-[#09ad95]/20';
    case 'pt':
      return 'bg-[#f7b924]/15 text-[#b58004] border border-[#f7b924]/30';
    case 'combo':
      return 'bg-[#6c5ffc]/10 text-[#6c5ffc] border border-[#6c5ffc]/20';
    default:
      return 'bg-gray-100 text-gray-700';
  }
};

const getValidityBadgeClass = (days) => {
  if (days <= 0) {
    return 'bg-[#ba1a1a]/10 text-[#ba1a1a]';
  }
  if (days <= 7) {
    return 'bg-[#f7b924]/20 text-[#b58004]';
  }
  return 'bg-[#09ad95]/10 text-[#09ad95]';
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'active':
      return 'bg-[#09ad95]/10 text-[#09ad95]';
    case 'inactive':
      return 'bg-gray-100 text-gray-600';
    case 'suspended':
      return 'bg-[#ba1a1a]/10 text-[#ba1a1a]';
    default:
      return 'bg-gray-100 text-gray-700';
  }
};
</script>
