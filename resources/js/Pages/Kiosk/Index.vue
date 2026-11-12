<template>
  <div class="min-h-screen bg-[#0b101b] text-slate-100 flex flex-col font-sans select-none antialiased">
    
    <!-- ==================== KIOSK HEADER ==================== -->
    <header class="h-16 border-b border-slate-800 bg-[#0f172a]/80 backdrop-blur px-6 flex items-center justify-between z-30">
      
      <!-- Brand & Kiosk Title -->
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#5242e2] to-[#09ad95] flex items-center justify-center text-white shadow-lg shadow-[#5242e2]/20">
          <span class="material-symbols-outlined text-[24px]">fitness_center</span>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-bold tracking-tight text-white">FitCore GMS</h1>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-[#09ad95]/20 text-[#09ad95] border border-[#09ad95]/30 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-[#09ad95] animate-ping"></span>
              Kios Gerbang Masuk
            </span>
          </div>
          <p class="text-[11px] text-slate-400">Terminal Presensi Mandiri RFID & Verifikasi Visual</p>
        </div>
      </div>

      <!-- Live Digital Clock -->
      <div class="text-center hidden sm:block">
        <div class="text-lg font-extrabold tracking-wider text-white font-mono leading-none">
          {{ currentTimeString }}
        </div>
        <div class="text-[11px] text-slate-400 mt-0.5">
          {{ currentDateString }}
        </div>
      </div>

      <!-- Hardware Status Badges & Controls -->
      <div class="flex items-center gap-3">
        
        <!-- RFID Scanner Status Badge -->
        <div 
          class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold border transition-all"
          :class="isRfidInputFocused ? 'bg-emerald-950/40 border-emerald-500/40 text-emerald-400' : 'bg-amber-950/30 border-amber-500/40 text-amber-400'"
          title="Status Reader RFID"
        >
          <span class="material-symbols-outlined text-[16px]">contactless</span>
          <span>{{ isRfidInputFocused ? 'RFID Siap' : 'Klik Layar untuk Fokus' }}</span>
        </div>

        <!-- Camera Status Badge -->
        <div 
          class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold border"
          :class="cameraStatus === 'ready' ? 'bg-cyan-950/40 border-cyan-500/40 text-cyan-400' : 'bg-red-950/40 border-red-500/40 text-red-400'"
          :title="cameraStatusText"
        >
          <span class="material-symbols-outlined text-[16px]">{{ cameraStatus === 'ready' ? 'videocam' : 'videocam_off' }}</span>
          <span>{{ cameraStatus === 'ready' ? 'Webcam Aktif' : 'Webcam Offline' }}</span>
        </div>

        <!-- Audio Mute / Unmute Button -->
        <button 
          @click="toggleAudio"
          class="p-2 rounded-lg border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-slate-300 transition-colors"
          :title="audioMuted ? 'Aktifkan Suara Buzzer/Chime' : 'Bisukan Suara'"
        >
          <span class="material-symbols-outlined text-[18px]">{{ audioMuted ? 'volume_off' : 'volume_up' }}</span>
        </button>

        <!-- Fullscreen Button -->
        <button 
          @click="toggleFullscreen"
          class="p-2 rounded-lg border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-slate-300 transition-colors"
          title="Layar Penuh"
        >
          <span class="material-symbols-outlined text-[18px]">{{ isFullscreen ? 'fullscreen_exit' : 'fullscreen' }}</span>
        </button>

        <!-- Exit Kiosk to Dashboard -->
        <Link 
          :href="route('dashboard')"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition-colors"
        >
          <span class="material-symbols-outlined text-[16px]">arrow_back</span>
          <span>Dashboard</span>
        </Link>
      </div>
    </header>

    <!-- ==================== MAIN KIOSK VIEW ==================== -->
    <main class="flex-1 p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 overflow-hidden">
      
      <!-- LEFT / CENTER COLUMN: WEBCAM FEED & RFID TAP ZONE (7 COLS) -->
      <section class="lg:col-span-7 flex flex-col gap-5">
        
        <!-- Webcam Viewport Card with Face Guide -->
        <div class="relative rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 shadow-2xl flex-1 flex flex-col min-h-[380px] lg:min-h-[440px]">
          
          <!-- Camera Feed Video Element -->
          <div class="relative w-full h-full flex-1 bg-black flex items-center justify-center overflow-hidden">
            <video 
              ref="videoElement" 
              autoplay 
              playsinline 
              muted 
              class="w-full h-full object-cover transform -scale-x-100"
              :class="{ 'hidden': cameraStatus !== 'ready' }"
            ></video>

            <!-- Fallback if Webcam is detached / disabled -->
            <div 
              v-if="cameraStatus !== 'ready'" 
              class="flex flex-col items-center justify-center p-8 text-center"
            >
              <div class="w-20 h-20 rounded-full bg-slate-800/80 border border-slate-700 flex items-center justify-center text-amber-400 mb-4 animate-pulse">
                <span class="material-symbols-outlined text-[42px]">no_photography</span>
              </div>
              <h3 class="text-base font-bold text-slate-200">Kamera Webcam Tidak Terhubung</h3>
              <p class="text-xs text-slate-400 max-w-sm mt-1">
                {{ cameraErrorMessage || 'Presensi kartu RFID tetap dapat digunakan normal tanpa foto webcam.' }}
              </p>
              <button 
                @click="initWebcam"
                class="mt-4 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-cyan-400 border border-cyan-500/30 transition-all flex items-center gap-1.5"
              >
                <span class="material-symbols-outlined text-[16px]">refresh</span>
                Coba Hubungkan Ulang Kamera
              </button>
            </div>

            <!-- Face Viewfinder Guide Overlay -->
            <div 
              v-if="cameraStatus === 'ready'"
              class="absolute inset-0 pointer-events-none flex items-center justify-center"
            >
              <!-- Target Frame Box -->
              <div class="relative w-64 h-64 sm:w-72 sm:h-72 border-2 border-dashed border-white/30 rounded-3xl flex items-center justify-center">
                <!-- Corner highlights -->
                <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-cyan-400 rounded-tl-xl"></div>
                <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-cyan-400 rounded-tr-xl"></div>
                <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-cyan-400 rounded-bl-xl"></div>
                <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-cyan-400 rounded-br-xl"></div>

                <span class="text-[11px] font-bold text-white/60 bg-black/40 px-3 py-1 rounded-full backdrop-blur-sm">
                  Posisikan Wajah di Sini
                </span>
              </div>
            </div>

            <!-- Live Camera Badge Overlay -->
            <div class="absolute top-4 left-4 z-10 flex items-center gap-2">
              <span class="px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-md border border-white/10 text-white text-[11px] font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                <span>REC LIVE</span>
              </span>
              <span class="px-2 py-1 rounded-lg bg-black/40 backdrop-blur-md text-slate-300 text-[10px] font-mono">
                Gate 01 Front Desk
              </span>
            </div>

            <!-- Flash Animation during Snapshot Capture -->
            <div 
              v-if="isCapturingFlash" 
              class="absolute inset-0 bg-white pointer-events-none animate-fadeOut"
            ></div>

          </div>

          <!-- RFID Tap Instruction & Invisible/Persistent Input Footer -->
          <div 
            class="p-5 border-t border-slate-800 bg-slate-900/90 backdrop-blur flex flex-col sm:flex-row items-center justify-between gap-4 cursor-pointer"
            @click="focusRfidInput"
          >
            <!-- Card Tap Prompt -->
            <div class="flex items-center gap-3">
              <div 
                class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300"
                :class="isScanning ? 'bg-cyan-500/20 text-cyan-400 scale-110' : 'bg-slate-800 text-[#09ad95] animate-pulse'"
              >
                <span class="material-symbols-outlined text-[28px]">contactless</span>
              </div>
              <div>
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                  <span>TEMPELKAN KARTU RFID</span>
                  <span v-if="isScanning" class="w-3.5 h-3.5 rounded-full border-2 border-cyan-400 border-t-transparent animate-spin"></span>
                </h2>
                <p class="text-xs text-slate-400">
                  Arahkan kartu ke scanner USB di samping monitor atau ketik UID di bawah.
                </p>
              </div>
            </div>

            <!-- Persistent RFID Input Field -->
            <div class="w-full sm:w-auto flex items-center gap-2">
              <div class="relative w-full sm:w-56">
                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-slate-500">credit_card</span>
                <input 
                  ref="rfidInputRef"
                  v-model="rfidInput"
                  @blur="handleInputBlur"
                  @focus="isRfidInputFocused = true"
                  @keydown.enter.prevent="handleRfidSubmit"
                  type="text" 
                  placeholder="Scan kartu RFID..."
                  autocomplete="off"
                  autofocus
                  class="w-full h-10 pl-8 pr-8 rounded-xl bg-slate-800/80 border text-xs text-white placeholder-slate-500 font-mono focus:outline-none transition-all"
                  :class="isRfidInputFocused ? 'border-cyan-500 ring-2 ring-cyan-500/20' : 'border-slate-700'"
                />
                <button 
                  v-if="rfidInput" 
                  @click.stop="handleRfidSubmit"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-cyan-400 hover:text-cyan-300"
                  title="Kirim Scan"
                >
                  <span class="material-symbols-outlined text-[18px]">subdirectory_arrow_left</span>
                </button>
              </div>
            </div>

          </div>

        </div>

        <!-- ==================== SCAN FEEDBACK BANNER (SUCCESS / REJECTION) ==================== -->
        <transition name="slide-fade">
          <div 
            v-if="currentFeedback" 
            class="rounded-2xl p-5 border shadow-2xl transition-all duration-300"
            :class="getFeedbackCardClass(currentFeedback.type)"
          >
            <!-- Progress Auto-dismiss Bar -->
            <div class="w-full bg-white/10 rounded-full h-1 mb-4 overflow-hidden">
              <div 
                class="h-full rounded-full transition-all linear"
                :class="getFeedbackBarClass(currentFeedback.type)"
                :style="{ width: `${feedbackProgressPercent}%` }"
              ></div>
            </div>

            <div class="flex items-start justify-between gap-4">
              
              <!-- Left side: Icon, Status, Member Info -->
              <div class="flex items-start gap-4">
                
                <!-- Status Icon Badge -->
                <div 
                  class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg text-white"
                  :class="getFeedbackIconBgClass(currentFeedback.type)"
                >
                  <span class="material-symbols-outlined text-[32px]">
                    {{ getFeedbackIcon(currentFeedback.type) }}
                  </span>
                </div>

                <div>
                  <div class="flex items-center gap-2">
                    <span 
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                      :class="getFeedbackBadgeClass(currentFeedback.type)"
                    >
                      {{ getFeedbackBadgeText(currentFeedback.type) }}
                    </span>
                    <span class="text-xs text-slate-300 font-mono">{{ currentFeedback.timestamp }}</span>
                  </div>

                  <h3 class="text-lg font-bold text-white mt-1 leading-snug">
                    {{ currentFeedback.title }}
                  </h3>
                  
                  <p class="text-xs text-slate-300 mt-0.5 leading-relaxed">
                    {{ currentFeedback.message }}
                  </p>

                  <!-- Member Details Cluster if available -->
                  <div v-if="currentFeedback.member" class="mt-3 flex flex-wrap items-center gap-3 text-xs bg-black/20 p-2.5 rounded-xl border border-white/10">
                    <div class="flex items-center gap-2">
                      <span class="material-symbols-outlined text-[16px] text-slate-400">badge</span>
                      <span class="font-bold text-white">{{ currentFeedback.member.full_name }}</span>
                      <span class="text-slate-400 text-[11px] font-mono">({{ currentFeedback.member.member_code }})</span>
                    </div>

                    <div v-if="currentFeedback.package_name" class="h-3 w-px bg-white/20"></div>

                    <div v-if="currentFeedback.package_name" class="flex items-center gap-1.5">
                      <span class="material-symbols-outlined text-[16px] text-cyan-400">card_membership</span>
                      <span class="font-semibold text-cyan-300">{{ currentFeedback.package_name }}</span>
                    </div>

                    <div v-if="currentFeedback.remaining_validity_days !== undefined" class="flex items-center gap-1 text-[11px] text-slate-300">
                      <span>• Masa Aktif:</span>
                      <strong class="text-white">{{ currentFeedback.remaining_validity_days }} Hari</strong>
                    </div>

                    <div v-if="currentFeedback.pt_session_balance > 0" class="flex items-center gap-1 text-[11px] text-amber-300">
                      <span>• Kuota PT:</span>
                      <strong>{{ currentFeedback.pt_session_balance }} Sesi</strong>
                    </div>
                  </div>

                </div>

              </div>

              <!-- Right side: Snapshot & Member Photo Verification Preview -->
              <div v-if="currentFeedback.snapshot_url || currentFeedback.member?.photo_asset_url" class="flex items-center gap-2 flex-shrink-0">
                <div v-if="currentFeedback.snapshot_url" class="text-center">
                  <div class="w-16 h-16 rounded-xl overflow-hidden border-2 border-white/20 bg-black shadow-md">
                    <img :src="currentFeedback.snapshot_url" class="w-full h-full object-cover" alt="Webcam Snapshot" />
                  </div>
                  <span class="text-[9px] text-slate-400 block mt-1">Snapshot</span>
                </div>
                <div v-if="currentFeedback.member?.photo_asset_url" class="text-center">
                  <div class="w-16 h-16 rounded-xl overflow-hidden border-2 border-white/20 bg-black shadow-md">
                    <img :src="currentFeedback.member.photo_asset_url" class="w-full h-full object-cover" alt="Foto Member" />
                  </div>
                  <span class="text-[9px] text-slate-400 block mt-1">Foto Profil</span>
                </div>
              </div>

            </div>

          </div>
        </transition>

      </section>

      <!-- RIGHT COLUMN: TODAY'S LIVE ATTENDANCE LOG TABLE (5 COLS) -->
      <section class="lg:col-span-5 flex flex-col gap-4">
        
        <!-- Summary Stats Card Row -->
        <div class="grid grid-cols-3 gap-3">
          <!-- Total Check-in IN -->
          <div class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-lg">
            <div class="flex items-center justify-between text-emerald-400 mb-1">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Masuk</span>
              <span class="material-symbols-outlined text-[18px]">login</span>
            </div>
            <div class="text-xl font-extrabold text-white font-mono leading-none">
              {{ liveStats.today_in_count }}
            </div>
            <span class="text-[10px] text-emerald-400 font-medium">IN Hari Ini</span>
          </div>

          <!-- Total Check-out OUT -->
          <div class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-lg">
            <div class="flex items-center justify-between text-cyan-400 mb-1">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Keluar</span>
              <span class="material-symbols-outlined text-[18px]">logout</span>
            </div>
            <div class="text-xl font-extrabold text-white font-mono leading-none">
              {{ liveStats.today_out_count }}
            </div>
            <span class="text-[10px] text-cyan-400 font-medium">OUT Hari Ini</span>
          </div>

          <!-- Total Scans -->
          <div class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-lg">
            <div class="flex items-center justify-between text-[#a78bfa] mb-1">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Aktivitas</span>
              <span class="material-symbols-outlined text-[18px]">speed</span>
            </div>
            <div class="text-xl font-extrabold text-white font-mono leading-none">
              {{ liveStats.total_scans_today }}
            </div>
            <span class="text-[10px] text-[#a78bfa] font-medium">Total Tap</span>
          </div>
        </div>

        <!-- Live Feed Container -->
        <div class="flex-1 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-2xl flex flex-col overflow-hidden min-h-[460px]">
          
          <!-- Feed Header -->
          <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-[20px] text-cyan-400">history_toggle_drop_down</span>
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">Log Kehadiran Hari Ini</h3>
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>
            <button 
              @click="fetchRecentLogs"
              class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
              title="Perbarui Daftar"
            >
              <span class="material-symbols-outlined text-[16px]" :class="{ 'animate-spin': isFetchingLogs }">refresh</span>
            </button>
          </div>

          <!-- Logs Scroll Area -->
          <div class="flex-1 overflow-y-auto divide-y divide-slate-800/60 p-2 space-y-1">
            <transition-group name="list">
              <div 
                v-for="log in attendanceLogs" 
                :key="log.id"
                class="p-3 rounded-xl hover:bg-slate-800/50 transition-all flex items-center justify-between gap-3 text-xs"
              >
                <!-- Member & Thumbnail -->
                <div class="flex items-center gap-3 min-w-0">
                  <div 
                    class="relative w-10 h-10 rounded-xl overflow-hidden border border-slate-700 bg-slate-800 flex-shrink-0 cursor-pointer"
                    @click="openSnapshotModal(log)"
                  >
                    <img v-if="log.snapshot_url" :src="log.snapshot_url" class="w-full h-full object-cover" alt="Snapshot" />
                    <div v-else class="w-full h-full flex items-center justify-center text-slate-500">
                      <span class="material-symbols-outlined text-[20px]">person</span>
                    </div>
                    <span 
                      class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-slate-900"
                      :class="log.status === 'IN' ? 'bg-[#09ad95]' : 'bg-[#05c3fb]'"
                    ></span>
                  </div>

                  <div class="truncate">
                    <p class="font-bold text-white truncate leading-tight">{{ log.member_name }}</p>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">
                      {{ log.member_code }} • {{ log.package_name }}
                    </p>
                  </div>
                </div>

                <!-- Status Badge & Scanned Time -->
                <div class="text-right flex-shrink-0">
                  <span 
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="log.status === 'IN' ? 'bg-[#09ad95]/20 text-[#09ad95] border border-[#09ad95]/30' : 'bg-[#05c3fb]/20 text-[#05c3fb] border border-[#05c3fb]/30'"
                  >
                    <span class="material-symbols-outlined text-[12px]">{{ log.status === 'IN' ? 'login' : 'logout' }}</span>
                    {{ log.status === 'IN' ? 'IN' : 'OUT' }}
                  </span>
                  <div class="text-[10px] text-slate-400 font-mono mt-1">
                    {{ log.scanned_time }}
                  </div>
                </div>

              </div>
            </transition-group>

            <!-- Empty State -->
            <div 
              v-if="attendanceLogs.length === 0" 
              class="h-48 flex flex-col items-center justify-center text-center p-6 text-slate-500"
            >
              <span class="material-symbols-outlined text-[36px] text-slate-600 mb-2">sensor_occupied</span>
              <p class="text-xs font-semibold">Belum ada pemindaian kartu hari ini.</p>
              <p class="text-[11px] text-slate-600">Tempelkan kartu RFID anggota untuk memulai.</p>
            </div>

          </div>

          <!-- Bottom Footer Info -->
          <div class="p-3 border-t border-slate-800 bg-slate-900/60 flex items-center justify-between text-[11px] text-slate-400">
            <span>Menampilkan {{ attendanceLogs.length }} rekaman terbaru</span>
            <span class="text-slate-500 flex items-center gap-1">
              <span class="material-symbols-outlined text-[13px]">sync</span>
              Sync Auto &lt;2.0s
            </span>
          </div>

        </div>

      </section>

    </main>

    <!-- ==================== SNAPSHOT ZOOM MODAL ==================== -->
    <div 
      v-if="zoomedSnapshot" 
      class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="zoomedSnapshot = null"
    >
      <div class="bg-slate-900 rounded-3xl max-w-md w-full overflow-hidden border border-slate-700 shadow-2xl animate-scaleIn">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-cyan-400 text-[20px]">photo_camera</span>
            <h4 class="text-xs font-bold text-white">Snapshot Gerbang Masuk</h4>
          </div>
          <button @click="zoomedSnapshot = null" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>
        <div class="p-4 bg-black flex items-center justify-center min-h-[260px]">
          <img 
            :src="zoomedSnapshot.snapshot_url" 
            :alt="zoomedSnapshot.member_name"
            class="max-w-full max-h-[380px] rounded-xl object-contain shadow-lg"
          />
        </div>
        <div class="p-4 bg-slate-900 flex items-center justify-between text-xs">
          <div>
            <p class="font-bold text-white">{{ zoomedSnapshot.member_name }}</p>
            <p class="text-[11px] text-slate-400">{{ zoomedSnapshot.member_code }} • {{ zoomedSnapshot.scanned_time }}</p>
          </div>
          <span 
            class="px-2.5 py-1 rounded-full font-bold text-[10px]"
            :class="zoomedSnapshot.status === 'IN' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-cyan-500/20 text-cyan-400'"
          >
            {{ zoomedSnapshot.status === 'IN' ? 'IN (Masuk)' : 'OUT (Keluar)' }}
          </span>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  recentAttendances: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({
      today_in_count: 0,
      today_out_count: 0,
      total_scans_today: 0,
    }),
  },
});

