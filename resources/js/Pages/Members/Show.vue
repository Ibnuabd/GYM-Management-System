<template>
  <AuthenticatedLayout>
    
    <!-- ==================== HEADER & BREADCRUMB ==================== -->
    <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-[#777587] text-xs font-semibold mb-1">
          <Link :href="route('dashboard')" class="hover:text-[#5242e2] transition-colors">Beranda</Link>
          <span class="material-symbols-outlined text-[14px]">chevron_right</span>
          <Link :href="route('members.index')" class="hover:text-[#5242e2] transition-colors">Data Member</Link>
          <span class="material-symbols-outlined text-[14px]">chevron_right</span>
          <span class="text-[#5242e2]">{{ member.full_name }}</span>
        </div>
        <h1 class="text-2xl font-bold text-[#121c2c] tracking-tight">Profil Member & Riwayat Langganan</h1>
        <p class="text-xs text-[#474555]">
          Informasi profil keanggotaan, status kartu RFID, multi-langganan paket aktif, dan kuota sesi PT.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <Link 
          :href="route('members.edit', member.id)"
          class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-[#474555] bg-white border border-[#c8c4d8]/40 hover:bg-[#f0f3fb] transition-colors"
        >
          <span class="material-symbols-outlined text-[18px]">edit</span>
          <span>Edit Biodata</span>
        </Link>

        <button 
          @click="showAddSubModal = true"
          class="flex items-center gap-1.5 bg-[#6c5ffc] hover:bg-[#5242e2] text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm shadow-[#6c5ffc]/25 active:scale-95"
        >
          <span class="material-symbols-outlined text-[18px]">add_circle</span>
          <span>Tambah Paket Langganan</span>
        </button>
      </div>
    </section>

    <!-- ==================== TOP MEMBER PROFILE BANNER ==================== -->
    <section class="bg-white rounded-xl p-6 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        
        <div class="flex items-center gap-5">
          <div class="w-20 h-20 rounded-2xl overflow-hidden bg-gradient-to-br from-[#6c5ffc]/20 to-[#09ad95]/20 flex items-center justify-center border-2 border-white shadow-md shrink-0">
            <img 
              v-if="member.photo_asset_url" 
              :src="member.photo_asset_url" 
              :alt="member.full_name"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-2xl font-bold text-[#5242e2]">
              {{ getInitials(member.full_name) }}
            </span>
          </div>

          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-lg font-bold text-[#121c2c]">{{ member.full_name }}</h2>
              <span 
                class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full"
                :class="member.status === 'active' ? 'bg-[#09ad95]/10 text-[#09ad95]' : 'bg-gray-100 text-gray-600'"
              >
                {{ member.status }}
              </span>
            </div>

            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-[#777587]">
              <span class="font-mono font-bold text-[#5242e2] bg-[#5242e2]/10 px-2 py-0.5 rounded">
                {{ member.member_code }}
              </span>
              <span>•</span>
              <span class="flex items-center gap-1 font-mono text-[#121c2c]">
                <span class="material-symbols-outlined text-[14px]">call</span>
                {{ member.phone }}
              </span>
              <span v-if="member.email">•</span>
              <span v-if="member.email" class="flex items-center gap-1 text-[#474555]">
                <span class="material-symbols-outlined text-[14px]">mail</span>
                {{ member.email }}
              </span>
            </div>

            <div class="flex flex-wrap items-center gap-2 mt-2">
              <span v-if="member.nik" class="text-[11px] text-[#777587]">
                NIK: <span class="font-mono font-semibold text-[#121c2c]">{{ member.nik }}</span>
              </span>
              <span v-if="member.gender" class="text-[11px] text-[#777587]">
                ({{ member.gender === 'male' ? 'Laki-laki' : 'Perempuan' }})
              </span>
            </div>
          </div>
        </div>

        <!-- Right: Digital RFID Card Badge -->
        <div class="p-4 rounded-xl bg-gradient-to-br from-[#121c2c] to-[#283042] text-white shadow-md min-w-[240px]">
          <div class="flex items-center justify-between mb-3">
            <span class="text-[10px] uppercase tracking-wider text-[#77f8dd] font-bold">RFID DIGITAL PASS</span>
            <span class="material-symbols-outlined text-[18px] text-[#77f8dd]">contactless</span>
          </div>
          <p class="font-mono text-sm font-bold tracking-widest mb-1">
            {{ member.rfid_uid || 'NO-CARD-MAPPED' }}
          </p>
          <div class="flex items-center justify-between text-[10px] text-gray-300">
            <span>{{ member.member_code }}</span>
            <span class="uppercase">{{ member.gender === 'male' ? 'L' : 'P' }}</span>
          </div>
        </div>

      </div>
    </section>

    <!-- ==================== KEY KPI CARDS ==================== -->
    <section class="grid grid-cols-1 sm:grid-cols-4 gap-5">
      <!-- Status Akses Gym -->
      <div class="bg-white rounded-xl p-4 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
        <span class="text-xs font-semibold text-[#777587] block mb-1">Status Keanggotaan</span>
        <div class="flex items-center gap-2">
          <span 
            class="w-3 h-3 rounded-full"
            :class="member.has_active_subscription ? 'bg-[#09ad95] animate-ping' : 'bg-[#ba1a1a]'"
          ></span>
          <span 
            class="text-base font-bold"
            :class="member.has_active_subscription ? 'text-[#09ad95]' : 'text-[#ba1a1a]'"
          >
            {{ member.has_active_subscription ? 'Akses Aktif' : 'Tidak Aktif' }}
          </span>
        </div>
        <p class="text-[11px] text-[#777587] mt-1">Status izin masuk gerbang RFID</p>
      </div>

      <!-- Sisa Masa Aktif -->
      <div class="bg-white rounded-xl p-4 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
        <span class="text-xs font-semibold text-[#777587] block mb-1">Sisa Masa Aktif</span>
        <span class="text-2xl font-bold text-[#121c2c] tnum">
          {{ member.remaining_validity_days }} <span class="text-xs font-normal text-[#777587]">Hari</span>
        </span>
        <p class="text-[11px] text-[#777587] mt-1">Dihitung dari langganan gym aktif</p>
      </div>

      <!-- Sisa Sesi PT -->
      <div class="bg-white rounded-xl p-4 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
        <span class="text-xs font-semibold text-[#777587] block mb-1">Sisa Kuota Sesi PT</span>
        <span class="text-2xl font-bold text-[#b58004] tnum">
          {{ member.pt_session_balance }} <span class="text-xs font-normal text-[#777587]">Sesi Tersisa</span>
        </span>
        <p class="text-[11px] text-[#777587] mt-1">Total sesi PT belum terpakai</p>
      </div>

      <!-- Total Paket Terdaftar -->
      <div class="bg-white rounded-xl p-4 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
        <span class="text-xs font-semibold text-[#777587] block mb-1">Total Riwayat Paket</span>
        <span class="text-2xl font-bold text-[#5242e2] tnum">
          {{ member.subscriptions?.length || 0 }} <span class="text-xs font-normal text-[#777587]">Paket</span>
        </span>
        <p class="text-[11px] text-[#777587] mt-1">Termasuk paket aktif & selesai</p>
      </div>
    </section>

    <!-- ==================== SUBSCRIPTIONS LIST ==================== -->
    <section class="bg-white rounded-xl border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)] overflow-hidden">
      <div class="p-5 border-b border-[#e9edf4] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-sm font-bold text-[#121c2c] flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-[#6c5ffc]">card_membership</span>
            <span>Rincian Multi-Paket Langganan Member</span>
          </h2>
          <p class="text-[11px] text-[#777587]">
            Daftar paket aktif saat ini, paket dalam antrean (queued), dan arsip riwayat paket.
          </p>
        </div>

        <button 
          @click="showAddSubModal = true"
          class="flex items-center gap-1.5 bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#5242e2] px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
        >
          <span class="material-symbols-outlined text-[16px]">add</span>
          <span>Tambah Paket Baru</span>
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-[#f9f9ff] border-b border-[#e9edf4] text-[11px] font-bold text-[#777587] uppercase tracking-wider">
              <th class="py-3.5 px-4">Paket & Tipe</th>
              <th class="py-3.5 px-4">Instruktur PT</th>
              <th class="py-3.5 px-4">Periode Aktif</th>
              <th class="py-3.5 px-4 text-center">Sisa Waktu</th>
              <th class="py-3.5 px-4">Sesi Latihan PT</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#e9edf4] text-xs">
            <tr 
              v-for="sub in member.subscriptions" 
              :key="sub.id"
              class="hover:bg-[#f0f3fb]/60 transition-colors"
            >
              <!-- Paket & Tipe -->
              <td class="py-3.5 px-4">
                <span class="font-bold text-[#121c2c] block">{{ sub.package?.name }}</span>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span 
                    class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                    :class="getPackageBadgeClass(sub.package?.type)"
                  >
                    {{ sub.package?.type }}
                  </span>
                  <span class="text-[11px] text-[#777587]">
                    Durasi: {{ sub.package?.duration_days }} Hari
                  </span>
                </div>
              </td>

              <!-- Instruktur PT -->
              <td class="py-3.5 px-4">
                <div v-if="sub.personal_trainer" class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-[#f7b924]/20 text-[#b58004] flex items-center justify-center font-bold text-[10px]">
                    {{ getInitials(sub.personal_trainer.name) }}
                  </div>
                  <div>
                    <span class="font-bold text-[#121c2c] block text-xs">{{ sub.personal_trainer.name }}</span>
                    <span class="text-[10px] text-[#777587] block">{{ sub.personal_trainer.email }}</span>
                  </div>
                </div>
                <span v-else class="text-[11px] text-[#777587] italic">-</span>
              </td>

              <!-- Periode Aktif -->
              <td class="py-3.5 px-4">
                <div class="font-mono text-xs text-[#121c2c]">
                  <span>{{ formatDate(sub.start_date) }}</span>
                  <span class="text-[#777587] mx-1">s/d</span>
                  <span class="font-bold">{{ formatDate(sub.end_date) }}</span>
                </div>
              </td>

              <!-- Sisa Waktu -->
              <td class="py-3.5 px-4 text-center">
                <span 
                  v-if="sub.status === 'active'"
                  class="inline-block text-xs font-bold px-2.5 py-1 rounded-full tnum"
                  :class="getValidityBadgeClass(sub.remaining_days)"
                >
                  {{ sub.remaining_days }} Hari Lagi
                </span>
                <span 
                  v-else-if="sub.status === 'queued'"
                  class="inline-block text-[11px] font-bold text-[#5242e2] bg-[#5242e2]/10 px-2 py-0.5 rounded-full"
                >
                  Dalam Antrean
                </span>
                <span 
                  v-else
                  class="text-[11px] text-[#777587] bg-gray-100 px-2 py-0.5 rounded-full"
                >
                  {{ sub.status }}
                </span>
              </td>

              <!-- Sesi Latihan PT -->
              <td class="py-3.5 px-4">
                <div v-if="sub.total_sessions > 0" class="space-y-1">
                  <div class="flex items-center justify-between text-[11px]">
                    <span class="font-semibold text-[#474555]">
                      {{ sub.used_sessions }} / {{ sub.total_sessions }} Sesi
                    </span>
                    <span class="font-bold text-[#b58004]">
                      Sisa: {{ sub.remaining_sessions }}
                    </span>
                  </div>
                  <div class="w-36 h-2 rounded-full bg-[#f0f3fb] overflow-hidden border border-[#c8c4d8]/30">
                    <div 
                      class="h-full bg-gradient-to-r from-[#f7b924] to-[#09ad95] rounded-full transition-all"
                      :style="{ width: Math.min(100, (sub.used_sessions / sub.total_sessions) * 100) + '%' }"
                    ></div>
                  </div>
                </div>
                <span v-else class="text-[11px] text-[#777587] italic">Hanya Akses Gym</span>
              </td>

              <!-- Status -->
              <td class="py-3.5 px-4 text-center">
                <span 
                  class="text-[10px] uppercase font-bold px-2.5 py-0.5 rounded-full"
                  :class="getSubscriptionStatusBadgeClass(sub.status)"
                >
                  {{ sub.status }}
                </span>
              </td>

              <!-- Aksi -->
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button 
                    v-if="sub.status === 'queued'"
                    @click="activateSubscription(sub.id)"
                    class="px-2.5 py-1 text-[11px] font-bold text-[#09ad95] bg-[#09ad95]/10 hover:bg-[#09ad95]/20 rounded-lg transition-colors"
                    title="Aktifkan Langganan Sekarang"
                  >
                    Aktifkan Hari Ini
                  </button>

                  <button 
                    v-if="sub.status === 'active' || sub.status === 'queued'"
                    @click="cancelSubscription(sub.id)"
                    class="p-1 text-[#ba1a1a] hover:bg-[#ba1a1a]/10 rounded-lg transition-colors"
                    title="Batalkan Langganan"
                  >
                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!member.subscriptions || member.subscriptions.length === 0">
              <td colspan="7" class="py-8 text-center text-xs text-[#777587]">
                Belum ada paket langganan yang didaftarkan untuk member ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ==================== AKSES PORTAL MEMBER (ATUR PASSWORD) ==================== -->
    <section class="bg-white rounded-2xl border border-[#c8c4d8]/40 shadow-xs p-5 mt-6">
      <div class="flex flex-col md:flex-row md:items-start gap-5">
        <div class="md:w-1/3">
          <h2 class="text-sm font-bold text-[#121c2c] flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-[#5242e2]">lock_person</span>
            Akses Portal Member
          </h2>
          <p class="text-xs text-[#777587] mt-1">
            Member login di <strong>/portal/login</strong> memakai email, nomor HP, atau kode member + password ini.
          </p>
          <span
            class="inline-block mt-2 px-2 py-0.5 rounded-md text-[10px] font-bold"
            :class="hasPortalPassword ? 'bg-[#09ad95]/10 text-[#09ad95]' : 'bg-amber-100 text-amber-700'"
          >
            {{ hasPortalPassword ? 'Password sudah diatur' : 'Belum punya password portal' }}
          </span>
        </div>

        <form @submit.prevent="submitPortalPassword" class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
          <div>
            <label class="block text-[11px] font-semibold text-[#474555] mb-1">Password Baru</label>
            <input
              v-model="portalPasswordForm.password"
              type="password"
              autocomplete="new-password"
              placeholder="Minimal 8 karakter"
              class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc]"
            />
            <span v-if="portalPasswordForm.errors.password" class="text-[11px] text-[#ba1a1a] mt-1 block">
              {{ portalPasswordForm.errors.password }}
            </span>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-[#474555] mb-1">Ulangi Password</label>
            <input
              v-model="portalPasswordForm.password_confirmation"
              type="password"
              autocomplete="new-password"
              placeholder="Ketik ulang password"
              class="w-full h-9 px-3 rounded-xl border border-[#c8c4d8]/60 text-xs outline-none focus:border-[#6c5ffc]"
            />
          </div>
          <div class="sm:pt-[18px]">
            <button
              type="submit"
              :disabled="portalPasswordForm.processing"
              class="w-full h-9 rounded-xl bg-[#6c5ffc] hover:bg-[#5242e2] text-white text-xs font-bold disabled:opacity-50"
            >
              {{ hasPortalPassword ? 'Reset Password' : 'Simpan Password' }}
            </button>
          </div>
        </form>
      </div>
    </section>

    <!-- ==================== MODAL: TAMBAH PAKET LANGGANAN ==================== -->
    <div 
      v-if="showAddSubModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-[#e9edf4] space-y-4">
        
        <div class="flex items-center justify-between pb-3 border-b border-[#e9edf4]">
          <div>
            <h3 class="text-base font-bold text-[#121c2c] flex items-center gap-2">
              <span class="material-symbols-outlined text-[#6c5ffc]">add_circle</span>
              <span>Tambah Langganan Paket Baru</span>
            </h3>
            <p class="text-xs text-[#777587]">Member: {{ member.full_name }} ({{ member.member_code }})</p>
          </div>
          <button 
            @click="showAddSubModal = false"
            class="text-[#777587] hover:text-[#121c2c] p-1.5 rounded-lg hover:bg-gray-100"
          >
            <span class="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        <form @submit.prevent="submitSubscription" class="space-y-4">
          
          <!-- Pilih Paket -->
          <div>
            <label class="block text-xs font-bold text-[#474555] mb-1">
              Pilih Paket Langganan <span class="text-[#ba1a1a]">*</span>
            </label>
            <select 
              v-model="subForm.package_id" 
              class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-white border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none cursor-pointer"
              required
            >
              <option :value="null">-- Pilih Salah Satu Paket --</option>
              <option 
                v-for="pkg in packages" 
                :key="pkg.id" 
                :value="pkg.id"
              >
                {{ pkg.name }} ({{ pkg.duration_days }} Hari) - Rp {{ Number(pkg.price).toLocaleString('id-ID') }}
              </option>
            </select>
            <span v-if="subForm.errors.package_id" class="text-[11px] text-[#ba1a1a] block mt-1 font-medium">
              {{ subForm.errors.package_id }}
            </span>
          </div>

          <!-- Package Preview Card -->
          <div v-if="selectedNewPackage" class="p-4 rounded-xl bg-[#e8eeff]/60 border border-[#c8c4d8]/40 space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-[#121c2c]">{{ selectedNewPackage.name }}</span>
              <span class="text-xs font-bold text-[#5242e2]">
                Rp {{ Number(selectedNewPackage.price).toLocaleString('id-ID') }}
              </span>
            </div>
            <p class="text-[11px] text-[#474555]">{{ selectedNewPackage.description }}</p>

            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#c8c4d8]/30 text-xs">
              <div>
                <span class="text-[10px] text-[#777587] block">Mulai Tanggal</span>
                <span class="font-bold text-[#121c2c]">{{ calculatedNewStartDate }}</span>
              </div>
              <div>
                <span class="text-[10px] text-[#777587] block">Berakhir Tanggal</span>
                <span class="font-bold text-[#09ad95]">{{ calculatedNewEndDate }}</span>
              </div>
            </div>
          </div>

          <!-- Personal Trainer Selector -->
          <div v-if="selectedNewPackage && (selectedNewPackage.type === 'pt' || selectedNewPackage.type === 'combo')">
            <label class="block text-xs font-bold text-[#474555] mb-1">
              Instruktur Personal Trainer (PT) <span class="text-[#ba1a1a]">*</span>
            </label>
            <select 
              v-model="subForm.pt_user_id" 
              class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-white border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none cursor-pointer"
              required
            >
              <option :value="null">-- Pilih Pelatih Pendamping --</option>
              <option 
                v-for="trainer in personalTrainers" 
                :key="trainer.id" 
                :value="trainer.id"
              >
                {{ trainer.name }} ({{ trainer.email }})
              </option>
            </select>
            <span v-if="subForm.errors.pt_user_id" class="text-[11px] text-[#ba1a1a] block mt-1 font-medium">
              {{ subForm.errors.pt_user_id }}
            </span>
          </div>

          <!-- Queueing Strategy Option -->
          <div class="space-y-2 pt-1">
            <label class="flex items-start gap-2.5 cursor-pointer">
              <input 
                type="checkbox" 
                v-model="subForm.queue_after_active" 
                class="mt-0.5 rounded border-[#c8c4d8] text-[#6c5ffc] focus:ring-[#6c5ffc]"
              />
              <div>
                <span class="text-xs font-bold text-[#121c2c] block">
                  Antrekan otomatis setelah paket aktif selesai (Queued)
                </span>
                <span class="text-[11px] text-[#777587] block">
                  Paket baru akan mulai aktif sehari setelah paket aktif saat ini berakhir (disarankan: {{ suggestedQueueDate }}).
                </span>
              </div>
            </label>
          </div>

          <!-- Custom Start Date -->
          <div v-if="!subForm.queue_after_active">
            <label class="block text-xs font-bold text-[#474555] mb-1">
              Tanggal Mulai Berlaku
            </label>
            <input 
              v-model="subForm.start_date" 
              type="date" 
              class="w-full h-10 px-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none"
            />
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-xs font-bold text-[#474555] mb-1">Catatan Tambahan</label>
            <textarea 
              v-model="subForm.notes" 
              rows="2" 
              placeholder="Catatan langganan / pembayaran..."
              class="w-full p-2.5 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none"
            ></textarea>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#e9edf4]">
            <button 
              type="button" 
              @click="showAddSubModal = false"
              class="px-4 py-2 text-xs font-bold text-[#474555] hover:bg-gray-100 rounded-xl transition-colors"
            >
              Batal
            </button>

            <button 
              type="submit" 
              :disabled="subForm.processing"
              class="flex items-center gap-1.5 bg-[#6c5ffc] hover:bg-[#5242e2] disabled:opacity-50 text-white px-5 py-2 rounded-xl text-xs font-bold transition-all shadow-md shadow-[#6c5ffc]/25 active:scale-95"
            >
              <span v-if="subForm.processing" class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
              <span v-else class="material-symbols-outlined text-[16px]">check</span>
              <span>Simpan & Aktifkan Paket</span>
            </button>
          </div>

        </form>

      </div>
    </div>

  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  member: Object,
  packages: Array,
  personalTrainers: Array,
  suggestedQueueDate: String,
  // true jika member sudah punya password portal (password-nya sendiri tidak dikirim)
  hasPortalPassword: Boolean,
});

// Form atur / reset password portal member
const portalPasswordForm = useForm({
  password: '',
  password_confirmation: '',
});

const submitPortalPassword = () => {
  portalPasswordForm.put(route('members.portal-password.update', props.member.id), {
    preserveScroll: true,
    // Kosongkan kolom password setelah berhasil disimpan
    onSuccess: () => portalPasswordForm.reset(),
  });
};

const showAddSubModal = ref(false);

const subForm = useForm({
  package_id: null,
  pt_user_id: null,
  start_date: new Date().toISOString().split('T')[0],
  queue_after_active: props.member.has_active_subscription,
  notes: '',
});

const selectedNewPackage = computed(() => {
  if (!subForm.package_id) return null;
  return props.packages.find((p) => p.id === subForm.package_id) || null;
});

const calculatedNewStartDate = computed(() => {
  if (subForm.queue_after_active) {
    return new Date(props.suggestedQueueDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  }
  const date = subForm.start_date ? new Date(subForm.start_date) : new Date();
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
});

const calculatedNewEndDate = computed(() => {
  if (!selectedNewPackage.value) return '-';
  const start = subForm.queue_after_active
    ? new Date(props.suggestedQueueDate)
    : (subForm.start_date ? new Date(subForm.start_date) : new Date());

  const end = new Date(start);
  end.setDate(end.getDate() + selectedNewPackage.value.duration_days - 1);
  return end.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
});

const submitSubscription = () => {
  subForm.post(route('subscriptions.store', props.member.id), {
    preserveScroll: true,
    onSuccess: () => {
      showAddSubModal.value = false;
      subForm.reset();
    },
  });
};

const cancelSubscription = (subId) => {
  if (confirm('Apakah Anda yakin ingin membatalkan status langganan ini?')) {
    router.post(route('subscriptions.cancel', subId), {}, {
      preserveScroll: true,
    });
  }
};

const activateSubscription = (subId) => {
  if (confirm('Aktifkan paket dalam antrean ini per hari ini?')) {
    router.post(route('subscriptions.activate', subId), {}, {
      preserveScroll: true,
    });
  }
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

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
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
  if (days <= 0) return 'bg-[#ba1a1a]/10 text-[#ba1a1a]';
  if (days <= 7) return 'bg-[#f7b924]/20 text-[#b58004]';
  return 'bg-[#09ad95]/10 text-[#09ad95]';
};

const getSubscriptionStatusBadgeClass = (status) => {
  switch (status) {
    case 'active':
      return 'bg-[#09ad95]/10 text-[#09ad95] border border-[#09ad95]/20';
    case 'queued':
      return 'bg-[#5242e2]/10 text-[#5242e2] border border-[#5242e2]/20';
    case 'expired':
      return 'bg-gray-100 text-gray-500 border border-gray-200';
    case 'cancelled':
      return 'bg-[#ba1a1a]/10 text-[#ba1a1a] border border-[#ba1a1a]/20';
    default:
      return 'bg-gray-100 text-gray-700';
  }
};
</script>
