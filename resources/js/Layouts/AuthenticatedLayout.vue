<template>
  <div class="bg-[#f0f3fb] text-[#121c2c] min-h-screen flex font-sans antialiased">
    
    <!-- ==================== SIDEBAR ==================== -->
    <aside class="fixed top-0 left-0 h-screen w-64 z-50 bg-white border-r border-[#c8c4d8]/30 flex flex-col justify-between custom-scroll overflow-y-auto shadow-sm">
      <div>
        <!-- Brand Logo Header -->
        <div class="h-16 flex items-center px-6 border-b border-[#c8c4d8]/30 gap-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#6c5ffc] to-[#5242e2] flex items-center justify-center text-white shadow-md shadow-[#6c5ffc]/20">
            <span class="material-symbols-outlined text-[22px]">fitness_center</span>
          </div>
          <div>
            <span class="font-bold text-base text-[#121c2c] tracking-tight block leading-none">FitCore GMS</span>
            <span class="text-[10px] text-[#777587] font-semibold tracking-wider uppercase">Integrated Gym Suite</span>
          </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="px-4 py-4 space-y-5">
          <!-- UTAMA -->
          <div>
            <span class="block px-3 mb-2 text-[10px] uppercase tracking-wider text-[#777587] font-bold">UTAMA</span>
            <div class="space-y-1">
              <Link 
                :href="route('dashboard')"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-xs transition-all duration-150"
                :class="$page.url.startsWith('/dashboard') || $page.url === '/' ? 'bg-[#6c5ffc] text-white shadow-sm font-semibold' : 'text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                <span>Dashboard Utama</span>
                <span v-if="$page.url.startsWith('/dashboard')" class="ml-auto text-[9px] bg-white/20 text-white font-bold px-1.5 py-0.5 rounded-full">Live</span>
              </Link>
            </div>
          </div>

          <!-- OPERASIONAL -->
          <div v-if="hasAnyPermission(['scan_attendance', 'pos_cashier', 'manage_pt_sessions'])">
            <span class="block px-3 mb-2 text-[10px] uppercase tracking-wider text-[#777587] font-bold">OPERASIONAL</span>
            <div class="space-y-1">
              <Link 
                v-if="hasPermission('scan_attendance')"
                :href="route('kiosk')"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] transition-colors duration-150 font-medium text-xs"
              >
                <span class="material-symbols-outlined text-[18px] text-[#09ad95]">badge</span>
                <span>Kios Kehadiran (RFID)</span>
                <span class="ml-auto w-2 h-2 rounded-full bg-[#09ad95] ring-2 ring-[#09ad95]/20 animate-pulse"></span>
              </Link>
              
              <Link 
                v-if="hasPermission('pos_cashier')"
                :href="route('pos')"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] transition-colors duration-150 font-medium text-xs"
                :class="$page.url.startsWith('/pos') ? 'bg-[#6c5ffc] text-white shadow-sm font-semibold' : ''"
              >
                <span class="material-symbols-outlined text-[18px]" :class="$page.url.startsWith('/pos') ? 'text-white' : 'text-[#6c5ffc]'">point_of_sale</span>
                <span>Kasir POS & Ritel</span>
              </Link>

              <Link 
                v-if="hasPermission('pos_cashier')"
                :href="route('products.index')"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] transition-colors duration-150 font-medium text-xs"
                :class="$page.url.startsWith('/products') ? 'bg-[#6c5ffc] text-white shadow-sm font-semibold' : ''"
              >
                <span class="material-symbols-outlined text-[18px]" :class="$page.url.startsWith('/products') ? 'text-white' : 'text-[#5242e2]'">inventory_2</span>
                <span>Katalog Produk & Stok</span>
              </Link>

              <Link 
                v-if="hasPermission('manage_pt_sessions')"
                :href="route('pt.dashboard')"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] transition-colors duration-150 font-medium text-xs"
              >
                <span class="material-symbols-outlined text-[18px] text-[#f7b924]">sports_gymnastics</span>
                <span>Sesi Personal Trainer</span>
              </Link>
            </div>
          </div>

          <!-- KEANGGOTAAN -->
          <div v-if="hasAnyPermission(['manage_members', 'manage_subscriptions'])">
            <span class="block px-3 mb-2 text-[10px] uppercase tracking-wider text-[#777587] font-bold">KEANGGOTAAN</span>
            <div class="space-y-1">
              <Link 
                :href="route('members.index')" 
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-xs transition-colors duration-150"
                :class="$page.url.startsWith('/members') ? 'bg-[#6c5ffc] text-white shadow-sm font-semibold' : 'text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                <span class="material-symbols-outlined text-[18px]" :class="$page.url.startsWith('/members') ? 'text-white' : 'text-[#03c892]'">group</span>
                <span>Data Member</span>
              </Link>
              <Link 
                :href="route('packages.index')" 
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-xs transition-colors duration-150"
                :class="$page.url.startsWith('/packages') ? 'bg-[#6c5ffc] text-white shadow-sm font-semibold' : 'text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                <span class="material-symbols-outlined text-[18px]" :class="$page.url.startsWith('/packages') ? 'text-white' : 'text-[#05c3fb]'">card_membership</span>
                <span>Paket Langganan</span>
              </Link>
            </div>
          </div>

          <!-- LAPORAN & AUDIT -->
          <div v-if="hasPermission('view_reports')">
            <span class="block px-3 mb-2 text-[10px] uppercase tracking-wider text-[#777587] font-bold">LAPORAN & AUDIT</span>
            <div class="space-y-1">
              <Link 
                :href="route('reports')" 
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition-colors duration-150 font-medium text-xs"
                :class="$page.url.startsWith('/reports') ? 'bg-[#6c5ffc] text-white shadow-sm font-semibold' : 'text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                <span class="material-symbols-outlined text-[18px]" :class="$page.url.startsWith('/reports') ? 'text-white' : 'text-[#6c5ffc]'">query_stats</span>
                <span>Laporan Pendapatan</span>
              </Link>
              <Link
                :href="route('attendances.index')"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition-colors duration-150 font-medium text-xs"
                :class="$page.url.startsWith('/attendances') ? 'bg-[#6c5ffc] text-white shadow-sm font-semibold' : 'text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                <span class="material-symbols-outlined text-[18px]" :class="$page.url.startsWith('/attendances') ? 'text-white' : 'text-[#09ad95]'">camera_indoor</span>
                <span>Log Kehadiran & Foto</span>
              </Link>
            </div>
          </div>

          <!-- PENGATURAN -->
          <div v-if="hasPermission('manage_users') || hasPermission('view_activity_logs')">
            <span class="block px-3 mb-2 text-[10px] uppercase tracking-wider text-[#777587] font-bold">PENGATURAN</span>
            <div class="space-y-1">
              <Link
                v-if="hasPermission('manage_users')"
                :href="route('users.index')"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition-colors duration-150 font-medium text-xs"
                :class="$page.url.startsWith('/users') ? 'bg-[#6c5ffc] text-white shadow-sm font-semibold' : 'text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                <span class="material-symbols-outlined text-[18px]" :class="$page.url.startsWith('/users') ? 'text-white' : 'text-[#5242e2]'">manage_accounts</span>
                <span>Manajemen User (RBAC)</span>
              </Link>

              <!-- Log Aktivitas semua staf (issue #28), hanya admin -->
              <Link
                v-if="hasPermission('view_activity_logs')"
                :href="route('activity-logs.index')"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition-colors duration-150 font-medium text-xs"
                :class="$page.url.startsWith('/activity-logs') ? 'bg-[#6c5ffc] text-white shadow-sm font-semibold' : 'text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                <span class="material-symbols-outlined text-[18px]" :class="$page.url.startsWith('/activity-logs') ? 'text-white' : 'text-[#5242e2]'">manage_history</span>
                <span>Log Aktivitas</span>
              </Link>
            </div>
          </div>
        </nav>
      </div>

      <!-- Hardware Status Badge -->
      <div class="p-4 border-t border-[#c8c4d8]/30">
        <div class="p-3 rounded-xl bg-gradient-to-br from-[#6c5ffc]/10 via-[#e8eeff] to-[#09ad95]/10 border border-[#6c5ffc]/20 text-center">
          <div class="flex items-center justify-center gap-2 mb-1">
            <span class="w-2 h-2 rounded-full bg-[#09ad95] animate-ping"></span>
            <span class="w-2 h-2 rounded-full bg-[#09ad95] -ml-4"></span>
            <span class="text-xs font-bold text-[#121c2c]">Kios Gate Online</span>
          </div>
          <p class="text-[10px] text-[#474555]">RFID USB & Webcam 60fps Terhubung</p>
        </div>
      </div>
    </aside>

    <!-- ==================== MAIN WRAPPER ==================== -->
    <div class="flex-1 ml-64 flex flex-col min-h-screen">
      
      <!-- Top Header Bar -->
      <header class="sticky top-0 z-40 h-16 w-full bg-white border-b border-[#c8c4d8]/30 px-6 flex items-center justify-between shadow-sm">
        
        <!-- Search Member Input with Quick Search Popup -->
        <div class="relative w-80">
          <div class="relative w-full">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[#777587]">search</span>
            <input 
              v-model="headerSearchQuery"
              @input="handleHeaderSearch"
              @keydown.enter="submitHeaderSearch"
              @focus="isSearchFocused = true"
              type="text" 
              placeholder="Cari Member (Nama, RFID, HP)..." 
              class="w-full h-[38px] pl-9 pr-8 text-xs rounded-lg bg-[#f0f3fb]/70 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white focus:ring-2 focus:ring-[#6c5ffc]/20 transition-all text-[#121c2c] placeholder:text-[#777587] outline-none"
            />
            <span v-if="isHeaderSearching" class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 border-2 border-[#6c5ffc] border-t-transparent rounded-full animate-spin"></span>
          </div>

          <!-- Quick Results Dropdown -->
          <div 
            v-if="isSearchFocused && (headerSearchResults.length > 0 || isHeaderSearching)"
            class="absolute left-0 right-0 top-full mt-1.5 bg-white rounded-xl shadow-xl border border-[#e9edf4] py-2 z-50 max-h-72 overflow-y-auto"
          >
            <div 
              v-for="res in headerSearchResults" 
              :key="res.id"
              @mousedown="navigateToMember(res.id)"
              class="px-3 py-2 hover:bg-[#f0f3fb] cursor-pointer flex items-center justify-between transition-colors text-xs"
            >
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-[#6c5ffc]/15 text-[#5242e2] flex items-center justify-center font-bold text-[10px]">
                  {{ res.full_name.substring(0, 2).toUpperCase() }}
                </div>
                <div>
                  <p class="font-bold text-[#121c2c] leading-tight">{{ res.full_name }}</p>
                  <p class="text-[10px] text-[#777587] font-mono leading-tight">{{ res.member_code }} • {{ res.phone }}</p>
                </div>
              </div>
              <span 
                class="text-[9px] font-bold px-1.5 py-0.5 rounded"
                :class="res.has_active_subscription ? 'bg-[#09ad95]/15 text-[#09ad95]' : 'bg-gray-100 text-gray-500'"
              >
                {{ res.has_active_subscription ? 'Aktif' : 'Non-aktif' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Action Items & User Profile -->
        <div class="flex items-center gap-3">
          
          <!-- Quick Kiosk Gate Button -->
          <Link 
            v-if="hasPermission('scan_attendance')"
            :href="route('kiosk')"
            class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#09ad95]/10 text-[#09ad95] hover:bg-[#09ad95]/20 font-bold text-xs transition-colors"
            title="Buka Kios Kehadiran Mandiri"
          >
            <span class="material-symbols-outlined text-[16px]">qr_code_scanner</span>
            <span>Buka Layar Kios</span>
          </Link>

          <!-- Role Badge -->
          <span 
            class="text-[10px] uppercase font-bold px-2.5 py-1 rounded-full border tracking-wider"
            :class="getRoleBadgeClass(primaryRole)"
          >
            {{ primaryRole }}
          </span>

          <div class="h-6 w-px bg-[#c8c4d8]/40"></div>

          <!-- User Profile Dropdown -->
          <div class="relative flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-[#6c5ffc] text-white flex items-center justify-center font-bold text-xs shadow-sm">
              {{ getUserInitials }}
            </div>
            <div class="hidden sm:block text-left">
              <p class="text-xs font-bold text-[#121c2c] leading-tight">{{ user?.name }}</p>
              <p class="text-[10px] text-[#777587] leading-tight">{{ user?.email }}</p>
            </div>
            
            <!-- Logout Button -->
            <button 
              @click="logout" 
              class="ml-2 p-1.5 text-[#777587] hover:text-[#ba1a1a] hover:bg-[#ffdad6]/40 rounded-lg transition-colors"
              title="Keluar dari Aplikasi"
            >
              <span class="material-symbols-outlined text-[20px]">logout</span>
            </button>
          </div>
        </div>
      </header>

      <!-- Pop-up Notifikasi (toast) di pojok kanan atas. Isinya dari flash success / error / info. -->
      <div class="fixed top-4 right-4 z-[60] flex flex-col gap-2 w-[min(360px,calc(100vw-2rem))]" aria-live="polite">
        <TransitionGroup
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 translate-x-4"
          leave-active-class="transition duration-150 ease-in"
          leave-to-class="opacity-0 translate-x-4"
        >
          <div
            v-for="toast in toasts"
            :key="toast.id"
            role="alert"
            class="flex items-start gap-2.5 p-3.5 rounded-xl border shadow-lg bg-white text-xs font-semibold"
            :class="toastStyles[toast.type].box"
          >
            <span class="material-symbols-outlined text-[20px] shrink-0" :class="toastStyles[toast.type].icon">
              {{ toastStyles[toast.type].iconName }}
            </span>
            <p class="flex-1 leading-snug text-[#121c2c]">{{ toast.message }}</p>
            <button
              type="button"
              @click="dismissToast(toast.id)"
              class="shrink-0 text-[#777587] hover:text-[#121c2c]"
              aria-label="Tutup notifikasi"
            >
              <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
          </div>
        </TransitionGroup>
      </div>

      <!-- Main Slot Content -->
      <main class="flex-1 p-6 space-y-6">
        <slot />
      </main>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import { route } from '@/route';

// ==================== POP-UP NOTIFIKASI (TOAST) ====================
// Server mengirim pesan lewat flash: ->with('success' | 'error' | 'info', 'pesan').
// Setiap pesan baru ditampilkan sebagai pop-up, lalu hilang sendiri setelah 5 detik.
const TOAST_DURATION_MS = 5000;

const toastStyles = {
  success: { box: 'border-[#09ad95]/40', icon: 'text-[#09ad95]', iconName: 'check_circle' },
  error: { box: 'border-[#ba1a1a]/40', icon: 'text-[#ba1a1a]', iconName: 'block' },
  info: { box: 'border-[#05c3fb]/50', icon: 'text-[#05c3fb]', iconName: 'info' },
};

const toasts = ref([]);
let lastToastId = 0;

const dismissToast = (id) => {
  toasts.value = toasts.value.filter((toast) => toast.id !== id);
};

const showToast = (type, message) => {
  if (!message) return;
  const id = ++lastToastId;
  toasts.value.push({ id, type, message });
  setTimeout(() => dismissToast(id), TOAST_DURATION_MS);
};

const page = usePage();

// Tampilkan pop-up setiap kali server mengirim flash baru (setelah pindah halaman / submit form).
watch(
  () => page.props.flash,
  (flash) => {
    showToast('success', flash?.success);
    showToast('error', flash?.error);
    showToast('info', flash?.info);
  },
  { immediate: true },
);
const user = computed(() => page.props.auth?.user);
const roles = computed(() => user.value?.roles || []);
const permissions = computed(() => user.value?.permissions || []);

// Header Quick Search State
const headerSearchQuery = ref('');
const headerSearchResults = ref([]);
const isHeaderSearching = ref(false);
const isSearchFocused = ref(false);
let headerSearchTimer = null;

const handleHeaderSearch = () => {
  const query = headerSearchQuery.value.trim();
  if (!query) {
    headerSearchResults.value = [];
    return;
  }

  clearTimeout(headerSearchTimer);
  headerSearchTimer = setTimeout(async () => {
    isHeaderSearching.value = true;
    try {
      const res = await fetch(`${route('members.quick-search')}?q=${encodeURIComponent(query)}`);
      headerSearchResults.value = await res.json();
    } catch (err) {
      console.error('Quick search error:', err);
    } finally {
      isHeaderSearching.value = false;
    }
  }, 250);
};

const submitHeaderSearch = () => {
  if (headerSearchQuery.value.trim()) {
    isSearchFocused.value = false;
    router.get(route('members.index'), { search: headerSearchQuery.value.trim() });
  }
};

const navigateToMember = (memberId) => {
  isSearchFocused.value = false;
  headerSearchQuery.value = '';
  router.visit(route('members.show', memberId));
};

const closeDropdownOnOutsideClick = (e) => {
  if (!e.target.closest('.relative.w-80')) {
    isSearchFocused.value = false;
  }
};

onMounted(() => {
  window.addEventListener('click', closeDropdownOnOutsideClick);
});

onUnmounted(() => {
  window.removeEventListener('click', closeDropdownOnOutsideClick);
});

const primaryRole = computed(() => {
  if (roles.value.includes('admin')) return 'Admin';
  if (roles.value.includes('supervisor')) return 'Supervisor';
  if (roles.value.includes('cs')) return 'Customer Service';
  if (roles.value.includes('pt')) return 'Personal Trainer';
  return roles.value[0] || 'Staff';
});

const getUserInitials = computed(() => {
  if (!user.value?.name) return 'FC';
  return user.value.name
    .split(' ')
    .map(n => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
});

const hasPermission = (permission) => {
  if (roles.value.includes('admin')) return true;
  return permissions.value.includes(permission);
};

const hasAnyPermission = (permList) => {
  if (roles.value.includes('admin')) return true;
  return permList.some(p => permissions.value.includes(p));
};

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'Admin':
      return 'bg-[#6c5ffc]/10 text-[#6c5ffc] border-[#6c5ffc]/20';
    case 'Supervisor':
      return 'bg-[#09ad95]/10 text-[#09ad95] border-[#09ad95]/20';
    case 'Customer Service':
      return 'bg-[#05c3fb]/10 text-[#05c3fb] border-[#05c3fb]/20';
    case 'Personal Trainer':
      return 'bg-[#f7b924]/15 text-[#b58004] border-[#f7b924]/30';
    default:
      return 'bg-gray-100 text-gray-700 border-gray-200';
  }
};

const logout = () => {
  router.post(route('logout'));
};
</script>
