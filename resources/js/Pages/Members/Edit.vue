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
          <span class="text-[#5242e2]">Edit: {{ member.full_name }}</span>
        </div>
        <h1 class="text-2xl font-bold text-[#121c2c] tracking-tight">Edit Biodata Member</h1>
        <p class="text-xs text-[#474555]">
          Perbarui data kontak, NIK, kartu RFID, foto profil, dan status keaktifan akun member.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <Link 
          :href="route('members.show', member.id)"
          class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-[#474555] bg-white border border-[#c8c4d8]/40 hover:bg-[#f0f3fb] transition-colors"
        >
          <span class="material-symbols-outlined text-[18px]">arrow_back</span>
          <span>Batal & Kembali</span>
        </Link>
      </div>
    </section>

    <!-- ==================== FORM CONTAINER ==================== -->
    <form @submit.prevent="submitUpdate" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Photo & RFID -->
        <div class="space-y-6">
          <div class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
            <h2 class="text-sm font-bold text-[#121c2c] mb-1 flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px] text-[#6c5ffc]">image</span>
              <span>Foto Profil Member</span>
            </h2>
            <p class="text-[11px] text-[#777587] mb-4">Perbarui foto profil jika diperlukan.</p>

            <div class="flex flex-col items-center">
              <div class="relative w-36 h-36 rounded-2xl overflow-hidden border border-[#c8c4d8] bg-[#f9f9ff] flex items-center justify-center mb-4">
                <img 
                  v-if="photoPreview || member.photo_asset_url" 
                  :src="photoPreview || member.photo_asset_url" 
                  alt="Foto Member"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-2xl font-bold text-[#5242e2]">
                  {{ getInitials(member.full_name) }}
                </span>
              </div>

              <label class="cursor-pointer flex items-center gap-1.5 bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#5242e2] px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                <span class="material-symbols-outlined text-[16px]">upload_file</span>
                <span>Ganti Foto</span>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFileChange" />
              </label>

              <span v-if="form.errors.photo" class="text-[11px] text-[#ba1a1a] mt-2 block font-medium">
                {{ form.errors.photo }}
              </span>
            </div>
          </div>

          <!-- RFID Card -->
          <div class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
            <h2 class="text-sm font-bold text-[#121c2c] mb-1 flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px] text-[#09ad95]">contactless</span>
              <span>Kartu RFID</span>
            </h2>
            <div class="space-y-2 mt-3">
              <label class="block text-xs font-bold text-[#474555]">RFID UID</label>
              <input 
                v-model="form.rfid_uid" 
                type="text" 
                placeholder="Contoh: E2-80-11-01" 
                class="w-full h-10 px-3 text-xs font-mono font-bold tracking-wider uppercase rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white outline-none"
              />
              <span v-if="form.errors.rfid_uid" class="text-[11px] text-[#ba1a1a] block font-medium">
                {{ form.errors.rfid_uid }}
              </span>
            </div>
          </div>
        </div>

        <!-- Right: Profile Fields -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-xl p-6 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
            <h2 class="text-sm font-bold text-[#121c2c] mb-4 flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px] text-[#5242e2]">badge</span>
              <span>Biodata Identitas Member</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">Kode Member</label>
                <input 
                  type="text" 
                  :value="member.member_code" 
                  disabled 
                  class="w-full h-10 px-3 text-xs font-mono font-bold bg-gray-100 border border-gray-200 rounded-xl text-gray-500 cursor-not-allowed"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">Status Member</label>
                <select 
                  v-model="form.status" 
                  class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-white border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none"
                >
                  <option value="active">Aktif</option>
                  <option value="inactive">Tidak Aktif</option>
                  <option value="suspended">Ditangguhkan (Suspended)</option>
                </select>
              </div>

              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-[#474555] mb-1">Nama Lengkap *</label>
                <input 
                  v-model="form.full_name" 
                  type="text" 
                  class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white outline-none"
                  required
                />
                <span v-if="form.errors.full_name" class="text-[11px] text-[#ba1a1a] block font-medium mt-1">
                  {{ form.errors.full_name }}
                </span>
              </div>

              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">NIK (KTP)</label>
                <input 
                  v-model="form.nik" 
                  type="text" 
                  maxlength="16"
                  class="w-full h-10 px-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white outline-none"
                />
                <span v-if="form.errors.nik" class="text-[11px] text-[#ba1a1a] block font-medium mt-1">
                  {{ form.errors.nik }}
                </span>
              </div>

              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">Nomor Telepon *</label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  class="w-full h-10 px-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white outline-none"
                  required
                />
                <span v-if="form.errors.phone" class="text-[11px] text-[#ba1a1a] block font-medium mt-1">
                  {{ form.errors.phone }}
                </span>
              </div>

              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">Alamat Email</label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  class="w-full h-10 px-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white outline-none"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">Jenis Kelamin *</label>
                <select 
                  v-model="form.gender" 
                  class="w-full h-10 px-3 text-xs rounded-xl bg-white border border-[#c8c4d8]/40 focus:border-[#6c5ffc] outline-none"
                >
                  <option value="male">Laki-laki</option>
                  <option value="female">Perempuan</option>
                  <option value="other">Lainnya</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">Tanggal Lahir</label>
                <input 
                  v-model="form.birth_date" 
                  type="date" 
                  class="w-full h-10 px-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] outline-none"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-[#474555] mb-1">Alamat</label>
                <textarea 
                  v-model="form.address" 
                  rows="2" 
                  class="w-full p-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] outline-none"
                ></textarea>
              </div>
            </div>

            <div class="flex items-center justify-between pt-6 mt-6 border-t border-[#e9edf4]">
              <button 
                type="button" 
                @click="deleteMember"
                class="flex items-center gap-1.5 text-[#ba1a1a] hover:bg-[#ba1a1a]/10 px-3 py-2 rounded-xl text-xs font-bold transition-colors"
              >
                <span class="material-symbols-outlined text-[18px]">delete</span>
                <span>Hapus Member</span>
              </button>

              <div class="flex items-center gap-3">
                <Link 
                  :href="route('members.show', member.id)"
                  class="px-4 py-2 text-xs font-semibold text-[#474555] hover:bg-gray-100 rounded-xl"
                >
                  Batal
                </Link>

                <button 
                  type="submit" 
                  :disabled="form.processing"
                  class="flex items-center gap-1.5 bg-[#6c5ffc] hover:bg-[#5242e2] text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md shadow-[#6c5ffc]/25 active:scale-95"
                >
                  <span v-if="form.processing" class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                  <span v-else class="material-symbols-outlined text-[18px]">save</span>
                  <span>Simpan Perubahan</span>
                </button>
              </div>
            </div>

          </div>
        </div>

      </div>
    </form>

  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  member: Object,
});

const form = useForm({
  _method: 'PUT',
  nik: props.member.nik || '',
  full_name: props.member.full_name || '',
  phone: props.member.phone || '',
  email: props.member.email || '',
  rfid_uid: props.member.rfid_uid || '',
  gender: props.member.gender || 'male',
  birth_date: props.member.birth_date ? props.member.birth_date.split('T')[0] : '',
  address: props.member.address || '',
  status: props.member.status || 'active',
  photo: null,
});

const photoPreview = ref(null);

const handleFileChange = (e) => {
  const file = e.target.files[0];
  // Reset nilai input agar memilih file yang SAMA lagi tetap memicu event "change".
  e.target.value = '';
  if (!file) return;

  form.photo = file;
  // Hapus pesan error foto dari percobaan sebelumnya.
  form.clearErrors('photo');
  const reader = new FileReader();
  reader.onload = (event) => {
    photoPreview.value = event.target.result;
  };
  reader.readAsDataURL(file);
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

const submitUpdate = () => {
  form.post(route('members.update', props.member.id), {
    preserveScroll: true,
  });
};

const deleteMember = () => {
  if (confirm(`Hapus permanen member ${props.member.full_name}? Seluruh riwayat langganan terkait juga akan terhapus.`)) {
    router.delete(route('members.destroy', props.member.id));
  }
};
</script>