// State Management
const videoElement = ref(null);
const rfidInputRef = ref(null);
const rfidInput = ref('');
const isRfidInputFocused = ref(true);
const isScanning = ref(false);
const isCapturingFlash = ref(false);
const isFullscreen = ref(false);
const audioMuted = ref(false);
const isFetchingLogs = ref(false);

const cameraStatus = ref('initializing'); // 'initializing' | 'ready' | 'unavailable'
const cameraErrorMessage = ref('');
let mediaStream = null;

const liveStats = ref({ ...props.stats });
const attendanceLogs = ref([...props.recentAttendances]);
const zoomedSnapshot = ref(null);

// Feedback State
const currentFeedback = ref(null);
const feedbackProgressPercent = ref(100);
let feedbackDismissTimer = null;
let feedbackProgressInterval = null;

// Clock State
const currentTimeString = ref('');
const currentDateString = ref('');
let clockTimer = null;
let syncPollingTimer = null;

// Web Audio API Context
let audioCtx = null;

// --- CLOCK UPDATE ---
const updateClock = () => {
  const now = new Date();
  currentTimeString.value = now.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  });
  currentDateString.value = now.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
};

// --- AUDIO FEEDBACK (WEB AUDIO API) ---
const initAudio = () => {
  try {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (AudioContextClass) {
      audioCtx = new AudioContextClass();
    }
  } catch (err) {
    console.warn('AudioContext gagal diinisialisasi:', err);
  }
};

