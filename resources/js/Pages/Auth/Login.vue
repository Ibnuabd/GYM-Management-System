<template>
  <div class="min-h-screen bg-[#f0f3fb] flex flex-col justify-center items-center p-4 antialiased font-sans relative overflow-hidden">
    
    <!-- Background subtle ambient glow circles -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#6c5ffc]/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-[#09ad95]/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Login Container Card -->
    <div class="w-full max-w-md bg-white rounded-2xl p-8 border border-[#e9edf4] shadow-[0px_10px_30px_-5px_rgba(108,95,252,0.08),0px_4px_12px_-2px_rgba(22,28,45,0.05)] relative z-10">
      
      <!-- Logo & Brand Header -->
      <div class="text-center mb-7">
        <div class="inline-flex w-12 h-12 rounded-2xl bg-gradient-to-br from-[#6c5ffc] to-[#5242e2] items-center justify-center text-white shadow-lg shadow-[#6c5ffc]/25 mb-3">
          <span class="material-symbols-outlined text-[28px]">fitness_center</span>
        </div>
        <h1 class="text-2xl font-extrabold text-[#121c2c] tracking-tight">FitCore GMS</h1>
        <p class="text-xs text-[#777587] mt-1">Sistem Manajemen Gym Terintegrasi</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="submit" class="space-y-4">
        
        <!-- Error Alert -->
        <div v-if="form.errors.email" class="p-3 bg-[#ffdad6]/60 border border-[#ba1a1a]/20 text-[#ba1a1a] rounded-xl text-xs font-medium flex items-center gap-2">
          <span class="material-symbols-outlined text-[18px]">error</span>
          <span>{{ form.errors.email }}</span>
        </div>

        <!-- Email Field -->
        <div>
          <label class="block text-xs font-bold text-[#474555] uppercase tracking-wider mb-1.5" for="email">
            Alamat Email
          </label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[#777587]">mail</span>
            <input 
              id="email"
              v-model="form.email" 
              type="email" 
              required 
              autofocus
              placeholder="nama@fitcore.com"
              class="w-full h-11 pl-10 pr-4 text-sm rounded-xl bg-white border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 transition-all text-[#121c2c] placeholder:text-[#777587] outline-none"
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
              class="w-full h-11 pl-10 pr-10 text-sm rounded-xl bg-white border border-[#c8c4d8]/60 focus:border-[#6c5ffc] focus:ring-2 focus:ring-[#6c5ffc]/20 transition-all text-[#121c2c] placeholder:text-[#777587] outline-none"
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
              class="w-4 h-4 rounded text-[#6c5ffc] border-[#c8c4d8] focus:ring-[#6c5ffc]"
            />
            <span>Ingat saya di perangkat ini</span>
          </label>
        </div>

        <!-- Submit Button -->
        <button 
          type="submit" 
          :disabled="form.processing"
          class="w-full h-11 rounded-xl bg-[#6c5ffc] hover:bg-[#5242e2] text-white font-bold text-sm tracking-wide transition-all duration-150 shadow-md shadow-[#6c5ffc]/25 active:scale-[0.98] flex items-center justify-center gap-2 disabled:opacity-70 mt-2"
        >
          <span v-if="form.processing" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <span class="material-symbols-outlined text-[18px]" v-else>login</span>
          <span>Masuk ke Dashboard</span>
        </button>
      </form>

      <!-- Demo Accounts One-Click Quick Fill -->
      <div class="mt-7 pt-5 border-t border-[#e9edf4]">
        <p class="text-[11px] font-bold text-[#777587] uppercase tracking-wider mb-2.5 text-center">
          Pilih Akun Demo untuk Pengujian RBAC:
        </p>
        <div class="grid grid-cols-2 gap-2">
          <button 
            type="button"
            @click="fillDemo('admin@fitcore.com')" 
            class="px-2.5 py-1.5 rounded-lg border border-[#6c5ffc]/30 hover:bg-[#6c5ffc]/10 text-left transition-colors"
          >
            <p class="text-[11px] font-bold text-[#6c5ffc]">Super Admin</p>
            <p class="text-[9px] text-[#777587] truncate">admin@fitcore.com</p>
          </button>

          <button 
            type="button"
            @click="fillDemo('supervisor@fitcore.com')" 
            class="px-2.5 py-1.5 rounded-lg border border-[#09ad95]/30 hover:bg-[#09ad95]/10 text-left transition-colors"
          >
            <p class="text-[11px] font-bold text-[#09ad95]">Supervisor</p>
            <p class="text-[9px] text-[#777587] truncate">supervisor@fitcore.com</p>
          </button>

          <button 
            type="button"
            @click="fillDemo('cs@fitcore.com')" 
            class="px-2.5 py-1.5 rounded-lg border border-[#05c3fb]/30 hover:bg-[#05c3fb]/10 text-left transition-colors"
          >
            <p class="text-[11px] font-bold text-[#05c3fb]">Frontliner (CS)</p>
            <p class="text-[9px] text-[#777587] truncate">cs@fitcore.com</p>
          </button>

          <button 
            type="button"
            @click="fillDemo('pt@fitcore.com')" 
            class="px-2.5 py-1.5 rounded-lg border border-[#f7b924]/40 hover:bg-[#f7b924]/10 text-left transition-colors"
          >
            <p class="text-[11px] font-bold text-[#b58004]">Personal Trainer</p>
            <p class="text-[9px] text-[#777587] truncate">pt@fitcore.com</p>
          </button>
        </div>
      </div>

    </div>

    <!-- Security Footnote -->
    <p class="text-center text-[11px] text-[#777587] mt-5">
      Dilindungi enkripsi sesi & otorisasi Role-Based Access Control (RBAC).
    </p>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const showPassword = ref(false);

const form = useForm({
  email: 'admin@fitcore.com',
  password: 'password',
  remember: true,
});

const fillDemo = (email) => {
  form.email = email;
  form.password = 'password';
};

const submit = () => {
  form.post('/login');
};
</script>
