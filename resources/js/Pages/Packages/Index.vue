<template>
  <AuthenticatedLayout>
    
    <!-- ==================== HEADER & BREADCRUMB ==================== -->
    <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-[#777587] text-xs font-semibold mb-1">
          <Link :href="route('dashboard')" class="hover:text-[#5242e2] transition-colors">Beranda</Link>
          <span class="material-symbols-outlined text-[14px]">chevron_right</span>
          <span>Keanggotaan</span>
          <span class="material-symbols-outlined text-[14px]">chevron_right</span>
          <span class="text-[#5242e2]">Katalog Paket</span>
        </div>
        <h1 class="text-2xl font-bold text-[#121c2c] tracking-tight">Katalog Paket Langganan Gym & PT</h1>
        <p class="text-xs text-[#474555]">
          Kelola master paket Gym Reguler, Personal Trainer, dan paket Combo (Gym + Sesi PT).
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button 
          v-if="canManage"
          @click="openCreateModal"
          class="flex items-center gap-2 bg-[#6c5ffc] hover:bg-[#5242e2] text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 shadow-sm shadow-[#6c5ffc]/25 active:scale-95"
        >
          <span class="material-symbols-outlined text-[18px]">add</span>
          <span>Buat Paket Baru</span>
        </button>
      </div>
    </section>

    <!-- ==================== PACKAGES GRID ==================== -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="pkg in packages" 
        :key="pkg.id"
        class="bg-white rounded-2xl p-6 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)] hover:shadow-[0px_10px_24px_-4px_rgba(108,95,252,0.08)] transition-all duration-200 flex flex-col justify-between"
      >
        <div>
          <!-- Header Badge -->
          <div class="flex items-center justify-between mb-3">
            <span 
              class="text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
              :class="getPackageBadgeClass(pkg.type)"
            >
              {{ pkg.type }}
            </span>

            <span 
              class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
              :class="pkg.is_active ? 'bg-[#09ad95]/10 text-[#09ad95]' : 'bg-gray-100 text-gray-500'"
            >
              {{ pkg.is_active ? 'Aktif' : 'Non-aktif' }}
            </span>
          </div>

          <!-- Package Title & Price -->
          <h2 class="text-base font-bold text-[#121c2c] mb-1">{{ pkg.name }}</h2>
          <div class="flex items-baseline gap-1 mb-3">
            <span class="text-2xl font-bold text-[#5242e2] tnum">
              Rp {{ Number(pkg.price).toLocaleString('id-ID') }}
            </span>
            <span class="text-xs text-[#777587]">/ {{ pkg.duration_days }} hari</span>
          </div>

          <p class="text-xs text-[#474555] mb-4 min-h-[36px]">
            {{ pkg.description || 'Tidak ada deskripsi paket.' }}
          </p>

          <!-- Metrics / Features -->
          <div class="space-y-2 py-3 border-t border-[#e9edf4] text-xs">
            <div class="flex items-center justify-between text-[#474555]">
              <span class="flex items-center gap-1.5 text-[#777587]">
                <span class="material-symbols-outlined text-[16px]">schedule</span>
                <span>Masa Berlaku</span>
              </span>
              <span class="font-bold text-[#121c2c]">{{ pkg.duration_days }} Hari</span>
            </div>

            <div class="flex items-center justify-between text-[#474555]">
              <span class="flex items-center gap-1.5 text-[#777587]">
                <span class="material-symbols-outlined text-[16px]">sports_gymnastics</span>
                <span>Alokasi Sesi PT</span>
              </span>
              <span class="font-bold text-[#b58004]">
                {{ pkg.session_count > 0 ? pkg.session_count + ' Sesi' : '-' }}
              </span>
            </div>

            <div class="flex items-center justify-between text-[#474555]">
              <span class="flex items-center gap-1.5 text-[#777587]">
                <span class="material-symbols-outlined text-[16px]">how_to_reg</span>
                <span>Member Aktif</span>
              </span>
              <span class="font-bold text-[#09ad95] tnum">{{ pkg.subscriptions_count || 0 }} Orang</span>
            </div>
          </div>
        </div>

        <!-- Card Footer Actions -->
        <div v-if="canManage" class="pt-4 mt-2 border-t border-[#e9edf4] flex items-center justify-between">
          <button 
            @click="openEditModal(pkg)"
            class="text-xs font-bold text-[#5242e2] hover:bg-[#5242e2]/10 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1"
          >
            <span class="material-symbols-outlined text-[16px]">edit</span>
            <span>Ubah</span>
          </button>

          <button 
            @click="deletePackage(pkg)"
            class="text-xs font-bold text-[#ba1a1a] hover:bg-[#ba1a1a]/10 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1"
          >
            <span class="material-symbols-outlined text-[16px]">delete</span>
            <span>Hapus</span>
          </button>
        </div>
      </div>
    </section>

    <!-- ==================== MODAL CREATE/EDIT PACKAGE ==================== -->
    <div 
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-[#e9edf4] space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-[#e9edf4]">
          <h3 class="text-base font-bold text-[#121c2c]">
            {{ isEditing ? 'Edit Paket Langganan' : 'Buat Paket Langganan Baru' }}
          </h3>
          <button @click="showModal = false" class="text-[#777587] hover:text-[#121c2c]">
            <span class="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        <form @submit.prevent="submitPackageForm" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-[#474555] mb-1">Nama Paket *</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="Contoh: Paket 3 Bulan Fitness + 10 Sesi PT"
              class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] outline-none"
              required
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-[#474555] mb-1">Tipe Paket *</label>
              <select 
                v-model="form.type" 
                class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-white border border-[#c8c4d8]/40 focus:border-[#6c5ffc] outline-none"
              >
                <option value="gym">Gym Reguler</option>
                <option value="pt">Personal Trainer</option>
                <option value="combo">Combo (Gym + PT)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-[#474555] mb-1">Durasi (Hari) *</label>
              <input 
                v-model="form.duration_days" 
                type="number" 
                min="1"
                class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] outline-none"
                required
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-[#474555] mb-1">Jumlah Sesi PT</label>
              <input 
                v-model="form.session_count" 
                type="number" 
                min="0"
                placeholder="0 jika gym reguler"
                class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] outline-none"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-[#474555] mb-1">Harga (Rp) *</label>
              <input 
                v-model="form.price" 
                type="number" 
                min="0"
                class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] outline-none"
                required
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-[#474555] mb-1">Deskripsi & Fasilitas</label>
            <textarea 
              v-model="form.description" 
              rows="2" 
              placeholder="Rincian fasilitas dan benefit paket..."
              class="w-full p-2.5 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] outline-none"
            ></textarea>
          </div>

          <div>
            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-[#474555]">
              <input type="checkbox" v-model="form.is_active" class="rounded text-[#6c5ffc]" />
              <span>Paket Aktif & Ditampilkan untuk Pendaftaran</span>
            </label>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#e9edf4]">
            <button 
              type="button" 
              @click="showModal = false"
              class="px-4 py-2 text-xs font-semibold text-[#474555] hover:bg-gray-100 rounded-xl"
            >
              Batal
            </button>

            <button 
              type="submit" 
              :disabled="form.processing"
              class="bg-[#6c5ffc] hover:bg-[#5242e2] text-white px-5 py-2 rounded-xl text-xs font-bold transition-all shadow-md active:scale-95"
            >
              Simpan Paket
            </button>
          </div>
        </form>
      </div>
    </div>

  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';
import { route } from '@/route';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Hanya user dengan permission "manage_packages" (admin & supervisor) yang melihat tombol tambah/ubah/hapus.
// Server juga mengecek permission ini, jadi menyembunyikan tombol hanya untuk kenyamanan tampilan.
const page = usePage();
const canManage = computed(() => (page.props.auth?.user?.permissions || []).includes('manage_packages'));

const props = defineProps({
  packages: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const currentEditingId = ref(null);

const form = useForm({
  name: '',
  type: 'gym',
  duration_days: 30,
  session_count: 0,
  price: 350000,
  description: '',
  is_active: true,
});

const openCreateModal = () => {
  isEditing.value = false;
  currentEditingId.value = null;
  form.reset();
  form.is_active = true;
  showModal.value = true;
};

const openEditModal = (pkg) => {
  isEditing.value = true;
  currentEditingId.value = pkg.id;
  form.name = pkg.name;
  form.type = pkg.type;
  form.duration_days = pkg.duration_days;
  form.session_count = pkg.session_count;
  form.price = pkg.price;
  form.description = pkg.description || '';
  form.is_active = Boolean(pkg.is_active);
  showModal.value = true;
};

const submitPackageForm = () => {
  if (isEditing.value) {
    form.put(route('packages.update', currentEditingId.value), {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    form.post(route('packages.store'), {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  }
};

const deletePackage = (pkg) => {
  if (confirm(`Hapus paket ${pkg.name}? Jika paket pernah dipakai member, paket hanya akan dinonaktifkan agar riwayat langganan tetap tersimpan.`)) {
    router.delete(route('packages.destroy', pkg.id));
  }
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
</script>
