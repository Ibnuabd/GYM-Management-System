<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      
      <!-- ==================== HEADER & TRAINER INFO ==================== -->
      <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <!-- Breadcrumb -->
          <div class="flex items-center gap-1.5 text-xs text-[#777587] mb-1">
            <Link :href="route('dashboard')" class="hover:text-[#5242e2] transition-colors">Beranda</Link>
            <span class="material-symbols-outlined text-[13px]">chevron_right</span>
            <span>Operasional Gym</span>
            <span class="material-symbols-outlined text-[13px]">chevron_right</span>
            <span class="text-[#5242e2] font-semibold">Dashboard Personal Trainer</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-[#121c2c] tracking-tight">Sesi Personal Trainer & Pemotongan Kuota</h1>
          <p class="text-xs text-[#777587] mt-0.5">
            Manajemen kuota latihan, pemotongan sesi atomik, dan pencatatan evaluasi latihan member bimbingan.
          </p>
        </div>

        <!-- Trainer Selector (For Admin/Supervisor) or Current Trainer Badge -->
        <div class="flex items-center gap-3">
          <div v-if="isManager && trainers.length > 0" class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border border-[#c8c4d8]/40 shadow-xs">
            <span class="material-symbols-outlined text-[18px] text-[#5242e2]">filter_alt</span>
            <label for="trainerSelect" class="text-xs font-semibold text-[#474555]">Filter Trainer:</label>
            <select
              id="trainerSelect"
              :value="filters.trainer_id || ''"
              @change="onTrainerChange($event.target.value)"
              class="text-xs font-bold text-[#121c2c] bg-transparent outline-none cursor-pointer"
            >
              <option value="">Semua Trainer</option>
              <option v-for="t in trainers" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>

          <div v-else class="flex items-center gap-2 bg-white px-3.5 py-2 rounded-xl border border-[#c8c4d8]/40 shadow-xs text-xs font-semibold text-[#474555]">
            <span class="material-symbols-outlined text-[18px] text-[#f7b924]">sports_gymnastics</span>
            <span>Trainer: <strong class="text-[#121c2c]">{{ currentTrainer.name }}</strong></span>
          </div>
        </div>
      </section>

      <!-- ==================== KPI METRIC CARDS ==================== -->
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Member Bimbingan -->
        <div class="bg-white rounded-2xl p-4.5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-[#777587]">Member Bimbingan Aktif</span>
            <div class="w-8 h-8 rounded-xl bg-[#5242e2]/10 text-[#5242e2] flex items-center justify-center">
              <span class="material-symbols-outlined text-[18px]">group</span>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-2xl font-black text-[#121c2c] tnum">{{ metrics.assigned_members_count }}</span>
            <span class="text-xs text-[#777587]">Member</span>
          </div>
          <p class="text-[11px] text-[#09ad95] mt-1 font-medium flex items-center gap-1">
            <span class="material-symbols-outlined text-[14px]">check_circle</span>
            Memiliki paket PT berjalan
          </p>
        </div>

        <!-- Card 2: Sisa Kuota Sesi Aktif -->
        <div class="bg-white rounded-2xl p-4.5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-[#777587]">Sisa Kuota Sesi Aktif</span>
            <div class="w-8 h-8 rounded-xl bg-[#f7b924]/10 text-[#b58004] flex items-center justify-center">
              <span class="material-symbols-outlined text-[18px]">fitness_center</span>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-2xl font-black text-[#121c2c] tnum">{{ metrics.active_remaining_sessions }}</span>
            <span class="text-xs text-[#777587]">Sesi Tersisa</span>
          </div>
          <p class="text-[11px] text-[#777587] mt-1 font-medium">Akumulasi seluruh paket aktif</p>
        </div>

        <!-- Card 3: Sesi Hari Ini -->
        <div class="bg-white rounded-2xl p-4.5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-[#777587]">Sesi Diselesaikan Hari Ini</span>
            <div class="w-8 h-8 rounded-xl bg-[#09ad95]/10 text-[#09ad95] flex items-center justify-center">
              <span class="material-symbols-outlined text-[18px]">today</span>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-2xl font-black text-[#121c2c] tnum">{{ metrics.sessions_today }}</span>
            <span class="text-xs text-[#777587]">Sesi Latihan</span>
          </div>
          <p class="text-[11px] text-[#09ad95] mt-1 font-medium flex items-center gap-1">
            <span class="material-symbols-outlined text-[14px]">done_all</span>
            Tercatat di sistem audit
          </p>
        </div>

        <!-- Card 4: Sesi Bulan Ini -->
        <div class="bg-white rounded-2xl p-4.5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-[#777587]">Total Sesi Bulan Ini</span>
            <div class="w-8 h-8 rounded-xl bg-[#6c5ffc]/10 text-[#6c5ffc] flex items-center justify-center">
              <span class="material-symbols-outlined text-[18px]">calendar_month</span>
            </div>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-2xl font-black text-[#121c2c] tnum">{{ metrics.sessions_this_month }}</span>
            <span class="text-xs text-[#777587]">Sesi Total</span>
          </div>
          <p class="text-[11px] text-[#777587] mt-1 font-medium">Bulan kalender berjalan</p>
        </div>

      </section>

      <!-- ==================== MAIN WORKFLOW TABS ==================== -->
      <section class="bg-white rounded-2xl border border-[#c8c4d8]/40 shadow-xs overflow-hidden">
        
        <!-- Tab Navigation Bar -->
        <div class="flex items-center justify-between border-b border-[#e9edf4] px-5 pt-3">
          <div class="flex items-center gap-6">
            <button
              type="button"
              @click="activeTab = 'members'"
              class="pb-3 text-xs font-bold transition-all relative"
              :class="activeTab === 'members' ? 'text-[#5242e2] border-b-2 border-[#5242e2]' : 'text-[#777587] hover:text-[#121c2c]'"
            >
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">assignment_ind</span>
                <span>Member Bimbingan & Kuota Sesi ({{ subscriptions.length }})</span>
              </div>
            </button>

            <button
              type="button"
              @click="activeTab = 'logs'"
              class="pb-3 text-xs font-bold transition-all relative"
              :class="activeTab === 'logs' ? 'text-[#5242e2] border-b-2 border-[#5242e2]' : 'text-[#777587] hover:text-[#121c2c]'"
            >
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">history_edu</span>
                <span>Riwayat & Catatan Sesi Latihan</span>
              </div>
            </button>
          </div>
        </div>

        <!-- ==================== TAB 1: MEMBERS & DEDUCTION LIST ==================== -->
        <div v-if="activeTab === 'members'" class="p-5 space-y-4">
          
          <!-- Filter & Search Toolbar -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            
            <!-- Status Pills -->
            <div class="flex flex-wrap items-center gap-1.5">
              <button
                type="button"
                v-for="st in [
                  { key: 'active', label: 'Paket Aktif' },
                  { key: 'all', label: 'Semua Status' },
                  { key: 'exhausted', label: 'Kuota Habis' },
                  { key: 'expired', label: 'Kedaluwarsa' },
                ]"
                :key="st.key"
                @click="filterByStatus(st.key)"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150"
                :class="filters.status === st.key ? 'bg-[#6c5ffc] text-white shadow-xs font-bold' : 'bg-[#f0f3fb] text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                {{ st.label }}
              </button>
            </div>

            <!-- Search Input -->
            <div class="relative min-w-[260px]">
              <span class="material-symbols-outlined absolute left-3 top-2.5 text-[18px] text-[#777587]">search</span>
              <input
                v-model="searchQuery"
                @input="onSearchInput"
                type="text"
                placeholder="Cari nama, kode member..."
                class="w-full h-9 pl-9 pr-3 text-xs rounded-xl border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-1 focus:ring-[#6c5ffc] outline-none"
              />
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="subscriptions.length === 0" class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-[#c8c4d8]/60">
            <span class="material-symbols-outlined text-4xl text-[#777587]/60 mb-2">sports_gymnastics</span>
            <p class="text-sm font-bold text-[#121c2c]">Tidak Ada Member Bimbingan Ditemukan</p>
            <p class="text-xs text-[#777587] mt-1 max-w-sm mx-auto">
              Tidak ada paket langganan Personal Trainer yang sesuai dengan filter pencarian ini.
            </p>
          </div>

          <!-- Subscriptions Grid -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="sub in subscriptions"
              :key="sub.id"
              class="bg-white rounded-xl border border-[#e9edf4] hover:border-[#6c5ffc]/40 hover:shadow-md transition-all duration-200 p-4.5 flex flex-col justify-between"
              :class="{ 'opacity-80 bg-slate-50/50': !sub.can_deduct }"
            >
              <div>
                <!-- Top Row: Member Info & Status Badge -->
                <div class="flex items-start justify-between gap-3 mb-3">
                  <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl overflow-hidden bg-gradient-to-br from-[#6c5ffc]/20 to-[#09ad95]/20 flex items-center justify-center shrink-0 border border-[#c8c4d8]/40">
                      <img v-if="sub.photo_url" :src="sub.photo_url" :alt="sub.member_name" class="w-full h-full object-cover" />
                      <span v-else class="text-xs font-black text-[#5242e2]">{{ getInitials(sub.member_name) }}</span>
                    </div>
                    <div>
                      <h3 class="text-xs font-bold text-[#121c2c] leading-tight line-clamp-1">{{ sub.member_name }}</h3>
                      <p class="text-[11px] font-mono text-[#5242e2] font-semibold">{{ sub.member_code }}</p>
                      <p class="text-[10px] text-[#777587]">{{ sub.phone }}</p>
                    </div>
                  </div>

                  <!-- Status Tag -->
                  <div>
                    <span
                      v-if="sub.can_deduct"
                      class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                      Aktif
                    </span>
                    <span
                      v-else-if="sub.is_exhausted"
                      class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200"
                    >
                      Habis Kuota
                    </span>
                    <span
                      v-else-if="sub.is_expired"
                      class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200"
                    >
                      Kedaluwarsa
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600"
                    >
                      {{ sub.status }}
                    </span>
                  </div>
                </div>

                <!-- Package & Trainer Name -->
                <div class="bg-[#f8faff] rounded-lg p-2.5 mb-3 border border-[#e8eeff]">
                  <p class="text-[11px] font-bold text-[#121c2c]">{{ sub.package_name }}</p>
                  <p class="text-[10px] text-[#777587] flex items-center gap-1 mt-0.5">
                    <span class="material-symbols-outlined text-[13px] text-[#f7b924]">sports_gymnastics</span>
                    <span>Trainer: <strong>{{ sub.trainer_name }}</strong></span>
                  </p>
                </div>

                <!-- Session Quota Progress -->
                <div class="space-y-1.5 mb-3">
                  <div class="flex items-center justify-between text-xs">
                    <span class="text-[#777587] font-medium text-[11px]">Sisa Sesi:</span>
                    <span class="font-extrabold text-[#121c2c] tnum">
                      <strong class="text-sm text-[#5242e2]">{{ sub.remaining_sessions }}</strong> / {{ sub.total_sessions }} Sesi
                    </span>
                  </div>

                  <!-- Progress Bar -->
                  <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-300"
                      :class="sub.remaining_sessions === 0 ? 'bg-rose-500' : (sub.remaining_sessions <= 2 ? 'bg-amber-500' : 'bg-[#6c5ffc]')"
                      :style="{ width: `${sub.progress_percent}%` }"
                    ></div>
                  </div>
                  
                  <div class="flex items-center justify-between text-[10px] text-[#777587]">
                    <span>Terpakai: {{ sub.used_sessions }} sesi ({{ sub.progress_percent }}%)</span>
                    <span :class="sub.remaining_days <= 7 ? 'text-amber-600 font-bold' : ''">
                      Berlaku: s/d {{ sub.end_date }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Action Buttons Row -->
              <div class="pt-3 border-t border-[#e9edf4] flex items-center gap-2">
                <!-- Session Deduction Button -->
                <button
                  type="button"
                  @click="openDeductModal(sub)"
                  :disabled="!sub.can_deduct"
                  class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-bold transition-all duration-150 shadow-2xs"
                  :class="sub.can_deduct
                    ? 'bg-[#6c5ffc] hover:bg-[#5242e2] text-white active:scale-95 shadow-[#6c5ffc]/20'
                    : 'bg-slate-100 text-slate-400 cursor-not-allowed border border-slate-200'"
                >
                  <span class="material-symbols-outlined text-[16px]">check_circle</span>
                  <span>{{ sub.can_deduct ? 'Catat & Potong Sesi' : (sub.is_exhausted ? 'Kuota Habis' : 'Paket Non-Aktif') }}</span>
                </button>

                <!-- History Button -->
                <button
                  type="button"
                  @click="openHistoryModal(sub)"
                  class="inline-flex items-center justify-center p-2 rounded-xl text-xs font-semibold text-[#474555] bg-white border border-[#c8c4d8]/50 hover:bg-[#f0f3fb] transition-colors"
                  title="Lihat Riwayat Latihan Member"
                >
                  <span class="material-symbols-outlined text-[18px]">history</span>
                </button>
              </div>

            </div>
          </div>

        </div>

        <!-- ==================== TAB 2: AUDIT LOGS TABLE ==================== -->
        <div v-if="activeTab === 'logs'" class="p-5 space-y-4">
          
          <!-- Search in Logs -->
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-xs font-bold text-[#121c2c] uppercase tracking-wider">Log Sesi Latihan Yang Telah Diselesaikan</h3>
            <div class="relative min-w-[280px]">
              <span class="material-symbols-outlined absolute left-3 top-2.5 text-[18px] text-[#777587]">search</span>
              <input
                v-model="logSearchQuery"
                @input="onLogSearchInput"
                type="text"
                placeholder="Cari member, fokus otot, catatan..."
                class="w-full h-9 pl-9 pr-3 text-xs rounded-xl border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-1 focus:ring-[#6c5ffc] outline-none"
              />
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="historicalLogs.length === 0" class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-[#c8c4d8]/60">
            <span class="material-symbols-outlined text-4xl text-[#777587]/60 mb-2">event_busy</span>
            <p class="text-sm font-bold text-[#121c2c]">Belum Ada Log Sesi Latihan</p>
            <p class="text-xs text-[#777587] mt-1 max-w-sm mx-auto">
              Sesi latihan yang dipotong melalui formulir akan otomatis tercatat dan tersimpan di audit trail ini.
            </p>
          </div>

          <!-- Logs Table -->
          <div v-else class="overflow-x-auto rounded-xl border border-[#e9edf4]">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-[#f8faff] border-b border-[#e9edf4] text-[#777587] font-bold text-[11px] uppercase tracking-wider">
                  <th class="py-3 px-4">Waktu & Tanggal</th>
                  <th class="py-3 px-4">Member</th>
                  <th class="py-3 px-4">Fokus Otot / Program</th>
                  <th class="py-3 px-4">Durasi</th>
                  <th class="py-3 px-4">Menu & Catatan Latihan</th>
                  <th class="py-3 px-4">Evaluasi / Feedback</th>
                  <th class="py-3 px-4">Trainer</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#e9edf4]">
                <tr v-for="log in historicalLogs" :key="log.id" class="hover:bg-[#f8faff]/70 transition-colors">
                  <td class="py-3 px-4 whitespace-nowrap">
                    <p class="font-bold text-[#121c2c]">{{ log.session_date_formatted }}</p>
                    <span class="text-[10px] text-[#777587]">{{ log.time_ago }}</span>
                  </td>
                  <td class="py-3 px-4 whitespace-nowrap">
                    <p class="font-bold text-[#121c2c]">{{ log.member_name }}</p>
                    <span class="text-[10px] font-mono text-[#5242e2] font-semibold">{{ log.member_code }}</span>
                  </td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-lg bg-[#5242e2]/10 text-[#5242e2]">
                      <span class="material-symbols-outlined text-[14px]">fitness_center</span>
                      {{ log.muscle_focus }}
                    </span>
                  </td>
                  <td class="py-3 px-4 whitespace-nowrap font-medium text-[#474555]">
                    {{ log.duration_minutes }} menit
                  </td>
                  <td class="py-3 px-4 max-w-xs">
                    <p class="text-xs text-[#121c2c] whitespace-pre-line line-clamp-2" :title="log.workout_notes">
                      {{ log.workout_notes }}
                    </p>
                  </td>
                  <td class="py-3 px-4 max-w-xs">
                    <p v-if="log.member_feedback" class="text-xs text-[#474555] italic line-clamp-2" :title="log.member_feedback">
                      "{{ log.member_feedback }}"
                    </p>
                    <span v-else class="text-[11px] text-[#777587]">-</span>
                  </td>
                  <td class="py-3 px-4 whitespace-nowrap text-[#474555] font-semibold">
                    {{ log.trainer_name }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>

      </section>

      <!-- ==================== MODAL: DEDUCT SESSION & ACTIVITY LOG ==================== -->
      <div 
        v-if="showDeductModal && selectedSubscription" 
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
        @click.self="closeDeductModal"
      >
        <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-[#e9edf4] my-8 animate-scaleIn">
          
          <!-- Modal Header -->
          <div class="p-5 border-b border-[#e9edf4] flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-[#6c5ffc]/15 text-[#5242e2] flex items-center justify-center">
                <span class="material-symbols-outlined text-[20px]">fitness_center</span>
              </div>
              <div>
                <h3 class="text-sm font-bold text-[#121c2c]">Catat Sesi & Potong Kuota PT</h3>
                <p class="text-[11px] text-[#777587]">Pemotongan atomik 1 sesi dengan formulir evaluasi</p>
              </div>
            </div>
            <button @click="closeDeductModal" class="text-[#777587] hover:text-[#121c2c] text-lg font-bold">✕</button>
          </div>

          <!-- Modal Form -->
          <form @submit.prevent="submitDeductSession" class="p-5 space-y-4">
            
            <!-- Member & Quota Banner -->
            <div class="p-3.5 rounded-xl bg-gradient-to-r from-[#6c5ffc]/10 to-[#09ad95]/10 border border-[#6c5ffc]/20 flex items-center justify-between">
              <div>
                <p class="text-[10px] text-[#777587] uppercase font-bold tracking-wider">Member Terpilih:</p>
                <p class="text-xs font-bold text-[#121c2c]">{{ selectedSubscription.member_name }} ({{ selectedSubscription.member_code }})</p>
                <p class="text-[10px] text-[#5242e2] font-semibold mt-0.5">{{ selectedSubscription.package_name }}</p>
              </div>
              <div class="text-right">
                <p class="text-[10px] text-[#777587] uppercase font-bold tracking-wider">Perubahan Kuota:</p>
                <div class="flex items-center gap-1.5 font-bold text-xs">
                  <span class="text-slate-500 line-through">{{ selectedSubscription.remaining_sessions }} sesi</span>
                  <span class="material-symbols-outlined text-[14px] text-[#5242e2]">arrow_forward</span>
                  <span class="text-[#5242e2] text-sm font-black">{{ Math.max(0, selectedSubscription.remaining_sessions - 1) }} sesi</span>
                </div>
              </div>
            </div>

            <!-- Date & Duration Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">Tanggal & Waktu Sesi *</label>
                <input
                  v-model="deductForm.session_date"
                  type="datetime-local"
                  required
                  class="w-full h-9 px-3 text-xs rounded-xl border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-1 focus:ring-[#6c5ffc] outline-none"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">Durasi Latihan (menit) *</label>
                <div class="flex items-center gap-1.5">
                  <input
                    v-model.number="deductForm.duration_minutes"
                    type="number"
                    min="15"
                    max="240"
                    required
                    class="w-full h-9 px-3 text-xs rounded-xl border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-1 focus:ring-[#6c5ffc] outline-none"
                  />
                  <!-- Quick chips -->
                  <button type="button" @click="deductForm.duration_minutes = 60" class="px-2 py-1 text-[10px] rounded-lg bg-slate-100 hover:bg-slate-200 font-bold">60m</button>
                  <button type="button" @click="deductForm.duration_minutes = 75" class="px-2 py-1 text-[10px] rounded-lg bg-slate-100 hover:bg-slate-200 font-bold">75m</button>
                </div>
              </div>
            </div>

            <!-- Muscle Focus & Program -->
            <div>
              <label class="block text-xs font-bold text-[#474555] mb-1">Fokus Otot / Program Latihan *</label>
              <input
                v-model="deductForm.muscle_focus"
                type="text"
                required
                placeholder="Contoh: Chest & Triceps Hypertrophy, Leg Day, HIIT Core"
                class="w-full h-9 px-3 text-xs rounded-xl border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-1 focus:ring-[#6c5ffc] outline-none"
              />
              <!-- Quick suggestions -->
              <div class="flex flex-wrap gap-1.5 mt-1.5">
                <button
                  type="button"
                  v-for="preset in ['Chest & Triceps', 'Back & Biceps', 'Leg Day & Core', 'Shoulders & Arms', 'Full Body HIIT']"
                  :key="preset"
                  @click="deductForm.muscle_focus = preset"
                  class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-[#e8eeff] text-[#5242e2] hover:bg-[#5242e2] hover:text-white transition-colors"
                >
                  {{ preset }}
                </button>
              </div>
              <p v-if="deductForm.errors.muscle_focus" class="text-[11px] text-rose-500 mt-0.5">{{ deductForm.errors.muscle_focus }}</p>
            </div>

            <!-- Workout Notes (Mandatory) -->
            <div>
              <label class="block text-xs font-bold text-[#474555] mb-1">Catatan Menu Latihan, Beban & Repetisi *</label>
              <textarea
                v-model="deductForm.workout_notes"
                rows="3"
                required
                placeholder="• Bench Press: 4x10 (60kg)&#10;• Incline Dumbbell: 3x12 (18kg)&#10;• Tricep Rope: 4x12 (20kg)"
                class="w-full p-3 text-xs rounded-xl border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-1 focus:ring-[#6c5ffc] outline-none"
              ></textarea>
              <p v-if="deductForm.errors.workout_notes" class="text-[11px] text-rose-500 mt-0.5">{{ deductForm.errors.workout_notes }}</p>
            </div>

            <!-- Member Feedback / Evaluation -->
            <div>
              <label class="block text-xs font-bold text-[#474555] mb-1">Evaluasi & Feedback Member (Opsional)</label>
              <textarea
                v-model="deductForm.member_feedback"
                rows="2"
                placeholder="Postur bench press stabil, mobilitas bahu baik. Disarankan recovery hidrasi cukup."
                class="w-full p-3 text-xs rounded-xl border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-1 focus:ring-[#6c5ffc] outline-none"
              ></textarea>
            </div>

            <!-- Atomic Lock Security Notice -->
            <div class="rounded-xl p-3 bg-amber-50 border border-amber-200 text-amber-900 text-[11px] flex items-start gap-2">
              <span class="material-symbols-outlined text-[16px] text-amber-600 mt-0.5">lock</span>
              <p>
                <strong>Proteksi Atomik:</strong> Transaksi ini menggunakan row-level lock (<code>lockForUpdate</code>) untuk menjamin pemotongan kuota tepat 1 sesi tanpa risiko duplikasi atau race condition.
              </p>
            </div>

            <!-- Error Messages from Server -->
            <div v-if="deductForm.errors.member_subscription_id" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
              {{ deductForm.errors.member_subscription_id }}
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#e9edf4]">
              <button
                type="button"
                @click="closeDeductModal"
                class="px-4 py-2 rounded-xl text-xs font-bold text-[#474555] hover:bg-slate-100 transition-colors"
              >
                Batal
              </button>
              
              <button
                type="submit"
                :disabled="deductForm.processing"
                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-[#6c5ffc] hover:bg-[#5242e2] text-white text-xs font-bold shadow-md shadow-[#6c5ffc]/25 active:scale-95 transition-all disabled:opacity-50"
              >
                <span v-if="deductForm.processing" class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>
                <span v-else class="material-symbols-outlined text-[16px]">check_circle</span>
                <span>{{ deductForm.processing ? 'Memproses Transaksi...' : 'Konfirmasi & Potong 1 Sesi' }}</span>
              </button>
            </div>

          </form>

        </div>
      </div>

      <!-- ==================== MODAL: MEMBER SESSION HISTORY ==================== -->
      <div 
        v-if="showHistoryModal && historyMember" 
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
        @click.self="closeHistoryModal"
      >
        <div class="bg-white rounded-2xl max-w-2xl w-full overflow-hidden shadow-2xl border border-[#e9edf4] my-8 animate-scaleIn">
          
          <div class="p-5 border-b border-[#e9edf4] flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-[#09ad95]/15 text-[#09ad95] flex items-center justify-center">
                <span class="material-symbols-outlined text-[20px]">history</span>
              </div>
              <div>
                <h3 class="text-sm font-bold text-[#121c2c]">Riwayat Latihan: {{ historyMember.name }}</h3>
                <p class="text-[11px] text-[#777587]">Kode: {{ historyMember.code }} • Kronologi sesi yang telah diselesaikan</p>
              </div>
            </div>
            <button @click="closeHistoryModal" class="text-[#777587] hover:text-[#121c2c] text-lg font-bold">✕</button>
          </div>

          <div class="p-5 max-h-[70vh] overflow-y-auto space-y-4">
            
            <div v-if="loadingHistory" class="py-12 text-center text-[#777587]">
              <span class="material-symbols-outlined text-3xl animate-spin text-[#5242e2] mb-2">progress_activity</span>
              <p class="text-xs">Memuat kronologi latihan member...</p>
            </div>

            <div v-else-if="memberHistoryLogs.length === 0" class="py-10 text-center bg-slate-50 rounded-xl border border-dashed border-[#c8c4d8]/60">
              <span class="material-symbols-outlined text-4xl text-[#777587]/60 mb-2">fitness_center</span>
              <p class="text-xs font-bold text-[#121c2c]">Belum Ada Sesi Latihan Yang Dicatat</p>
              <p class="text-[11px] text-[#777587] mt-1">Gunakan tombol "Catat & Potong Sesi" setelah sesi selesai dilakukan.</p>
            </div>

            <!-- Timeline of Completed Workouts -->
            <div v-else class="space-y-3">
              <div
                v-for="log in memberHistoryLogs"
                :key="log.id"
                class="p-4 rounded-xl border border-[#e9edf4] bg-white hover:border-[#6c5ffc]/30 transition-all space-y-2"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-[#5242e2]/10 text-[#5242e2]">
                      {{ log.muscle_focus }}
                    </span>
                    <span class="text-[11px] text-[#777587]">• {{ log.duration_minutes }} Menit</span>
                  </div>
                  <span class="text-xs font-bold text-[#121c2c]">{{ log.session_date_formatted }}</span>
                </div>

                <div class="bg-slate-50 p-2.5 rounded-lg text-xs font-mono text-[#121c2c] whitespace-pre-line">
                  {{ log.workout_notes }}
                </div>

                <div v-if="log.member_feedback" class="text-xs text-[#777587] italic">
                  Evaluasi: "{{ log.member_feedback }}"
                </div>
                
                <p class="text-[10px] text-[#777587] text-right">Trainer: {{ log.trainer_name }}</p>
              </div>
            </div>

          </div>

          <div class="p-4 border-t border-[#e9edf4] bg-slate-50 flex justify-end">
            <button
              @click="closeHistoryModal"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-[#121c2c] text-white hover:bg-black transition-colors"
            >
              Tutup
            </button>
          </div>

        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  subscriptions: {
    type: Array,
    default: () => [],
  },
  historicalLogs: {
    type: Array,
    default: () => [],
  },
  metrics: {
    type: Object,
    default: () => ({
      assigned_members_count: 0,
      active_remaining_sessions: 0,
      sessions_today: 0,
      sessions_this_month: 0,
    }),
  },
  filters: {
    type: Object,
    default: () => ({
      search: '',
      status: 'active',
      trainer_id: null,
      log_search: '',
    }),
  },
  trainers: {
    type: Array,
    default: () => [],
  },
  isManager: {
    type: Boolean,
    default: false,
  },
  currentTrainer: {
    type: Object,
    default: () => ({ id: 0, name: '' }),
  },
});

// Tab state
const activeTab = ref('members');

// Search states
const searchQuery = ref(props.filters.search || '');
const logSearchQuery = ref(props.filters.log_search || '');

let searchDebounceTimeout = null;
const onSearchInput = () => {
  clearTimeout(searchDebounceTimeout);
  searchDebounceTimeout = setTimeout(() => {
    router.get(
      route('pt.dashboard'),
      {
        ...props.filters,
        search: searchQuery.value,
      },
      { preserveState: true, replace: true }
    );
  }, 300);
};

let logSearchDebounceTimeout = null;
const onLogSearchInput = () => {
  clearTimeout(logSearchDebounceTimeout);
  logSearchDebounceTimeout = setTimeout(() => {
    router.get(
      route('pt.dashboard'),
      {
        ...props.filters,
        log_search: logSearchQuery.value,
      },
      { preserveState: true, replace: true }
    );
  }, 300);
};

const filterByStatus = (status) => {
  router.get(
    route('pt.dashboard'),
    {
      ...props.filters,
      status: status,
    },
    { preserveState: true, replace: true }
  );
};

const onTrainerChange = (trainerId) => {
  router.get(
    route('pt.dashboard'),
    {
      ...props.filters,
      trainer_id: trainerId || undefined,
    },
    { preserveState: true, replace: true }
  );
};

// ==================== MODAL: DEDUCT SESSION ====================
const showDeductModal = ref(false);
const selectedSubscription = ref(null);

const deductForm = useForm({
  member_subscription_id: null,
  session_date: '',
  duration_minutes: 60,
  muscle_focus: '',
  workout_notes: '',
  member_feedback: '',
});

const getNowLocalDateTime = () => {
  const now = new Date();
  const offset = now.getTimezoneOffset() * 60000;
  return new Date(now.getTime() - offset).toISOString().slice(0, 16);
};

const openDeductModal = (sub) => {
  if (!sub.can_deduct) return;
  selectedSubscription.value = sub;
  deductForm.reset();
  deductForm.member_subscription_id = sub.id;
  deductForm.session_date = getNowLocalDateTime();
  deductForm.duration_minutes = 60;
  deductForm.muscle_focus = '';
  deductForm.workout_notes = '';
  deductForm.member_feedback = '';
  showDeductModal.value = true;
};

const closeDeductModal = () => {
  showDeductModal.value = false;
  selectedSubscription.value = null;
  deductForm.reset();
};

const submitDeductSession = () => {
  deductForm.post(route('pt.sessions.store'), {
    preserveScroll: true,
    onSuccess: () => {
      closeDeductModal();
    },
  });
};

// ==================== MODAL: MEMBER HISTORY ====================
const showHistoryModal = ref(false);
const historyMember = ref(null);
const memberHistoryLogs = ref([]);
const loadingHistory = ref(false);

const openHistoryModal = async (sub) => {
  historyMember.value = {
    id: sub.member_id,
    name: sub.member_name,
    code: sub.member_code,
  };
  showHistoryModal.value = true;
  loadingHistory.value = true;
  memberHistoryLogs.value = [];

  try {
    const response = await fetch(route('pt.members.history', sub.member_id));
    if (response.ok) {
      const data = await response.json();
      memberHistoryLogs.value = data.logs || [];
    }
  } catch (error) {
    console.error('Failed to load member session history:', error);
  } finally {
    loadingHistory.value = false;
  }
};

const closeHistoryModal = () => {
  showHistoryModal.value = false;
  historyMember.value = null;
  memberHistoryLogs.value = [];
};

// Helpers
const getInitials = (name) => {
  if (!name) return 'MB';
  const parts = name.split(' ').filter(Boolean);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
};
</script>