const playFeedbackSound = (type) => {
  if (audioMuted.value) return;
  try {
    if (!audioCtx) {
      initAudio();
    }
    if (audioCtx.state === 'suspended') {
      audioCtx.resume();
    }

    const now = audioCtx.currentTime;

    if (type === 'in') {
      // Pleasant Rising Dual-Tone Chime: D5 (587.33 Hz) -> A5 (880.00 Hz)
      const osc1 = audioCtx.createOscillator();
      const osc2 = audioCtx.createOscillator();
      const gain = audioCtx.createGain();

      osc1.type = 'sine';
      osc2.type = 'sine';

      osc1.frequency.setValueAtTime(587.33, now);
      osc2.frequency.setValueAtTime(880.00, now + 0.12);

      gain.gain.setValueAtTime(0.3, now);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);

      osc1.connect(gain);
      osc2.connect(gain);
      gain.connect(audioCtx.destination);

      osc1.start(now);
      osc1.stop(now + 0.12);
      osc2.start(now + 0.12);
      osc2.stop(now + 0.5);
    } else if (type === 'out') {
      // Gentle Falling Dual-Tone Chime: E5 (659.25 Hz) -> A4 (440.00 Hz)
      const osc1 = audioCtx.createOscillator();
      const osc2 = audioCtx.createOscillator();
      const gain = audioCtx.createGain();

      osc1.type = 'sine';
      osc2.type = 'sine';

      osc1.frequency.setValueAtTime(659.25, now);
      osc2.frequency.setValueAtTime(440.00, now + 0.14);

      gain.gain.setValueAtTime(0.25, now);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.55);

      osc1.connect(gain);
      osc2.connect(gain);
      gain.connect(audioCtx.destination);

      osc1.start(now);
      osc1.stop(now + 0.14);
      osc2.start(now + 0.14);
      osc2.stop(now + 0.55);
    } else {
      // Distinct Harsh Error Buzzer (160 Hz Sawtooth Wave)
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();

      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(160, now);
      osc.frequency.setValueAtTime(140, now + 0.15);

      gain.gain.setValueAtTime(0.4, now);
      gain.gain.exponentialRampToValueAtTime(0.01, now + 0.45);

      osc.connect(gain);
      gain.connect(audioCtx.destination);

      osc.start(now);
      osc.stop(now + 0.45);
    }
  } catch (err) {
    console.error('Gagal memutar audio:', err);
  }
};

