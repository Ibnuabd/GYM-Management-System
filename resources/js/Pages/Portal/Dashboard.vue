<template>
  <div class="min-h-screen bg-[#f0f3fb] text-[#121c2c] font-sans antialiased flex flex-col">
    
    <!-- ==================== TOP NAVIGATION ==================== -->
    <header class="bg-white border-b border-[#c8c4d8]/40 sticky top-0 z-40 shadow-xs">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        
        <!-- Brand Logo -->
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#09ad95] to-[#058471] flex items-center justify-center text-white shadow-md shadow-[#09ad95]/20">
            <span class="material-symbols-outlined text-[20px]">fitness_center</span>
          </div>
          <div>
            <span class="font-extrabold text-base text-[#121c2c] tracking-tight block leading-tight">FitCore Member</span>
            <span class="text-[10px] text-[#777587] font-semibold tracking-wider uppercase">Self-Service Portal</span>
          </div>
        </div>

        <!-- Read-Only Badge & Member Actions -->
        <div class="flex items-center gap-3 sm:gap-4">
          <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#09ad95]/10 text-[#078e7a] text-[11px] font-semibold border border-[#09ad95]/20">
            <span class="w-1.5 h-1.5 rounded-full bg-[#09ad95] animate-pulse"></span>
            <span>Akses Mandiri Terverifikasi</span>
          </div>

          <!-- Member Profile Preview & Logout -->
          <div class="flex items-center gap-3 pl-3 sm:border-l border-[#c8c4d8]/40">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-[#6c5ffc]/15 text-[#6c5ffc] flex items-center justify-center font-bold text-xs overflow-hidden border border-[#6c5ffc]/30">
                <img v-if="member.photo_asset_url" :src="member.photo_asset_url" :alt="member.full_name" class="w-full h-full object-cover" />
                <span v-else>{{ getInitials(member.full_name) }}</span>
              </div>
              <div class="hidden md:block text-left leading-tight">
                <p class="text-xs font-bold text-[#121c2c] truncate max-w-[140px]">{{ member.full_name }}</p>
                <p class="text-[10px] text-[#777587]">{{ member.member_code }}</p>
              </div>
            </div>

            <button 
              @click="logout"
              title="Keluar dari Portal"
              class="w-8 h-8 rounded-lg flex items-center justify-center text-[#777587] hover:text-[#ba1a1a] hover:bg-[#ffdad6]/40 transition-colors"
            >
              <span class="material-symbols-outlined text-[18px]">logout</span>
            </button>
          </div>

        </div>

      </div>
    </header>

    <!-- ==================== MAIN CONTENT BODY ==================== -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-8">
      
      <!-- Greeting Banner -->
      <div class="bg-gradient-to-r from-[#121c2c] via-[#1b2b45] to-[#121c2c] rounded-2xl p-6 text-white shadow-lg shadow-[#121c2c]/10 relative overflow-hidden flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative z-10">
          <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-white/10 text-emerald-300 text-[10px] font-semibold mb-2">
            <span class="material-symbols-outlined text-[14px]">verified</span>
            <span>Status Keanggotaan: {{ digitalCard.is_active ? 'AKTIF' : 'TIDAK AKTIF' }}</span>
          </div>
          <h2 class="text-xl sm:text-2xl font-black tracking-tight">Halo, {{ member.full_name }}! 👋</h2>
          <p class="text-xs text-white/70 mt-1 max-w-xl">
            Selamat datang di portal mandiri FitCore. Di sini Anda dapat memeriksa kartu keanggotaan digital, sisa kuota Personal Trainer, dan catatan latihan dari pelatih Anda.
          </p>
        </div>

        <div class="relative z-10 flex items-center gap-3">
          <div class="px-4 py-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10 text-center">
            <p class="text-[10px] uppercase tracking-wider text-white/70 font-semibold">Masa Aktif Gym</p>
            <p class="text-lg font-black text-emerald-400">{{ digitalCard.countdown_days }} <span class="text-xs font-normal text-white/70">Hari Lagi</span></p>
          </div>
          <div class="px-4 py-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/10 text-center">
            <p class="text-[10px] uppercase tracking-wider text-white/70 font-semibold">Sisa Sesi PT</p>
            <p class="text-lg font-black text-[#8b7eff]">{{ totalPtSessionsRemaining }} <span class="text-xs font-normal text-white/70">Sesi</span></p>
          </div>
        </div>

        <!-- Ambient decorative shapes -->
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-[#09ad95]/20 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -left-10 -top-10 w-48 h-48 bg-[#6c5ffc]/20 rounded-full blur-2xl pointer-events-none"></div>
      </div>

      <!-- Grid: Left Column (Card & PT Summary) + Right Column (Workout Timeline) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- LEFT: Digital Card & PT Progress Tracker (5 Cols) -->
        <div class="lg:col-span-5 space-y-6">
          
          <!-- Section Title -->
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px] text-[#09ad95]">badge</span>
            <h3 class="text-sm font-extrabold uppercase tracking-wider text-[#474555]">Kartu Member Digital</h3>
          </div>

          <!-- ==================== DIGITAL MEMBERSHIP CARD UI ==================== -->
          <div class="w-full rounded-2xl bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#0f172a] text-white p-6 shadow-xl border border-slate-700/60 relative overflow-hidden flex flex-col justify-between min-h-[250px]">
            
            <!-- Metallic Decorative Wave -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-emerald-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-[#6c5ffc]/15 rounded-full blur-xl pointer-events-none"></div>

            <!-- Top Row: Gym Brand & RFID Badge -->
            <div class="flex items-start justify-between relative z-10">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-slate-900 font-black shadow-sm">
                  <span class="material-symbols-outlined text-[18px]">fitness_center</span>
                </div>
                <div>
                  <h4 class="font-black text-sm tracking-wide text-white leading-tight">FITCORE GYM</h4>
                  <p class="text-[9px] uppercase tracking-widest text-emerald-400 font-bold">VIP MEMBERSHIP</p>
                </div>
              </div>

              <!-- RFID UID Pill -->
              <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white/10 backdrop-blur-md border border-white/10 text-[10px] font-mono text-slate-300">
                <span class="material-symbols-outlined text-[13px] text-emerald-400">contactless</span>
                <span>{{ digitalCard.rfid_uid }}</span>
              </div>
            </div>

            <!-- Middle Row: Chip & Member Details -->
            <div class="my-4 relative z-10 flex items-center gap-4">
              <!-- Smart Chip Visual -->
              <div class="w-10 h-7 rounded bg-gradient-to-tr from-amber-400 to-yellow-200 border border-amber-500/60 flex items-center justify-center shadow-inner relative overflow-hidden">
                <div class="w-full h-[1px] bg-amber-600/50 absolute top-2"></div>
                <div class="w-full h-[1px] bg-amber-600/50 absolute bottom-2"></div>
                <div class="h-full w-[1px] bg-amber-600/50 absolute left-3"></div>
                <div class="h-full w-[1px] bg-amber-600/50 absolute right-3"></div>
              </div>

              <div>
                <p class="text-base font-extrabold text-white tracking-wide leading-tight">{{ digitalCard.full_name }}</p>
                <p class="text-xs font-mono text-emerald-300 font-semibold tracking-wider mt-0.5">{{ digitalCard.member_code }}</p>
              </div>
            </div>

            <!-- Bottom Row: Package, Validity Countdown & Barcode -->
            <div class="pt-3 border-t border-slate-700/60 relative z-10 flex items-end justify-between">
              <div>
                <p class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold">Paket Aktif</p>
                <div class="flex flex-wrap gap-1 mt-0.5">
                  <span 
                    v-for="(pkg, idx) in digitalCard.active_packages" 
                    :key="idx" 
                    class="text-[10px] font-semibold text-white bg-white/10 px-1.5 py-0.5 rounded"
                  >
                    {{ pkg }}
                  </span>
                  <span v-if="!digitalCard.active_packages.length" class="text-[10px] text-slate-400 italic">Tidak ada paket</span>
                </div>
                <p class="text-[9px] text-emerald-400 font-medium mt-1">
                  Berlaku s/d: {{ digitalCard.expiry_formatted }} ({{ digitalCard.countdown_days }} hari lagi)
                </p>
              </div>

              <!-- Barcode Graphic Simulation -->
              <div class="text-right">
                <div class="inline-flex gap-[2px] items-end h-7 px-2 py-1 bg-white rounded">
                  <span class="w-[2px] h-5 bg-slate-900 inline-block"></span>
                  <span class="w-[1px] h-5 bg-slate-900 inline-block"></span>
                  <span class="w-[3px] h-5 bg-slate-900 inline-block"></span>
                  <span class="w-[1px] h-5 bg-slate-900 inline-block"></span>
                  <span class="w-[2px] h-5 bg-slate-900 inline-block"></span>
                  <span class="w-[4px] h-5 bg-slate-900 inline-block"></span>
                  <span class="w-[1px] h-5 bg-slate-900 inline-block"></span>
                  <span class="w-[2px] h-5 bg-slate-900 inline-block"></span>
                  <span class="w-[3px] h-5 bg-slate-900 inline-block"></span>
                  <span class="w-[1px] h-5 bg-slate-900 inline-block"></span>
                </div>
                <p class="text-[8px] font-mono text-slate-400 text-center mt-0.5">{{ digitalCard.member_code }}</p>
              </div>
            </div>

          </div>

          <!-- ==================== PT PACKAGE PROGRESS TRACKER ==================== -->
          <div class="bg-white rounded-2xl p-6 border border-[#c8c4d8]/40 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-[#6c5ffc]">sports_gymnastics</span>
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-[#474555]">Progres Paket Personal Trainer</h3>
              </div>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#6c5ffc]/10 text-[#5242e2]">
                {{ ptSubscriptions.length }} Paket
              </span>
            </div>

            <!-- List of PT Subscriptions -->
            <div v-if="ptSubscriptions.length" class="space-y-4">
              <div 
                v-for="sub in ptSubscriptions" 
                :key="sub.id"
                class="p-4 rounded-xl border border-[#e9edf4] bg-[#f8faff] space-y-3"
              >
                <!-- Title & Trainer -->
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <h4 class="font-bold text-xs text-[#121c2c]">{{ sub.package_name }}</h4>
                    <div class="flex items-center gap-1.5 mt-0.5">
                      <div class="w-4 h-4 rounded-full bg-[#6c5ffc]/20 text-[#6c5ffc] flex items-center justify-center text-[9px] font-bold">
                        {{ sub.trainer_initials }}
                      </div>
                      <span class="text-[11px] text-[#777587]">Pelatih: <strong class="text-[#474555]">{{ sub.trainer_name }}</strong></span>
                    </div>
                  </div>

                  <span 
                    class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                    :class="sub.is_exhausted ? 'bg-[#ffdad6] text-[#ba1a1a]' : 'bg-[#e8f5e9] text-[#2e7d32]'"
                  >
                    {{ sub.remaining_sessions }} Sesi Tersisa
                  </span>
                </div>

                <!-- Progress Bar Visual -->
                <div>
                  <div class="flex justify-between text-[11px] mb-1 font-medium">
                    <span class="text-[#777587]">Sesi Terpakai: <strong class="text-[#121c2c]">{{ sub.used_sessions }} / {{ sub.total_sessions }}</strong></span>
                    <span class="font-bold text-[#6c5ffc]">{{ sub.progress_percent }}%</span>
                  </div>
                  <div class="w-full h-2.5 rounded-full bg-[#e2e8f0] overflow-hidden">
                    <div 
                      class="h-full rounded-full transition-all duration-500 bg-gradient-to-r from-[#6c5ffc] to-[#09ad95]"
                      :style="{ width: `${sub.progress_percent}%` }"
                    ></div>
                  </div>
                </div>

                <!-- Validity Window Footer -->
                <div class="flex items-center justify-between text-[10px] text-[#777587] pt-2 border-t border-[#e2e8f0]/60">
                  <span>Masa Berlaku: {{ sub.start_date }} - {{ sub.end_date }}</span>
                  <span class="font-semibold text-[#09ad95]">{{ sub.remaining_days }} Hari Lagi</span>
                </div>
              </div>
            </div>

            <!-- Empty State for PT Packages -->
            <div v-else class="text-center py-6 text-[#777587] text-xs">
              <span class="material-symbols-outlined text-[32px] text-[#777587]/40 block mb-1">sports_kabaddi</span>
              Belum ada paket Personal Trainer yang terdaftar untuk akun Anda.
            </div>

          </div>

          <!-- ==================== GATE ATTENDANCE HISTORY ==================== -->
          <div class="bg-white rounded-2xl p-6 border border-[#c8c4d8]/40 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-[#09ad95]">history</span>
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-[#474555]">Histori Kehadiran Kios</h3>
              </div>
              <span class="text-[10px] text-[#777587]">10 Terakhir</span>
            </div>

            <div v-if="recentAttendances.length" class="space-y-2">
              <div 
                v-for="att in recentAttendances" 
                :key="att.id"
                class="flex items-center justify-between p-2 rounded-lg bg-[#f8faff] text-xs"
              >
                <div class="flex items-center gap-2">
                  <span 
                    class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                    :class="att.status === 'IN' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                  >
                    {{ att.status }}
                  </span>
                  <span class="text-[#121c2c] font-medium">{{ att.scanned_at }}</span>
                </div>
                <span class="text-[11px] text-[#777587]">{{ att.time_ago }}</span>
              </div>
            </div>

            <div v-else class="text-center py-4 text-[#777587] text-xs">
              Belum ada catatan pemindaian kehadiran.
            </div>
          </div>

        </div>

        <!-- RIGHT: Chronological Workout History Timeline (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
          
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-[20px] text-[#6c5ffc]">timeline</span>
              <h3 class="text-sm font-extrabold uppercase tracking-wider text-[#474555]">Histori Latihan Personal Trainer</h3>
            </div>
            <span class="text-[11px] font-semibold text-[#777587]">
              Total {{ workoutHistory.length }} Sesi Tercatat
            </span>
          </div>

          <!-- Chronological Workout Logs Timeline -->
          <div v-if="workoutHistory.length" class="relative pl-6 sm:pl-8 space-y-6 before:content-[''] before:absolute before:left-3 sm:before:left-4 before:top-3 before:bottom-3 before:w-[2px] before:bg-gradient-to-b before:from-[#6c5ffc] before:via-[#09ad95] before:to-transparent">
            
            <div 
              v-for="log in workoutHistory" 
              :key="log.id"
              class="relative bg-white rounded-2xl p-5 border border-[#c8c4d8]/40 shadow-xs hover:shadow-md transition-shadow duration-150 space-y-3"
            >
              <!-- Timeline Icon Marker -->
              <div class="absolute -left-6 sm:-left-8 top-5 -translate-x-1/2 w-7 h-7 rounded-full bg-white border-2 border-[#6c5ffc] flex items-center justify-center text-[#6c5ffc] shadow-xs">
                <span class="material-symbols-outlined text-[14px]">exercise</span>
              </div>

              <!-- Header: Muscle Focus & Timestamp -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                <div>
                  <span class="inline-block px-2.5 py-0.5 rounded-md bg-[#6c5ffc]/10 text-[#5242e2] text-xs font-bold mb-1">
                    {{ log.muscle_focus }}
                  </span>
                  <div class="flex items-center gap-2 text-[11px] text-[#777587]">
                    <span class="material-symbols-outlined text-[13px]">calendar_today</span>
                    <span>{{ log.date_formatted }}</span>
                    <span>&bull;</span>
                    <span>{{ log.time_ago }}</span>
                  </div>
                </div>

                <!-- Duration Pill -->
                <div class="flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#09ad95]/10 text-[#078e7a] text-xs font-bold w-fit">
                  <span class="material-symbols-outlined text-[14px]">timer</span>
                  <span>{{ log.duration_minutes }} Menit</span>
                </div>
              </div>

              <!-- Workout Notes (Exercises, Sets, Reps) -->
              <div class="p-3.5 rounded-xl bg-[#f8faff] border border-[#e9edf4]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-[#777587] mb-1.5 flex items-center gap-1">
                  <span class="material-symbols-outlined text-[12px]">list_alt</span>
                  Rincian Latihan & Beban
                </p>
                <p class="text-xs text-[#121c2c] whitespace-pre-line leading-relaxed font-mono">
                  {{ log.workout_notes }}
                </p>
              </div>

              <!-- Trainer Evaluation & Feedback -->
              <div v-if="log.member_feedback" class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/60">
                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-800 mb-1 flex items-center gap-1">
                  <span class="material-symbols-outlined text-[14px]">comment</span>
                  Evaluasi & Masukan Pelatih
                </p>
                <p class="text-xs text-amber-950 italic leading-relaxed">
                  "{{ log.member_feedback }}"
                </p>
              </div>

              <!-- Trainer Footer Signature -->
              <div class="flex items-center justify-between pt-2 border-t border-[#f0f3fb] text-[11px] text-[#777587]">
                <div class="flex items-center gap-1.5">
                  <div class="w-5 h-5 rounded-full bg-[#6c5ffc]/15 text-[#6c5ffc] flex items-center justify-center font-bold text-[9px]">
                    {{ log.trainer_initials }}
                  </div>
                  <span>Dicatat oleh: <strong class="text-[#474555]">{{ log.trainer_name }}</strong></span>
                </div>
                <span class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
                  <span class="material-symbols-outlined text-[12px]">verified_user</span> Terverifikasi Sistem
                </span>
              </div>

            </div>

          </div>

          <!-- Empty State -->
          <div v-else class="bg-white rounded-2xl p-10 border border-[#c8c4d8]/40 text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-[#6c5ffc]/10 text-[#6c5ffc] flex items-center justify-center mx-auto">
              <span class="material-symbols-outlined text-[24px]">history_edu</span>
            </div>
            <h4 class="font-bold text-sm text-[#121c2c]">Belum Ada Riwayat Sesi Latihan</h4>
            <p class="text-xs text-[#777587] max-w-sm mx-auto">
              Ketika Anda menyelesaikan sesi latihan bersama Personal Trainer, rincian repetisi, beban, dan catatan evaluasi akan langsung muncul di sini.
            </p>
          </div>

        </div>

      </div>

    </main>

    <!-- ==================== FOOTER ==================== -->
    <footer class="bg-white border-t border-[#c8c4d8]/40 py-6 text-center text-xs text-[#777587] mt-auto">
      <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
        <p>&copy; 2026 FitCore Gym Management System. Hak Cipta Dilindungi.</p>
        <p class="text-[11px]">Portal Mandiri Anggota beroperasi dalam mode baca (*read-only*).</p>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  member: {
    type: Object,
    required: true,
  },
  digitalCard: {
    type: Object,
    required: true,
  },
  ptSubscriptions: {
    type: Array,
    default: () => [],
  },
  workoutHistory: {
    type: Array,
    default: () => [],
  },
  recentAttendances: {
    type: Array,
    default: () => [],
  },
});

const totalPtSessionsRemaining = computed(() => {
  return props.ptSubscriptions.reduce((acc, sub) => acc + (sub.remaining_sessions || 0), 0);
});

const getInitials = (name) => {
  if (!name) return 'MB';
  const parts = name.split(' ');
  return parts.length >= 2 
    ? (parts[0][0] + parts[1][0]).toUpperCase()
    : name.slice(0, 2).toUpperCase();
};

const logout = () => {
  router.post('/portal/logout');
};
</script>

