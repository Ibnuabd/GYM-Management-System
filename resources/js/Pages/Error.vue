<template>
  <!--
    Halaman error mandiri (tanpa sidebar), karena bisa dibuka oleh tamu yang belum login.
    Desain diadaptasi dari tamplate/error_404_page.html, disesuaikan dengan merek FitCore.
    Teks untuk setiap kode error ada di objek "messages" di bagian <script>.

    Catatan: ukuran ikon memakai tanda "!" (contoh text-[68px]!) karena CSS bawaan
    Google Fonts selalu memaksa ikon 24px dan mengalahkan class Tailwind biasa.
  -->
  <div class="bg-[#f0f3fb] text-[#121c2c] min-h-screen flex flex-col justify-between antialiased relative overflow-hidden">
    <Head :title="content.title" />

    <!-- Hiasan latar belakang -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[520px] bg-gradient-to-b from-[#5242e2]/5 via-[#e8eeff]/20 to-transparent blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/4 -right-48 w-96 h-96 bg-[#6c5ffc]/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-10 -left-48 w-96 h-96 bg-[#74f5da]/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- ==================== HEADER: LOGO ==================== -->
    <header class="relative z-10 w-full max-w-7xl mx-auto px-6 md:px-8 pt-6 flex items-center justify-between">
      <a :href="homeUrl" class="inline-flex items-center gap-3 transition-transform duration-150 active:scale-95">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#6c5ffc] to-[#5242e2] flex items-center justify-center text-white shadow-md shadow-[#6c5ffc]/20">
          <span class="material-symbols-outlined text-[22px]!">fitness_center</span>
        </div>
        <div class="text-left">
          <span class="font-bold text-base text-[#121c2c] tracking-tight block leading-none">FitCore GMS</span>
          <span class="text-[10px] text-[#777587] font-semibold uppercase tracking-wider">Integrated Gym Suite</span>
        </div>
      </a>
    </header>

    <!-- ==================== KONTEN UTAMA ==================== -->
    <main class="relative z-10 w-full max-w-4xl mx-auto px-4 md:px-8 py-8 flex-1 flex flex-col items-center justify-center text-center">

      <!-- Angka status besar. Angka tengah "0" diganti ikon (404, 403, 500, 503). -->
      <div class="relative w-72 h-44 sm:w-80 sm:h-52 flex items-center justify-center mb-4">
        <h1 class="text-[120px] sm:text-[150px] font-extrabold tracking-tighter text-[#6c5ffc] leading-none select-none glow-primary flex items-center justify-center tnum">
          <template v-if="hasIconInMiddle">
            {{ statusDigits[0] }}
            <span class="relative mx-1 inline-flex items-center justify-center">
              <span class="text-[#dae2fa]/60">0</span>
              <span class="absolute inset-0 flex items-center justify-center">
                <span class="material-symbols-outlined text-[#5242e2] text-[68px]! sm:text-[84px]! drop-shadow-sm animate-pulse">{{ content.icon }}</span>
              </span>
            </span>
            {{ statusDigits[2] }}
          </template>
          <template v-else>{{ status }}</template>
        </h1>

        <!-- Lencana melayang (hiasan) -->
        <div class="absolute -top-1 -right-4 sm:-right-2 bg-white border border-[#c8c4d8]/40 rounded-xl p-2.5 shadow-sm rotate-6 hover:rotate-0 transition-transform duration-200">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-[#ffdad6]/40 flex items-center justify-center text-[#ba1a1a]">
              <span class="material-symbols-outlined text-[18px]!">{{ content.badgeIcon }}</span>
            </div>
            <span class="text-[10px] font-semibold tracking-wide text-[#121c2c]">{{ content.badge }}</span>
          </div>
        </div>
        <div class="absolute bottom-2 -left-4 sm:-left-2 bg-white border border-[#c8c4d8]/40 rounded-xl p-2.5 shadow-sm -rotate-6 hover:rotate-0 transition-transform duration-200">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-[#e8eeff] flex items-center justify-center text-[#5242e2]">
              <span class="material-symbols-outlined text-[18px]!">tag</span>
            </div>
            <span class="text-[10px] font-semibold tracking-wide text-[#121c2c] font-mono">Kode: {{ status }}</span>
          </div>
        </div>
      </div>

      <!-- Judul & penjelasan -->
      <div class="max-w-xl mx-auto space-y-2 mb-6">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#5242e2]/10 text-[#5242e2] text-[10px] font-semibold tracking-wide mb-1">
          <span class="material-symbols-outlined text-[14px]!">error</span>
          <span>{{ content.label }}</span>
        </div>
        <h2 class="text-[28px] leading-9 font-semibold tracking-tight text-[#121c2c]">{{ content.title }}</h2>
        <p class="text-sm text-[#474555] max-w-lg mx-auto">{{ content.description }}</p>
      </div>

      <!-- Tombol utama -->
      <div class="flex flex-wrap items-center justify-center gap-4 mb-8">
        <a
          :href="homeUrl"
          class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-[#6c5ffc] text-white text-sm font-semibold shadow-sm hover:bg-[#5242e2] transition-all duration-150 active:scale-95"
        >
          <span class="material-symbols-outlined text-[20px]!">home</span>
          <span>{{ homeLabel }}</span>
        </a>
        <button
          type="button"
          @click="goBack"
          class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-white border border-[#c8c4d8]/40 text-[#474555] hover:text-[#121c2c] hover:bg-[#f1f3ff] text-sm font-semibold shadow-sm transition-all duration-150 active:scale-95"
        >
          <span class="material-symbols-outlined text-[20px]!">arrow_back</span>
          <span>Ke Halaman Sebelumnya</span>
        </button>
      </div>

      <!-- Tautan cepat ke menu yang sering dipakai (semuanya halaman yang benar-benar ada) -->
      <div class="pt-4 border-t border-[#c8c4d8]/30 w-full max-w-md mx-auto">
        <p class="text-[10px] font-semibold text-[#474555]/80 uppercase tracking-wider mb-2">Tautan Cepat</p>
        <div class="flex items-center justify-center gap-6 text-xs font-medium">
          <template v-for="(link, index) in quickLinks" :key="link.url">
            <span v-if="index > 0" class="w-1 h-1 rounded-full bg-[#c8c4d8]"></span>
            <a :href="link.url" class="text-[#5242e2] hover:text-[#3921ca] transition-colors duration-150 inline-flex items-center gap-1 group">
              <span>{{ link.label }}</span>
              <span class="material-symbols-outlined text-[14px]! group-hover:translate-x-0.5 transition-transform duration-150">arrow_forward</span>
            </a>
          </template>
        </div>
      </div>
    </main>

    <!-- ==================== FOOTER ==================== -->
    <footer class="relative z-10 w-full max-w-7xl mx-auto px-6 md:px-8 py-4 flex flex-col sm:flex-row items-center justify-between text-xs text-[#474555] border-t border-[#c8c4d8]/20">
      <div class="flex items-center gap-2 mb-2 sm:mb-0">
        <span class="font-medium">FitCore GMS</span>
        <span class="text-[#c8c4d8]">•</span>
        <span>Integrated Gym Suite</span>
      </div>
      <span>© {{ currentYear }} FitCore GMS. Hak Cipta Dilindungi.</span>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
  // Kode status HTTP dari server: 404, 403, 419, 500, atau 503
  status: Number,
});

// Teks & ikon untuk setiap kode error
const messages = {
  404: {
    title: 'Halaman Tidak Ditemukan',
    label: 'Kesalahan Rute Navigasi',
    description: 'Maaf, halaman yang Anda cari tidak ada, telah dihapus, atau sedang dipindahkan ke alamat lain. Silakan periksa kembali tautan yang dituju.',
    icon: 'explore_off',
    badge: 'Halaman Tidak Ada',
    badgeIcon: 'link_off',
  },
  403: {
    title: 'Akses Ditolak',
    label: 'Izin Tidak Cukup',
    description: 'Akun Anda tidak memiliki izin untuk membuka halaman ini. Hubungi admin jika Anda membutuhkan akses.',
    icon: 'lock',
    badge: 'Tanpa Izin',
    badgeIcon: 'block',
  },
  419: {
    title: 'Sesi Kedaluwarsa',
    label: 'Sesi Berakhir',
    description: 'Halaman sudah terlalu lama dibuka. Silakan muat ulang halaman lalu coba lagi.',
    icon: 'timer_off',
    badge: 'Sesi Habis',
    badgeIcon: 'timer_off',
  },
  500: {
    title: 'Terjadi Kesalahan Server',
    label: 'Gangguan Server',
    description: 'Maaf, terjadi masalah di server kami. Silakan coba beberapa saat lagi.',
    icon: 'error',
    badge: 'Server Error',
    badgeIcon: 'dns',
  },
  503: {
    title: 'Sedang Dalam Perbaikan',
    label: 'Pemeliharaan Sistem',
    description: 'Sistem sedang dalam pemeliharaan. Silakan kembali beberapa saat lagi.',
    icon: 'construction',
    badge: 'Maintenance',
    badgeIcon: 'build',
  },
};

const content = computed(() => messages[props.status] ?? messages[500]);

// Angka tengah "0" (404, 403, 500, 503) diganti ikon, seperti desain template.
const statusDigits = computed(() => String(props.status).split(''));
const hasIconInMiddle = computed(() => statusDigits.value.length === 3 && statusDigits.value[1] === '0');

const currentYear = new Date().getFullYear();

// Halaman di portal member (/portal/...) memakai tautan portal; selain itu tautan staf.
// "/" otomatis diarahkan ke Dashboard (jika sudah login) atau ke halaman Login.
const isPortal = computed(() => window.location.pathname.startsWith('/portal'));
const homeUrl = computed(() => (isPortal.value ? '/portal' : '/'));
const homeLabel = computed(() => (isPortal.value ? 'Kembali ke Portal Member' : 'Kembali ke Dashboard'));

const quickLinks = computed(() => (isPortal.value
  ? [
      { label: 'Beranda Portal', url: '/portal' },
      { label: 'Kartu Digital', url: '/portal/card' },
      { label: 'Riwayat Latihan', url: '/portal/workouts' },
    ]
  : [
      { label: 'Dashboard', url: '/dashboard' },
      { label: 'Data Member', url: '/members' },
      { label: 'Kasir POS', url: '/pos' },
    ]));

// Kembali ke halaman sebelumnya. Jika dibuka langsung (tidak ada riwayat), ke beranda.
const goBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    window.location.href = homeUrl.value;
  }
};
</script>

<style scoped>
/* Bayangan lembut ungu di angka status besar (dari template) */
.glow-primary {
  text-shadow: 0 10px 40px rgba(108, 95, 252, 0.22);
}
</style>