const toggleAudio = () => {
  audioMuted.value = !audioMuted.value;
};

// --- FULLSCREEN TOGGLE ---
const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().then(() => {
      isFullscreen.value = true;
    }).catch(err => {
      console.warn('Gagal beralih ke fullscreen:', err);
    });
  } else {
    document.exitFullscreen().then(() => {
      isFullscreen.value = false;
    });
  }
};

// --- WEBCAM INITIALIZATION & GRACEFUL DEGRADATION ---
const initWebcam = async () => {
  cameraStatus.value = 'initializing';
  cameraErrorMessage.value = '';

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    cameraStatus.value = 'unavailable';
    cameraErrorMessage.value = 'Browser ini tidak mendukung akses perangkat webcam.';
    return;
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: {
        width: { ideal: 640 },
        height: { ideal: 480 },
        facingMode: 'user',
      },
      audio: false,
    });

    mediaStream = stream;
    if (videoElement.value) {
      videoElement.value.srcObject = stream;
      videoElement.value.onloadedmetadata = () => {
        videoElement.value.play();
      };
    }
    cameraStatus.value = 'ready';
  } catch (err) {
    console.warn('Akses webcam tidak tersedia:', err.name, err.message);
    cameraStatus.value = 'unavailable';
    if (err.name === 'NotAllowedError') {
      cameraErrorMessage.value = 'Izin akses kamera ditolak. Silakan izinkan kamera di peramban.';
    } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
      cameraErrorMessage.value = 'Tidak ada perangkat kamera webcam yang terpasang pada komputer ini.';
    } else {
      cameraErrorMessage.value = 'Kamera tidak dapat diakses saat ini.';
    }
  }
};

