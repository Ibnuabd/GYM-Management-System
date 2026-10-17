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
              >
                <span class="material-symbols-outlined text-[18px] text-[#6c5ffc]">point_of_sale</span>
                <span>Kasir POS & Ritel</span>
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
              <a 
                href="#members" 
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] transition-colors duration-150 font-medium text-xs"
              >
                <span class="material-symbols-outlined text-[18px] text-[#03c892]">group</span>
                <span>Data Member</span>
              </a>
              <a 
                href="#packages" 
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] transition-colors duration-150 font-medium text-xs"
              >
                <span class="material-symbols-outlined text-[18px] text-[#05c3fb]">card_membership</span>
                <span>Paket Langganan</span>
              </a>
            </div>
          </div>

          <!-- LAPORAN & AUDIT -->
          <div v-if="hasPermission('view_reports')">
            <span class="block px-3 mb-2 text-[10px] uppercase tracking-wider text-[#777587] font-bold">LAPORAN & AUDIT</span>
            <div class="space-y-1">
              <Link 
                :href="route('reports')" 
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] transition-colors duration-150 font-medium text-xs"
              >
                <span class="material-symbols-outlined text-[18px] text-[#6c5ffc]">query_stats</span>
                <span>Laporan Pendapatan</span>
              </Link>
              <a 
                href="#attendance-logs" 
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] transition-colors duration-150 font-medium text-xs"
              >
                <span class="material-symbols-outlined text-[18px] text-[#09ad95]">camera_indoor</span>
                <span>Log Kehadiran & Foto</span>
              </a>
            </div>
          </div>

          <!-- PENGATURAN -->
          <div v-if="hasPermission('manage_users')">
            <span class="block px-3 mb-2 text-[10px] uppercase tracking-wider text-[#777587] font-bold">PENGATURAN</span>
            <div class="space-y-1">
              <Link 
                :href="route('users')" 
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] transition-colors duration-150 font-medium text-xs"
              >
                <span class="material-symbols-outlined text-[18px] text-[#5242e2]">manage_accounts</span>
                <span>Manajemen User (RBAC)</span>
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
        
        <!-- Search Member Input -->
        <div class="flex items-center gap-3 w-80">
          <div class="relative w-full">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[#777587]">search</span>
            <input 
              type="text" 
              placeholder="Cari Member (Nama, RFID, HP)..." 
              class="w-full h-[38px] pl-9 pr-3 text-xs rounded-lg bg-[#f0f3fb]/70 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 transition-all text-[#121c2c] placeholder:text-[#777587] outline-none"
            />
          </div>
        </div>

        <!-- Action Items & User Profile -->
        <div class="flex items-center gap-3">
          
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

      <!-- Flash Notification Alerts -->
      <div v-if="$page.props.flash?.success" class="mx-6 mt-4 p-3 bg-[#09ad95]/10 border border-[#09ad95]/30 text-[#09ad95] rounded-xl text-xs font-semibold flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">check_circle</span>
        <span>{{ $page.props.flash.success }}</span>
      </div>
      <div v-if="$page.props.flash?.error" class="mx-6 mt-4 p-3 bg-[#ba1a1a]/10 border border-[#ba1a1a]/30 text-[#ba1a1a] rounded-xl text-xs font-semibold flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">error</span>
        <span>{{ $page.props.flash.error }}</span>
      </div>

      <!-- Main Slot Content -->
      <main class="flex-1 p-6 space-y-6">
        <slot />
      </main>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const roles = computed(() => user.value?.roles || []);
const permissions = computed(() => user.value?.permissions || []);

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
