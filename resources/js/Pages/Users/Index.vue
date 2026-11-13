<template>
  <AuthenticatedLayout>
    <div class="space-y-6">

      <!-- ==================== HEADER ==================== -->
      <section class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
          <div class="flex items-center gap-1.5 text-xs text-[#777587] mb-1">
            <Link :href="route('dashboard')" class="hover:text-[#5242e2] transition-colors">Beranda</Link>
            <span class="material-symbols-outlined text-[13px]">chevron_right</span>
            <span>Pengaturan</span>
            <span class="material-symbols-outlined text-[13px]">chevron_right</span>
            <span class="text-[#5242e2] font-semibold">Manajemen User</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-[#121c2c] tracking-tight">Manajemen User (RBAC)</h1>
          <p class="text-xs text-[#777587] mt-0.5">
            Kelola akun staf: tambah user, atur role, reset password, dan nonaktifkan akun.
          </p>
        </div>
        <button
          type="button"
          @click="openCreateModal"
          class="h-10 px-4 rounded-xl bg-[#6c5ffc] hover:bg-[#5242e2] text-white text-xs font-bold flex items-center gap-1.5 shadow-md shadow-[#6c5ffc]/20"
        >
          <span class="material-symbols-outlined text-[18px]">person_add</span>
          Tambah User
        </button>
      </section>

      <!-- ==================== RINGKASAN ==================== -->
      <section class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-[11px] font-semibold text-[#777587]">Total Staf</p>
          <p class="text-2xl font-black text-[#121c2c] tnum mt-1">{{ summary.total }}</p>
        </div>
        <div v-for="role in roles" :key="role" class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-[11px] font-semibold text-[#777587]">{{ roleLabels[role] }}</p>
          <p class="text-2xl font-black tnum mt-1" :class="roleStyles[role].text">{{ summary.per_role[role] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-[#e9edf4] p-4">
          <p class="text-[11px] font-semibold text-[#777587]">Nonaktif</p>
          <p class="text-2xl font-black text-[#ba1a1a] tnum mt-1">{{ summary.inactive }}</p>
        </div>
      </section>

      <!-- ==================== FILTER ==================== -->
      <form @submit.prevent="applyFilters" class="bg-white rounded-2xl border border-[#e9edf4] p-4 flex flex-col sm:flex-row gap-3 sm:items-center">
        <div class="relative flex-1 sm:max-w-xs">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
          <input
            v-model="filterForm.search"
            type="text"
            placeholder="Cari nama atau email..."
            class="w-full h-10 pl-9 pr-3 rounded-xl border border-[#c8c4d8]/40 text-xs bg-[#f8fafc] focus:bg-white focus:border-[#5242e2] outline-none"
          />
        </div>
        <div class="relative shrink-0">
          <select
            v-model="filterForm.role"
            @change="applyFilters"
            class="appearance-none h-10 min-w-[170px] pl-3 pr-9 rounded-xl border border-[#c8c4d8]/40 text-xs bg-[#f8fafc] focus:bg-white focus:border-[#5242e2] outline-none cursor-pointer"
          >
            <option value="">Semua Role</option>
            <option v-for="role in roles" :key="role" :value="role">{{ roleLabels[role] }}</option>
          </select>
          <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">expand_more</span>
        </div>
        <div class="flex gap-2 sm:ml-auto">
          <button type="submit" class="h-10 px-4 rounded-xl bg-[#6c5ffc] hover:bg-[#5242e2] text-white text-xs font-bold">Cari</button>
          <button type="button" @click="resetFilters" class="h-10 px-4 rounded-xl bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#474555] text-xs font-semibold">Reset</button>
        </div>
      </form>

      <!-- ==================== TABEL USER ==================== -->
      <section class="bg-white rounded-2xl border border-[#e9edf4] p-5">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[820px]">
            <thead>
              <tr class="text-[10px] uppercase tracking-wider text-[#777587] bg-[#f8f9fc]">
                <th class="py-2.5 px-3 font-bold">User</th>
                <th class="py-2.5 px-3 font-bold">Role</th>
                <th class="py-2.5 px-3 font-bold">Status</th>
                <th class="py-2.5 px-3 font-bold">PIN Supervisor</th>
                <th class="py-2.5 px-3 font-bold">Dibuat</th>
                <th class="py-2.5 px-3 font-bold text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f0f3f8] text-xs">
              <tr v-for="user in users.data" :key="user.id" class="hover:bg-[#fbfcfe] transition-colors" :class="{ 'opacity-60': !user.is_active }">
                <td class="py-3 px-3">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0" :class="roleStyles[user.role]?.badge ?? 'bg-slate-100 text-slate-500'">
                      {{ initialsOf(user.name) }}
                    </div>
                    <div class="min-w-0">
                      <p class="font-bold text-[#121c2c] leading-tight truncate">
                        {{ user.name }}
                        <span v-if="user.is_current_user" class="ml-1 text-[9px] font-bold text-[#5242e2] bg-[#e8eeff] px-1.5 py-0.5 rounded-full">Anda</span>
                      </p>
                      <p class="text-[10px] text-[#777587] truncate">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3">
                  <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold" :class="roleStyles[user.role]?.badge ?? 'bg-slate-100 text-slate-500'">
                    {{ roleLabels[user.role] ?? 'Tanpa role' }}
                  </span>
                </td>
                <td class="py-3 px-3">
                  <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="user.is_active ? 'bg-[#09ad95]/10 text-[#09ad95]' : 'bg-[#ba1a1a]/10 text-[#ba1a1a]'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="user.is_active ? 'bg-[#09ad95]' : 'bg-[#ba1a1a]'"></span>
                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="py-3 px-3 text-[11px]">
                  <!-- PIN tidak pernah ditampilkan, hanya status sudah/belum diatur -->
                  <span v-if="!rolesWithSupervisorPin.includes(user.role)" class="text-[#c8c4d8]">Tidak berlaku</span>
                  <span v-else-if="user.has_supervisor_pin" class="text-[#09ad95] font-semibold">Sudah diatur</span>
                  <span v-else class="text-[#f7b924] font-semibold">Belum diatur</span>
                </td>
                <td class="py-3 px-3 text-[11px] text-[#777587] tnum">{{ user.created_at }}</td>
                <td class="py-3 px-3">
                  <div class="flex items-center justify-end gap-1">
                    <button type="button" @click="openEditModal(user)" class="p-1.5 rounded-lg text-[#5242e2] hover:bg-[#e8eeff]" title="Edit user">
                      <span class="material-symbols-outlined text-[18px]">edit</span>
                    </button>
                    <button type="button" @click="openPasswordModal(user)" class="p-1.5 rounded-lg text-[#f7b924] hover:bg-[#f7b924]/10" title="Reset password">
                      <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                    </button>
                    <!-- Akun sendiri tidak bisa dinonaktifkan -->
                    <button
                      v-if="!user.is_current_user"
                      type="button"
                      @click="toggleActive(user)"
                      class="p-1.5 rounded-lg"
                      :class="user.is_active ? 'text-[#ba1a1a] hover:bg-[#ba1a1a]/10' : 'text-[#09ad95] hover:bg-[#09ad95]/10'"
                      :title="user.is_active ? 'Nonaktifkan akun' : 'Aktifkan kembali'"
                    >
                      <span class="material-symbols-outlined text-[18px]">{{ user.is_active ? 'person_off' : 'person_check' }}</span>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Tampilan kosong -->
              <tr v-if="users.data.length === 0">
                <td colspan="6" class="py-10 text-center text-[#777587]">
                  <span class="material-symbols-outlined text-[32px] text-[#c8c4d8] block mb-1">group_off</span>
                  Tidak ada user yang cocok dengan filter ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginasi: filter tetap terbawa karena URL dari server sudah berisi query filter -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 pt-3 mt-2 border-t border-[#e9edf4] text-xs">
          <span class="text-[#777587] text-[11px]">
            <template v-if="users.total > 0">Menampilkan {{ users.from }}–{{ users.to }} dari {{ users.total }} user</template>
            <template v-else>0 user</template>
          </span>
          <div class="flex items-center gap-2">
            <Link v-if="users.prev_page_url" :href="users.prev_page_url" preserve-scroll class="px-3 py-1 rounded-lg border border-[#c8c4d8]/40 text-[#474555] hover:bg-[#e8eeff]">Sebelumnya</Link>
            <span class="text-[11px] text-[#777587]">Halaman {{ users.current_page }} dari {{ users.last_page }}</span>
            <Link v-if="users.next_page_url" :href="users.next_page_url" preserve-scroll class="px-3 py-1 rounded-lg border border-[#c8c4d8]/40 text-[#474555] hover:bg-[#e8eeff]">Berikutnya</Link>
          </div>
        </div>
      </section>
    </div>

    <!-- ==================== MODAL TAMBAH / EDIT USER ==================== -->
    <div v-if="showUserModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" @click.self="closeUserModal">
      <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-[#e9edf4]">
        <div class="p-5 border-b border-[#e9edf4] flex items-center justify-between bg-slate-50">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[#5242e2] text-[20px]">{{ editingUser ? 'edit' : 'person_add' }}</span>
            <h3 class="text-sm font-bold text-[#121c2c]">{{ editingUser ? `Ubah User: ${editingUser.name}` : 'Tambah User Baru' }}</h3>
          </div>
          <button type="button" @click="closeUserModal" class="text-slate-400 hover:text-slate-700" aria-label="Tutup">
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>

        <form @submit.prevent="submitUserForm" class="p-5 space-y-3.5 text-xs">
          <!-- Nama -->
          <div>
            <label for="user_name" class="block font-bold text-[#121c2c] mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
            <input id="user_name" v-model="userForm.name" type="text" required class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 outline-none focus:border-[#5242e2]" />
            <p v-if="userForm.errors.name" class="text-[#ba1a1a] text-[11px] mt-1">{{ userForm.errors.name }}</p>
          </div>

          <!-- Email -->
          <div>
            <label for="user_email" class="block font-bold text-[#121c2c] mb-1">Email (untuk login) <span class="text-red-500">*</span></label>
            <input id="user_email" v-model="userForm.email" type="email" required class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 outline-none focus:border-[#5242e2]" />
            <p v-if="userForm.errors.email" class="text-[#ba1a1a] text-[11px] mt-1">{{ userForm.errors.email }}</p>
          </div>

          <!-- Role -->
          <div>
            <label for="user_role" class="block font-bold text-[#121c2c] mb-1">Role <span class="text-red-500">*</span></label>
            <div class="relative">
              <select
                id="user_role"
                v-model="userForm.role"
                :disabled="editingUser?.is_current_user"
                class="appearance-none w-full h-10 pl-3 pr-9 rounded-xl border border-[#c8c4d8]/50 bg-white outline-none focus:border-[#5242e2] disabled:bg-slate-100 disabled:text-slate-500"
              >
                <option v-for="role in roles" :key="role" :value="role">{{ roleLabels[role] }} — {{ roleDescriptions[role] }}</option>
              </select>
              <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">expand_more</span>
            </div>
            <p v-if="editingUser?.is_current_user" class="text-[10px] text-[#777587] mt-1">Role akun Anda sendiri tidak bisa diubah.</p>
            <p v-if="userForm.errors.role" class="text-[#ba1a1a] text-[11px] mt-1">{{ userForm.errors.role }}</p>
          </div>

          <!-- Password: hanya saat TAMBAH user. Untuk user lama, pakai tombol "Reset Password". -->
          <div v-if="!editingUser" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="user_password" class="block font-bold text-[#121c2c] mb-1">Password <span class="text-red-500">*</span></label>
              <input id="user_password" v-model="userForm.password" type="password" required autocomplete="new-password" class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 outline-none focus:border-[#5242e2]" />
            </div>
            <div>
              <label for="user_password_confirmation" class="block font-bold text-[#121c2c] mb-1">Ulangi Password <span class="text-red-500">*</span></label>
              <input id="user_password_confirmation" v-model="userForm.password_confirmation" type="password" required autocomplete="new-password" class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 outline-none focus:border-[#5242e2]" />
            </div>
            <p v-if="userForm.errors.password" class="sm:col-span-2 text-[#ba1a1a] text-[11px]">{{ userForm.errors.password }}</p>
            <p class="sm:col-span-2 text-[10px] text-[#777587]">Minimal 8 karakter.</p>
          </div>

          <!-- PIN Supervisor: hanya untuk admin & supervisor -->
          <div v-if="roleNeedsPin" class="rounded-xl bg-[#f8f9fc] border border-[#e9edf4] p-3">
            <label for="user_pin" class="block font-bold text-[#121c2c] mb-1">PIN Supervisor (6 digit)</label>
            <input
              id="user_pin"
              v-model="userForm.supervisor_pin"
              type="password"
              inputmode="numeric"
              maxlength="6"
              autocomplete="off"
              :placeholder="editingUser ? 'Kosongkan jika tidak ingin mengganti PIN' : 'Opsional'"
              class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 bg-white outline-none focus:border-[#5242e2] font-mono tracking-widest"
            />
            <p class="text-[10px] text-[#777587] mt-1">Dipakai untuk menyetujui VOID transaksi dan diskon besar di kasir.</p>
            <p v-if="userForm.errors.supervisor_pin" class="text-[#ba1a1a] text-[11px] mt-1">{{ userForm.errors.supervisor_pin }}</p>
          </div>
          <p v-else-if="editingUser?.has_supervisor_pin" class="text-[10px] text-[#f7b924] font-semibold">
            Role {{ roleLabels[userForm.role] }} tidak memakai PIN supervisor. PIN lama user ini akan dihapus saat disimpan.
          </p>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="closeUserModal" class="h-10 px-4 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-100">Batal</button>
            <button type="submit" :disabled="userForm.processing" class="h-10 px-5 rounded-xl bg-[#6c5ffc] hover:bg-[#5242e2] text-white font-bold disabled:opacity-60">
              {{ userForm.processing ? 'Menyimpan...' : (editingUser ? 'Simpan Perubahan' : 'Tambah User') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ==================== MODAL RESET PASSWORD ==================== -->
    <div v-if="passwordUser" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" @click.self="closePasswordModal">
      <div class="bg-white rounded-3xl max-w-sm w-full overflow-hidden shadow-2xl border border-[#e9edf4]">
        <div class="p-5 border-b border-[#e9edf4] flex items-center justify-between bg-slate-50">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[#f7b924] text-[20px]">lock_reset</span>
            <h3 class="text-sm font-bold text-[#121c2c]">Reset Password</h3>
          </div>
          <button type="button" @click="closePasswordModal" class="text-slate-400 hover:text-slate-700" aria-label="Tutup">
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>

        <form @submit.prevent="submitPasswordForm" class="p-5 space-y-3.5 text-xs">
          <p class="text-[#474555]">Atur password baru untuk <strong>{{ passwordUser.name }}</strong> ({{ passwordUser.email }}).</p>
          <div>
            <label for="reset_password" class="block font-bold text-[#121c2c] mb-1">Password Baru</label>
            <input id="reset_password" v-model="passwordForm.password" type="password" required autocomplete="new-password" class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 outline-none focus:border-[#5242e2]" />
          </div>
          <div>
            <label for="reset_password_confirmation" class="block font-bold text-[#121c2c] mb-1">Ulangi Password Baru</label>
            <input id="reset_password_confirmation" v-model="passwordForm.password_confirmation" type="password" required autocomplete="new-password" class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 outline-none focus:border-[#5242e2]" />
          </div>
          <p v-if="passwordForm.errors.password" class="text-[#ba1a1a] text-[11px]">{{ passwordForm.errors.password }}</p>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="closePasswordModal" class="h-10 px-4 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-100">Batal</button>
            <button type="submit" :disabled="passwordForm.processing" class="h-10 px-5 rounded-xl bg-[#f7b924] hover:bg-[#e0a612] text-white font-bold disabled:opacity-60">
              {{ passwordForm.processing ? 'Menyimpan...' : 'Reset Password' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { route } from '@/route';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  // Data paginasi Laravel: { data: [...], total, from, to, current_page, last_page, prev_page_url, next_page_url }
  users: Object,
  // Ringkasan: { total, inactive, per_role: { admin, supervisor, cs, pt } }
  summary: Object,
  // Filter aktif: { search, role }
  filters: Object,
  // Daftar role yang boleh dipilih: ['admin', 'supervisor', 'cs', 'pt']
  roles: Array,
  // Role yang memakai PIN supervisor: ['admin', 'supervisor']
  rolesWithSupervisorPin: Array,
});

// ---------------- Label & warna per role ----------------
const roleLabels = {
  admin: 'Admin',
  supervisor: 'Supervisor',
  cs: 'CS / Frontliner',
  pt: 'Personal Trainer',
};

const roleDescriptions = {
  admin: 'akses penuh',
  supervisor: 'operasional, void, laporan',
  cs: 'member, kios, kasir',
  pt: 'sesi personal trainer',
};

const roleStyles = {
  admin: { badge: 'bg-[#5242e2]/10 text-[#5242e2]', text: 'text-[#5242e2]' },
  supervisor: { badge: 'bg-[#f7b924]/15 text-[#b8860b]', text: 'text-[#f7b924]' },
  cs: { badge: 'bg-[#09ad95]/10 text-[#09ad95]', text: 'text-[#09ad95]' },
  pt: { badge: 'bg-[#05c3fb]/10 text-[#0596c2]', text: 'text-[#05c3fb]' },
};

// Inisial nama untuk avatar, contoh: "Budi Santoso" -> "BS"
const initialsOf = (name) => name.split(' ').filter(Boolean).slice(0, 2).map((word) => word[0]).join('').toUpperCase();

// ---------------- Filter ----------------
const filterForm = ref({ ...props.filters });

const applyFilters = () => {
  router.get(route('users.index'), filterForm.value, { preserveScroll: true, preserveState: true });
};

const resetFilters = () => {
  router.get(route('users.index'));
};

// ---------------- Modal Tambah / Edit ----------------
const showUserModal = ref(false);
const editingUser = ref(null); // null = mode TAMBAH, berisi user = mode EDIT

const userForm = useForm({
  name: '',
  email: '',
  role: 'cs',
  password: '',
  password_confirmation: '',
  supervisor_pin: '',
});

// Field PIN hanya muncul untuk role admin & supervisor
const roleNeedsPin = computed(() => props.rolesWithSupervisorPin.includes(userForm.role));

const openCreateModal = () => {
  editingUser.value = null;
  userForm.reset();
  userForm.clearErrors();
  showUserModal.value = true;
};

const openEditModal = (user) => {
  editingUser.value = user;
  userForm.clearErrors();
  userForm.name = user.name;
  userForm.email = user.email;
  userForm.role = user.role ?? 'cs';
  userForm.password = '';
  userForm.password_confirmation = '';
  userForm.supervisor_pin = '';
  showUserModal.value = true;
};

const closeUserModal = () => {
  showUserModal.value = false;
};

const submitUserForm = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showUserModal.value = false;
      userForm.reset();
    },
  };

  if (editingUser.value) {
    userForm.put(route('users.update', editingUser.value.id), options);
  } else {
    userForm.post(route('users.store'), options);
  }
};

// ---------------- Modal Reset Password ----------------
const passwordUser = ref(null); // user yang password-nya sedang direset (null = modal tertutup)

const passwordForm = useForm({
  password: '',
  password_confirmation: '',
});

const openPasswordModal = (user) => {
  passwordUser.value = user;
  passwordForm.reset();
  passwordForm.clearErrors();
};

const closePasswordModal = () => {
  passwordUser.value = null;
};

const submitPasswordForm = () => {
  passwordForm.put(route('users.password.update', passwordUser.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      passwordUser.value = null;
      passwordForm.reset();
    },
  });
};

// ---------------- Aktif / Nonaktif ----------------
const toggleActive = (user) => {
  const question = user.is_active
    ? `Nonaktifkan akun ${user.name}? User ini tidak akan bisa login sampai diaktifkan kembali.`
    : `Aktifkan kembali akun ${user.name}?`;

  if (!confirm(question)) {
    return;
  }

  router.post(route('users.toggle-active', user.id), {}, { preserveScroll: true });
};
</script>