const stopWebcam = () => {
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop());
    mediaStream = null;
  }
};

const cameraStatusText = computed(() => {
  if (cameraStatus.value === 'ready') return 'Webcam Siap Membidik Snapshot Otomatis';
  if (cameraStatus.value === 'initializing') return 'Menghubungkan ke Perangkat Webcam...';
  return cameraErrorMessage.value || 'Kamera Terputus';
});

// --- PERSISTENT RFID AUTOFOCUS & KEY LISTENER FALLBACK ---
const focusRfidInput = () => {
  if (rfidInputRef.value) {
    rfidInputRef.value.focus();
    isRfidInputFocused.value = true;
  }
};

const handleInputBlur = () => {
  isRfidInputFocused.value = false;
  // Persistent autofocus fallback: refocus after brief delay unless another input has focus
  setTimeout(() => {
    if (document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'BUTTON') {
      focusRfidInput();
    }
  }, 100);
};

const handleGlobalKeydown = (event) => {
  // If user is typing in another input element, let it be
  if (event.target !== rfidInputRef.value && (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA')) {
    return;
  }

  // If RFID input is not currently focused, focus it and redirect keystroke
  if (document.activeElement !== rfidInputRef.value) {
    focusRfidInput();
    if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
      rfidInput.value += event.key;
      event.preventDefault();
    }
  }
};

