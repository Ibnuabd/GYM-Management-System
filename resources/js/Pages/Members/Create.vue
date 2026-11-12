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
          <span class="text-[#5242e2]">Registrasi Baru</span>
        </div>
        <h1 class="text-2xl font-bold text-[#121c2c] tracking-tight">Formulir Pendaftaran Member</h1>
        <p class="text-xs text-[#474555]">
          Registrasi data profil tunggal, integrasi kartu RFID, unggah foto profil, dan aktivasi paket perdana.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <Link 
          :href="route('members.index')"
          class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-[#474555] bg-white border border-[#c8c4d8]/40 hover:bg-[#f0f3fb] transition-colors"
        >
          <span class="material-symbols-outlined text-[18px]">arrow_back</span>
          <span>Kembali</span>
        </Link>
      </div>
    </section>

    <!-- ==================== FORM CONTAINER ==================== -->
    <form @submit.prevent="submitForm" class="space-y-6">
      
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT COLUMN: PHOTO & RFID CARD -->
        <div class="space-y-6">
          
          <!-- Card Foto Profil & Webcam -->
          <div class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
            <h2 class="text-sm font-bold text-[#121c2c] mb-1 flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px] text-[#6c5ffc]">add_a_photo</span>
              <span>Foto Profil Member</span>
            </h2>
            <p class="text-[11px] text-[#777587] mb-4">
              Digunakan untuk verifikasi visual saat check-in gerbang RFID.
            </p>

            <!-- Photo Preview Area -->
            <div class="flex flex-col items-center">
              <div class="relative w-36 h-36 rounded-2xl overflow-hidden border-2 border-dashed border-[#c8c4d8] bg-[#f9f9ff] flex items-center justify-center group mb-4">
                <img 
                  v-if="photoPreview" 
                  :src="photoPreview" 
                  alt="Preview Foto" 
                  class="w-full h-full object-cover"
                />

                <video 
                  v-else-if="isCameraOpen" 
                  ref="videoElement" 
                  autoplay 
                  playsinline 
                  class="w-full h-full object-cover"
                ></video>

                <div v-else class="text-center p-3 text-[#777587]">
                  <span class="material-symbols-outlined text-[42px] text-[#c8c4d8]">account_circle</span>
                  <span class="block text-[10px] font-semibold mt-1">Belum ada foto</span>
                </div>

                <canvas ref="canvasElement" class="hidden" width="400" height="400"></canvas>
              </div>

              <!-- Action Controls for Photo -->
              <div class="flex flex-wrap items-center justify-center gap-2 w-full">
                <label class="cursor-pointer flex items-center gap-1.5 bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#5242e2] px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                  <span class="material-symbols-outlined text-[16px]">upload_file</span>
                  <span>Pilih File</span>
                  <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFileChange" />
                </label>

                <button 
                  v-if="!isCameraOpen"
                  type="button" 
                  @click="openWebcam" 
                  class="flex items-center gap-1.5 bg-[#09ad95]/10 hover:bg-[#09ad95]/20 text-[#09ad95] px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                >
                  <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                  <span>Buka Kamera</span>
                </button>

                <button 
                  v-else 
                  type="button" 
                  @click="captureSnapshot" 
                  class="flex items-center gap-1.5 bg-[#09ad95] text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm active:scale-95"
                >
                  <span class="material-symbols-outlined text-[16px]">camera</span>
                  <span>Ambil Foto</span>
                </button>

                <button 
                  v-if="photoPreview" 
                  type="button" 
                  @click="clearPhoto" 
                  class="flex items-center gap-1 text-[#ba1a1a] hover:bg-[#ba1a1a]/10 px-2 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                >
                  <span class="material-symbols-outlined text-[16px]">delete</span>
                </button>
              </div>

              <span v-if="form.errors.photo" class="text-[11px] text-[#ba1a1a] mt-2 block font-medium">
                {{ form.errors.photo }}
              </span>
              <!-- Error untuk foto webcam (base64), dari aturan validasi App\Rules\Base64Image -->
              <span v-if="form.errors.photo_data" class="text-[11px] text-[#ba1a1a] mt-2 block font-medium">
                {{ form.errors.photo_data }}
              </span>
            </div>
          </div>

          <!-- Card RFID Card Assignment -->
          <div class="bg-white rounded-xl p-5 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
            <h2 class="text-sm font-bold text-[#121c2c] mb-1 flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px] text-[#09ad95]">contactless</span>
              <span>Pautkan Kartu RFID</span>
            </h2>
            <p class="text-[11px] text-[#777587] mb-3">
              Tempelkan kartu RFID pada alat pembaca USB atau ketik manual UID kartu.
            </p>

            <div class="space-y-2">
              <label class="block text-xs font-bold text-[#474555]">
                RFID Card UID <span class="text-[#777587] font-normal">(Opsional)</span>
              </label>
              
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[#777587]">badge</span>
                <input 
                  v-model="form.rfid_uid" 
                  @input="checkRfidUniqueness"
                  type="text" 
                  placeholder="Contoh: E2-80-11-01" 
                  class="w-full h-10 pl-9 pr-10 text-xs font-mono font-bold tracking-wider uppercase rounded-xl bg-[#f0f3fb]/60 border transition-all outline-none"
                  :class="rfidStatusClass"
                />
                
                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center">
                  <span v-if="isCheckingRfid" class="w-4 h-4 rounded-full border-2 border-[#6c5ffc] border-t-transparent animate-spin"></span>
                  <span v-else-if="rfidCheckResult && rfidCheckResult.unique" class="material-symbols-outlined text-[#09ad95] text-[18px]">check_circle</span>
                  <span v-else-if="rfidCheckResult && !rfidCheckResult.unique" class="material-symbols-outlined text-[#ba1a1a] text-[18px]">error</span>
                </div>
              </div>

              <div v-if="rfidCheckResult" class="text-[11px] font-semibold mt-1" :class="rfidCheckResult.unique ? 'text-[#09ad95]' : 'text-[#ba1a1a]'">
                {{ rfidCheckResult.message }}
              </div>

              <span v-if="form.errors.rfid_uid" class="text-[11px] text-[#ba1a1a] block font-medium">
                {{ form.errors.rfid_uid }}
              </span>

              <div class="mt-2 p-2.5 rounded-lg bg-[#e8eeff]/60 border border-[#c8c4d8]/40 text-[11px] text-[#474555] flex items-start gap-2">
                <span class="material-symbols-outlined text-[16px] text-[#5242e2] shrink-0 mt-0.5">sensors</span>
                <span>Mode pendeteksi RFID aktif: Cukup tap kartu fisik ke scanner, kode otomatis terisi.</span>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT COLUMN: PROFILE DETAILS & INITIAL PACKAGE -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Card Biodata Member -->
          <div class="bg-white rounded-xl p-6 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
            <h2 class="text-sm font-bold text-[#121c2c] mb-1 flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px] text-[#5242e2]">person</span>
              <span>Informasi Profil Utama</span>
            </h2>
            <p class="text-[11px] text-[#777587] mb-5">
              Data identitas resmi member untuk pencatatan riwayat transaksi dan audit.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              
              <!-- Kode Member (Otomatis) -->
              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">
                  Kode Member <span class="text-[10px] font-normal text-[#09ad95] bg-[#09ad95]/10 px-1.5 py-0.5 rounded">Otomatis Terbit</span>
                </label>
                <input 
                  type="text" 
                  :value="suggestedMemberCode" 
                  disabled 
                  class="w-full h-10 px-3 text-xs font-mono font-bold bg-gray-100 border border-gray-200 rounded-xl text-gray-500 cursor-not-allowed"
                />
              </div>

              <!-- NIK KTP -->
              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">
                  Nomor Induk Kependudukan (NIK) <span class="text-[#777587] font-normal">(16 Digit)</span>
                </label>
                <input 
                  v-model="form.nik" 
                  type="text" 
                  maxlength="16"
                  placeholder="Contoh: 3201123456780001" 
                  class="w-full h-10 px-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none transition-all"
                />
                <span v-if="form.errors.nik" class="text-[11px] text-[#ba1a1a] block font-medium mt-1">
                  {{ form.errors.nik }}
                </span>
              </div>

              <!-- Nama Lengkap -->
              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-[#474555] mb-1">
                  Nama Lengkap Member <span class="text-[#ba1a1a]">*</span>
                </label>
                <input 
                  v-model="form.full_name" 
                  type="text" 
                  placeholder="Ketik nama lengkap sesuai identitas..." 
                  class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none transition-all"
                  required
                />
                <span v-if="form.errors.full_name" class="text-[11px] text-[#ba1a1a] block font-medium mt-1">
                  {{ form.errors.full_name }}
                </span>
              </div>

              <!-- Nomor HP / WhatsApp -->
              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">
                  Nomor Telepon / WhatsApp <span class="text-[#ba1a1a]">*</span>
                </label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  placeholder="Contoh: 081234567890" 
                  class="w-full h-10 px-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none transition-all"
                  required
                />
                <span v-if="form.errors.phone" class="text-[11px] text-[#ba1a1a] block font-medium mt-1">
                  {{ form.errors.phone }}
                </span>
              </div>

              <!-- Email -->
              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">
                  Alamat Email <span class="text-[#777587] font-normal">(Opsional)</span>
                </label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  placeholder="member@example.com" 
                  class="w-full h-10 px-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none transition-all"
                />
                <span v-if="form.errors.email" class="text-[11px] text-[#ba1a1a] block font-medium mt-1">
                  {{ form.errors.email }}
                </span>
              </div>

              <!-- Jenis Kelamin -->
              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">
                  Jenis Kelamin <span class="text-[#ba1a1a]">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                  <label 
                    class="flex items-center justify-center gap-2 h-10 rounded-xl border cursor-pointer text-xs font-semibold transition-all"
                    :class="form.gender === 'male' ? 'bg-[#6c5ffc]/10 border-[#6c5ffc] text-[#6c5ffc]' : 'bg-[#f0f3fb]/60 border-[#c8c4d8]/40 text-[#474555]'"
                  >
                    <input type="radio" value="male" v-model="form.gender" class="hidden" />
                    <span class="material-symbols-outlined text-[16px]">male</span>
                    <span>Laki-laki</span>
                  </label>

                  <label 
                    class="flex items-center justify-center gap-2 h-10 rounded-xl border cursor-pointer text-xs font-semibold transition-all"
                    :class="form.gender === 'female' ? 'bg-[#6c5ffc]/10 border-[#6c5ffc] text-[#6c5ffc]' : 'bg-[#f0f3fb]/60 border-[#c8c4d8]/40 text-[#474555]'"
                  >
                    <input type="radio" value="female" v-model="form.gender" class="hidden" />
                    <span class="material-symbols-outlined text-[16px]">female</span>
                    <span>Perempuan</span>
                  </label>
                </div>
              </div>

              <!-- Tanggal Lahir -->
              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">
                  Tanggal Lahir <span class="text-[#777587] font-normal">(Opsional)</span>
                </label>
                <input 
                  v-model="form.birth_date" 
                  type="date" 
                  class="w-full h-10 px-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none transition-all"
                />
              </div>

              <!-- Alamat Tinggal -->
              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-[#474555] mb-1">
                  Alamat Domisili <span class="text-[#777587] font-normal">(Opsional)</span>
                </label>
                <textarea 
                  v-model="form.address" 
                  rows="2" 
                  placeholder="Ketik alamat lengkap..."
                  class="w-full p-3 text-xs rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none transition-all"
                ></textarea>
              </div>

            </div>
          </div>

          <!-- Card Langganan Perdana -->
          <div class="bg-white rounded-xl p-6 border border-[#e9edf4] shadow-[0px_4px_16px_-2px_rgba(108,95,252,0.04)]">
            <div class="flex items-center justify-between mb-1">
              <h2 class="text-sm font-bold text-[#121c2c] flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-[#05c3fb]">card_membership</span>
                <span>Aktivasi Paket Perdana</span>
              </h2>
              <span class="text-[10px] font-bold text-[#6c5ffc] bg-[#6c5ffc]/10 px-2 py-0.5 rounded-full">
                Multi-Subscription
              </span>
            </div>
            <p class="text-[11px] text-[#777587] mb-4">
              Pilih paket gym atau PT langsung saat pendaftaran. Sistem akan menghitung otomatis masa aktif dan kuota sesi.
            </p>

            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-[#474555] mb-1">
                  Pilih Paket Keanggotaan
                </label>
                <select 
                  v-model="form.package_id" 
                  class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-white border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none cursor-pointer"
                >
                  <option :value="null">-- Belum Memilih Paket (Daftar Profil Saja) --</option>
                  <option 
                    v-for="pkg in packages" 
                    :key="pkg.id" 
                    :value="pkg.id"
                  >
                    {{ pkg.name }} ({{ pkg.duration_days }} Hari) - Rp {{ Number(pkg.price).toLocaleString('id-ID') }}
                  </option>
                </select>
              </div>

              <!-- Dynamic Package Preview Details -->
              <div v-if="selectedPackage" class="p-4 rounded-xl bg-[#e8eeff]/50 border border-[#c8c4d8]/40 space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-[#121c2c]">{{ selectedPackage.name }}</span>
                  <span class="text-xs font-bold text-[#5242e2] tnum">
                    Rp {{ Number(selectedPackage.price).toLocaleString('id-ID') }}
                  </span>
                </div>
                <p class="text-[11px] text-[#474555]">{{ selectedPackage.description }}</p>

                <!-- Calculations -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-[#c8c4d8]/30 text-xs">
                  <div>
                    <span class="text-[10px] text-[#777587] block">Mulai Berlaku</span>
                    <span class="font-bold text-[#121c2c]">{{ calculatedStartDate }}</span>
                  </div>
                  <div>
                    <span class="text-[10px] text-[#777587] block">Berakhir Pada</span>
                    <span class="font-bold text-[#09ad95]">{{ calculatedEndDate }}</span>
                  </div>
                  <div>
                    <span class="text-[10px] text-[#777587] block">Alokasi Sesi PT</span>
                    <span class="font-bold text-[#b58004]">
                      {{ selectedPackage.session_count > 0 ? selectedPackage.session_count + ' Sesi' : 'Hanya Akses Gym' }}
                    </span>
                  </div>
                </div>

                <!-- Personal Trainer Selector -->
                <div v-if="selectedPackage.type === 'pt' || selectedPackage.type === 'combo'" class="pt-2">
                  <label class="block text-xs font-bold text-[#474555] mb-1">
                    Pilih Instruktur Personal Trainer (PT) <span class="text-[#ba1a1a]">*</span>
                  </label>
                  <select 
                    v-model="form.pt_user_id" 
                    class="w-full h-10 px-3 text-xs font-semibold rounded-xl bg-white border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 outline-none cursor-pointer"
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
                </div>
              </div>

            </div>
          </div>

          <!-- Action Submission Bar -->
          <div class="flex items-center justify-end gap-3 pt-2">
            <Link 
              :href="route('members.index')"
              class="px-5 py-2.5 rounded-xl text-xs font-semibold text-[#474555] bg-white border border-[#c8c4d8]/40 hover:bg-[#f0f3fb] transition-colors"
            >
              Batalkan
            </Link>

            <button 
              type="submit" 
              :disabled="form.processing || (rfidCheckResult && !rfidCheckResult.unique)"
              class="flex items-center gap-2 bg-[#6c5ffc] hover:bg-[#5242e2] disabled:opacity-50 text-white px-6 py-2.5 rounded-xl text-xs font-bold transition-all shadow-md shadow-[#6c5ffc]/25 active:scale-95"
            >
              <span v-if="form.processing" class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
              <span v-else class="material-symbols-outlined text-[18px]">how_to_reg</span>
              <span>Daftarkan Member Sekarang</span>
            </button>
          </div>

        </div>

      </div>

    </form>

  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  packages: Array,
  personalTrainers: Array,
  suggestedMemberCode: String,
});

const form = useForm({
  nik: '',
  full_name: '',
  phone: '',
  email: '',
  rfid_uid: '',
  gender: 'male',
  birth_date: '',
  address: '',
  photo: null,
  photo_data: null,
  package_id: null,
  pt_user_id: null,
  start_date: new Date().toISOString().split('T')[0],
  notes: '',
});

// Photo & Webcam State
const photoPreview = ref(null);
const isCameraOpen = ref(false);
const videoElement = ref(null);
const canvasElement = ref(null);
let mediaStream = null;

// Hapus pesan error foto lama setiap kali user mengganti/menghapus foto,
// supaya error dari percobaan sebelumnya tidak tetap tampil.
const clearPhotoErrors = () => {
  form.clearErrors('photo', 'photo_data');
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  // Reset nilai input agar memilih file yang SAMA lagi tetap memicu event "change".
  e.target.value = '';
  if (!file) return;

  form.photo = file;
  form.photo_data = null;
  clearPhotoErrors();
  stopWebcam();

  const reader = new FileReader();
  reader.onload = (event) => {
    photoPreview.value = event.target.result;
  };
  reader.readAsDataURL(file);
};

const openWebcam = async () => {
  try {
    isCameraOpen.value = true;
    photoPreview.value = null;
    form.photo = null;

    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: { width: 400, height: 400 },
      audio: false,
    });

    if (videoElement.value) {
      videoElement.value.srcObject = mediaStream;
    }
  } catch (err) {
    alert('Kamera tidak dapat diakses atau izin ditolak: ' + err.message);
    isCameraOpen.value = false;
  }
};

const captureSnapshot = () => {
  if (!videoElement.value || !canvasElement.value) return;

  const context = canvasElement.value.getContext('2d');
  context.drawImage(videoElement.value, 0, 0, 400, 400);

  const dataUrl = canvasElement.value.toDataURL('image/jpeg', 0.85);
  photoPreview.value = dataUrl;
  form.photo_data = dataUrl;
  form.photo = null;
  clearPhotoErrors();

  stopWebcam();
};

const stopWebcam = () => {
  if (mediaStream) {
    mediaStream.getTracks().forEach((track) => track.stop());
    mediaStream = null;
  }
  isCameraOpen.value = false;
};

const clearPhoto = () => {
  stopWebcam();
  photoPreview.value = null;
  form.photo = null;
  form.photo_data = null;
  clearPhotoErrors();
};

onUnmounted(() => {
  stopWebcam();
});

// RFID Validation State
const isCheckingRfid = ref(false);
const rfidCheckResult = ref(null);
let rfidDebounceTimer = null;

const checkRfidUniqueness = () => {
  const rfid = form.rfid_uid ? form.rfid_uid.trim() : '';
  if (!rfid) {
    rfidCheckResult.value = null;
    return;
  }

  clearTimeout(rfidDebounceTimer);
  rfidDebounceTimer = setTimeout(async () => {
    isCheckingRfid.value = true;
    try {
      const response = await fetch(`${route('members.check-rfid')}?rfid=${encodeURIComponent(rfid)}`);
      const data = await response.json();
      rfidCheckResult.value = data;
    } catch (err) {
      console.error('Gagal memeriksa RFID:', err);
    } finally {
      isCheckingRfid.value = false;
    }
  }, 250);
};

const rfidStatusClass = computed(() => {
  if (!rfidCheckResult.value) return 'border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white';
  return rfidCheckResult.value.unique
    ? 'border-[#09ad95] bg-[#09ad95]/5 text-[#006b5b]'
    : 'border-[#ba1a1a] bg-[#ba1a1a]/5 text-[#ba1a1a]';
});

// Package Selection Computations
const selectedPackage = computed(() => {
  if (!form.package_id) return null;
  return props.packages.find((p) => p.id === form.package_id) || null;
});

const calculatedStartDate = computed(() => {
  const date = form.start_date ? new Date(form.start_date) : new Date();
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
});

const calculatedEndDate = computed(() => {
  if (!selectedPackage.value) return '-';
  const start = form.start_date ? new Date(form.start_date) : new Date();
  const end = new Date(start);
  end.setDate(end.getDate() + selectedPackage.value.duration_days - 1);
  return end.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
});

const submitForm = () => {
  form.post(route('members.store'), {
    preserveScroll: true,
  });
};
</script>
