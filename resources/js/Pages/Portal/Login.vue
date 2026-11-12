<template>
  <div class="min-h-screen bg-[#f0f3fb] flex flex-col justify-center items-center p-4 antialiased font-sans relative overflow-hidden">
    
    <!-- Ambient Background Glow Circles -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#09ad95]/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-[#6c5ffc]/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Login Container Card -->
    <div class="w-full max-w-md bg-white rounded-2xl p-8 border border-[#e9edf4] shadow-[0px_10px_30px_-5px_rgba(9,173,149,0.08),0px_4px_12px_-2px_rgba(22,28,45,0.05)] relative z-10">
      
      <!-- Brand & Portal Header -->
      <div class="text-center mb-6">
        <div class="inline-flex w-12 h-12 rounded-2xl bg-gradient-to-br from-[#09ad95] to-[#058471] items-center justify-center text-white shadow-lg shadow-[#09ad95]/25 mb-3">
          <span class="material-symbols-outlined text-[26px]">badge</span>
        </div>
        <h1 class="text-2xl font-extrabold text-[#121c2c] tracking-tight">Portal Member FitCore</h1>
        <p class="text-xs text-[#777587] mt-1">Layanan Mandiri Status Keanggotaan & Histori PT</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="submit" class="space-y-4">
        
        <!-- Error Alert -->
        <div v-if="form.errors.login" class="p-3 bg-[#ffdad6]/60 border border-[#ba1a1a]/20 text-[#ba1a1a] rounded-xl text-xs font-medium flex items-center gap-2">
          <span class="material-symbols-outlined text-[18px]">error</span>
          <span>{{ form.errors.login }}</span>
        </div>

        <!-- Login Identifier (Email / Phone / Member Code) -->
        <div>
          <label class="block text-xs font-bold text-[#474555] uppercase tracking-wider mb-1.5" for="login">
            Email / No. HP / Kode Member
          </label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[#777587]">account_circle</span>
            <input 
              id="login"
              v-model="form.login" 
              type="text" 
              required 
              autofocus
              placeholder="Contoh: aditya.pratama@gmail.com atau 081234567890"
              class="w-full h-11 pl-10 pr-4 text-sm rounded-xl bg-white border border-[#c8c4d8]/60 focus:border-[#09ad95] focus:ring-2 focus:ring-[#09ad95]/20 transition-all text-[#121c2c] placeholder:text-[#777587]/70 outline-none"
            />
          </div>
        </div>

        <!-- Password Field -->
        <div>
          <label class="block text-xs font-bold text-[#474555] uppercase tracking-wider mb-1.5" for="password">
            Kata Sandi
          </label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[#777587]">lock</span>
            <input 
              id="password"
              v-model="form.password" 
              :type="showPassword ? 'text' : 'password'" 
              required
              placeholder="••••••••"
              class="w-full h-11 pl-10 pr-10 text-sm rounded-xl bg-white border border-[#c8c4d8]/60 focus:border-[#09ad95] focus:ring-2 focus:ring-[#09ad95]/20 transition-all text-[#121c2c] placeholder:text-[#777587]/70 outline-none"
            />
            <button 
              type="button" 
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-[#777587] hover:text-[#121c2c]"
            >
              <span class="material-symbols-outlined text-[18px]">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
            </button>
          </div>
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center gap-2 text-[#474555] cursor-pointer">
            <input 
              v-model="form.remember" 
              type="checkbox" 
              class="w-4 h-4 rounded text-[#09ad95] border-[#c8c4d8] focus:ring-[#09ad95]"
            />
            <span>Ingat saya di perangkat ini</span>
          </label>
        </div>

        <!-- Submit Button -->
        <button 
          type="submit" 
          :disabled="form.processing"
          class="w-full h-11 rounded-xl bg-[#09ad95] hover:bg-[#078e7a] text-white font-bold text-sm tracking-wide transition-all duration-150 shadow-md shadow-[#09ad95]/25 active:scale-[0.98] flex items-center justify-center gap-2 disabled:opacity-70 mt-2"
        >
          <span v-if="form.processing" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <span class="material-symbols-outlined text-[18px]" v-else>login</span>
          <span>Masuk ke Portal Member</span>
        </button>
      </form>

      <!-- Quick Demo Account Fill -->
      <div class="mt-6 pt-5 border-t border-[#e9edf4]">
        <p class="text-[11px] font-bold text-[#777587] uppercase tracking-wider mb-2.5 text-center">
          Pilih Akun Member Demo:
        </p>
        <div class="grid grid-cols-2 gap-2">
          <button 
            type="button"
            @click="fillDemo('aditya.pratama@gmail.com')" 
            class="p-2 rounded-xl border border-[#09ad95]/30 hover:bg-[#09ad95]/10 text-left transition-colors"
          >
            <p class="text-[11px] font-bold text-[#09ad95]">Aditya Pratama</p>
            <p class="text-[9px] text-[#777587] truncate">aditya.pratama@gmail.com</p>
            <span class="inline-block mt-1 text-[8px] bg-[#09ad95]/15 text-[#078e7a] px-1.5 py-0.5 rounded font-semibold">Gym + PT Aktif</span>
          </button>

          <button 
            type="button"
            @click="fillDemo('clarissa.steph@gmail.com')" 
            class="p-2 rounded-xl border border-[#6c5ffc]/30 hover:bg-[#6c5ffc]/10 text-left transition-colors"
          >
            <p class="text-[11px] font-bold text-[#6c5ffc]">Clarissa Stephanie</p>
            <p class="text-[9px] text-[#777587] truncate">clarissa.steph@gmail.com</p>
            <span class="inline-block mt-1 text-[8px] bg-[#6c5ffc]/15 text-[#5242e2] px-1.5 py-0.5 rounded font-semibold">Ultimate Combo</span>
          </button>
        </div>
      </div>

      <!-- Link Back to Staff Login -->
      <div class="mt-5 pt-4 text-center border-t border-[#e9edf4]">
        <p class="text-xs text-[#777587]">
          Staf Gym, Kasir, atau Personal Trainer?
          <a href="/login" class="font-bold text-[#6c5ffc] hover:underline ml-1">
            Masuk ke Login Staf &rarr;
          </a>
        </p>
      </div>

    </div>

    <!-- Security & Read-Only Notice -->
    <p class="text-center text-[11px] text-[#777587] mt-5">
      Portal Mandiri Anggota beroperasi dalam mode baca (*read-only*) yang aman.
    </p>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const showPassword = ref(false);

const form = useForm({
  login: 'aditya.pratama@gmail.com',
  password: 'password',
  remember: true,
});

const fillDemo = (loginVal) => {
  form.login = loginVal;
  form.password = 'password';
};

const submit = () => {
  form.post('/portal/login');
};
</script>