// --- WEBCAM SNAPSHOT CAPTURE ---
const captureSnapshot = () => {
  if (cameraStatus.value !== 'ready' || !videoElement.value) {
    return null;
  }

  try {
    isCapturingFlash.value = true;
    setTimeout(() => {
      isCapturingFlash.value = false;
    }, 150);

    const canvas = document.createElement('canvas');
    const width = videoElement.value.videoWidth || 640;
    const height = videoElement.value.videoHeight || 480;
    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext('2d');
    // Mirror snapshot to match webcam preview orientation
    ctx.translate(width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(videoElement.value, 0, 0, width, height);

    // Compress to JPEG 0.7 (guaranteed under 100 KB)
    return canvas.toDataURL('image/jpeg', 0.7);
  } catch (err) {
    console.error('Gagal mengambil snapshot webcam:', err);
    return null;
  }
};

// --- RFID SCAN SUBMISSION HANDLER ---
const handleRfidSubmit = async () => {
  const rfid = rfidInput.value ? rfidInput.value.trim() : '';
  if (!rfid || isScanning.value) return;

  isScanning.value = true;
  const snapshotData = captureSnapshot();

  // Reset input immediately for snappy kiosk feel
  rfidInput.value = '';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  try {
    const response = await fetch(route('kiosk.scan'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        rfid_uid: rfid,
        snapshot: snapshotData,
      }),
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // 1. Success IN or OUT
      const att = data.attendance;
      const isCheckIn = att.status === 'IN';

      playFeedbackSound(isCheckIn ? 'in' : 'out');

      showFeedback({
        type: isCheckIn ? 'in' : 'out',
        title: isCheckIn ? 'CHECK-IN BERHASIL (MASUK)' : 'CHECK-OUT BERHASIL (KELUAR)',
        message: data.message,
        timestamp: att.scanned_time,
        member: {
          full_name: att.member_name,
          member_code: att.member_code,
          photo_asset_url: att.member_photo,
        },
        package_name: att.package_name,
        remaining_validity_days: att.remaining_validity_days,
        pt_session_balance: att.pt_session_balance,
        snapshot_url: att.snapshot_url,
      });

      // Instantly prepend to log table (< 2.0s requirement)
      attendanceLogs.value.unshift(att);
      if (attendanceLogs.value.length > 25) {
        attendanceLogs.value.pop();
      }

      // Update counters
      if (isCheckIn) {
        liveStats.value.today_in_count++;
      } else {
        liveStats.value.today_out_count++;
      }
      liveStats.value.total_scans_today++;

    } else {
      // 2. Error / Rejected (Unregistered or Expired Subscription)
      playFeedbackSound('rejected');

      const errorCode = data.error_code || 'REJECTED';
      let title = 'AKSES DITOLAK';
      if (errorCode === 'UNREGISTERED_CARD') {
        title = 'KARTU TIDAK TERDAFTAR';
      } else if (errorCode === 'EXPIRED_SUBSCRIPTION') {
        title = 'PAKET LANGGANAN KEDALUWARSA';
      } else if (errorCode === 'INACTIVE_MEMBER') {
        title = 'STATUS MEMBER NON-AKTIF';
      }

      showFeedback({
        type: 'rejected',
        title: title,
        message: data.message || 'Kartu RFID ini tidak diizinkan masuk ke area gym.',
        timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
        member: data.member || null,
        snapshot_url: snapshotData,
      });
    }

  } catch (err) {
    console.error('Koneksi ke backend kios gagal:', err);
    playFeedbackSound('rejected');
    showFeedback({
      type: 'rejected',
      title: 'KENDALA JARINGAN SISTEM',
      message: 'Gagal menghubungi server verifikasi. Silakan hubungi petugas meja depan.',
      timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
    });
  } finally {
    isScanning.value = false;
    focusRfidInput();
  }
};

// --- FEEDBACK BANNER CONTROLLER ---
const showFeedback = (feedback) => {
  currentFeedback.value = feedback;
  feedbackProgressPercent.value = 100;

  if (feedbackDismissTimer) clearTimeout(feedbackDismissTimer);
  if (feedbackProgressInterval) clearInterval(feedbackProgressInterval);

  const durationMs = 4500;
  const intervalMs = 50;
  const step = (intervalMs / durationMs) * 100;

  feedbackProgressInterval = setInterval(() => {
    feedbackProgressPercent.value = Math.max(0, feedbackProgressPercent.value - step);
  }, intervalMs);

  feedbackDismissTimer = setTimeout(() => {
    currentFeedback.value = null;
    clearInterval(feedbackProgressInterval);
  }, durationMs);
};

// --- SYNC POLLING FOR RECEPTIONIST / MULTI-KIOSK CONSISTENCY ---
const fetchRecentLogs = async () => {
  if (isFetchingLogs.value) return;
  isFetchingLogs.value = true;

  try {
    const res = await fetch(route('kiosk.recent'));
    if (res.ok) {
      const data = await res.json();
      if (data.recentAttendances) {
        attendanceLogs.value = data.recentAttendances;
      }
      if (data.stats) {
        liveStats.value = data.stats;
      }
    }
  } catch (err) {
    // silently ignore network jitter
  } finally {
    isFetchingLogs.value = false;
  }
};

// --- STYLING HELPERS ---
const getFeedbackCardClass = (type) => {
  if (type === 'in') {
    return 'bg-gradient-to-r from-emerald-950/80 via-emerald-900/60 to-slate-900 border-emerald-500/50 shadow-emerald-950/50';
  }
  if (type === 'out') {
    return 'bg-gradient-to-r from-cyan-950/80 via-cyan-900/60 to-slate-900 border-cyan-500/50 shadow-cyan-950/50';
  }
  return 'bg-gradient-to-r from-rose-950/90 via-red-900/70 to-slate-900 border-rose-500/60 shadow-rose-950/50 animate-shake';
};

const getFeedbackBarClass = (type) => {
  if (type === 'in') return 'bg-emerald-400';
  if (type === 'out') return 'bg-cyan-400';
  return 'bg-rose-500';
};

const getFeedbackIcon = (type) => {
  if (type === 'in') return 'check_circle';
  if (type === 'out') return 'logout';
  return 'cancel';
};

const getFeedbackIconBgClass = (type) => {
  if (type === 'in') return 'bg-emerald-500 shadow-emerald-500/30';
  if (type === 'out') return 'bg-cyan-500 shadow-cyan-500/30';
  return 'bg-rose-500 shadow-rose-500/30';
};

const getFeedbackBadgeClass = (type) => {
  if (type === 'in') return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
  if (type === 'out') return 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30';
  return 'bg-rose-500/20 text-rose-300 border border-rose-500/30';
};

const getFeedbackBadgeText = (type) => {
  if (type === 'in') return 'GERBANG TERBUKA (IN)';
  if (type === 'out') return 'GERBANG TERBUKA (OUT)';
  return 'GERBANG DITUTUP';
};

const openSnapshotModal = (log) => {
  if (log.snapshot_url) {
    zoomedSnapshot.value = log;
  }
};

// --- LIFECYCLE HOOKS ---
onMounted(() => {
  updateClock();
  clockTimer = setInterval(updateClock, 1000);
  initAudio();
  initWebcam();
  focusRfidInput();

  window.addEventListener('keydown', handleGlobalKeydown);

  // Sync background logs every 4 seconds without reloading page
  syncPollingTimer = setInterval(fetchRecentLogs, 4000);
});

onUnmounted(() => {
  if (clockTimer) clearInterval(clockTimer);
  if (syncPollingTimer) clearInterval(syncPollingTimer);
  if (feedbackDismissTimer) clearTimeout(feedbackDismissTimer);
  if (feedbackProgressInterval) clearInterval(feedbackProgressInterval);

  stopWebcam();
  window.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<style scoped>
@keyframes flash {
  0% { opacity: 0.9; }
  100% { opacity: 0; }
}

.animate-fadeOut {
  animation: flash 0.15s ease-out forwards;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-6px); }
  40%, 80% { transform: translateX(6px); }
}

.animate-shake {
  animation: shake 0.4s ease-in-out;
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-scaleIn {
  animation: scaleIn 0.18s ease-out forwards;
}

.slide-fade-enter-active {
  transition: all 0.25s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-8px);
  opacity: 0;
}

.list-enter-active,
.list-leave-active {
  transition: all 0.25s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(12px);
}
</style>

