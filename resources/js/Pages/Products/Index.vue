<template>
  <AuthenticatedLayout>
    <div class="space-y-5">
      
      <!-- ==================== HEADER BAR ==================== -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-[#e9edf4] shadow-sm">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-lg font-bold text-[#121c2c]">Katalog Produk & Manajemen Stok</h1>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#6c5ffc]/10 text-[#5242e2]">
              POS Ritel
            </span>
          </div>
          <p class="text-xs text-[#777587]">
            Kelola data produk suplemen, minuman, merchandise, harga modal (HPP), harga jual, dan batas stok menipis.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <Link 
            :href="route('pos')"
            class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-[#474555] font-bold text-xs transition-colors"
          >
            <span class="material-symbols-outlined text-[16px]">point_of_sale</span>
            <span>Buka Kasir POS</span>
          </Link>

          <button 
            v-if="canManage"
            @click="openCreateModal"
            class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#5242e2] hover:bg-[#4335ba] text-white font-bold text-xs shadow-md shadow-[#5242e2]/25 transition-all"
          >
            <span class="material-symbols-outlined text-[16px]">add</span>
            <span>Tambah Produk</span>
          </button>
        </div>
      </div>

      <!-- ==================== FILTERS & SEARCH BAR ==================== -->
      <div class="bg-white p-4 rounded-2xl border border-[#e9edf4] shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        
        <div class="flex flex-1 items-center gap-2 w-full sm:w-auto">
          <!-- Search input -->
          <div class="relative flex-1 sm:max-w-xs">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input 
              v-model="searchQuery"
              @keydown.enter="applyFilters"
              type="text" 
              placeholder="Cari barcode / nama produk..."
              class="w-full h-10 pl-9 pr-3 rounded-xl border border-[#c8c4d8]/40 text-xs text-[#121c2c] bg-[#f8fafc] focus:bg-white focus:border-[#5242e2] outline-none"
            />
          </div>

          <!-- Category filter -->
          <select 
            v-model="selectedCategory"
            @change="applyFilters"
            class="h-10 px-3 rounded-xl border border-[#c8c4d8]/40 text-xs text-[#121c2c] bg-[#f8fafc] focus:bg-white outline-none"
          >
            <option value="">Semua Kategori</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>

          <!-- Low stock checkbox toggle -->
          <label class="flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer ml-1">
            <input 
              type="checkbox" 
              v-model="onlyLowStock"
              @change="applyFilters"
              class="rounded text-[#5242e2] focus:ring-[#5242e2]"
            />
            <span>Stok Menipis Saja</span>
          </label>
        </div>

        <button 
          @click="applyFilters"
          class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 transition-colors"
        >
          Terapkan Filter
        </button>

      </div>

      <!-- ==================== PRODUCTS TABLE ==================== -->
      <div class="bg-white rounded-2xl border border-[#e9edf4] shadow-sm overflow-hidden">
        
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-[#f8fafc] text-[#777587] text-[10px] uppercase font-bold tracking-wider">
                <th class="py-3 px-4">Barcode</th>
                <th class="py-3 px-4">Nama Produk & Kategori</th>
                <th class="py-3 px-4">Harga Modal (HPP)</th>
                <th class="py-3 px-4">Harga Jual</th>
                <th class="py-3 px-4 text-center">Stok / Min</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f0f3f8]">
              <tr v-for="product in products.data" :key="product.id" class="hover:bg-[#fbfcfe] transition-colors">
                
                <!-- Barcode -->
                <td class="py-3.5 px-4 font-mono font-bold text-[#5242e2]">
                  {{ product.barcode }}
                </td>

                <!-- Name & Category -->
                <td class="py-3.5 px-4">
                  <p class="font-bold text-[#121c2c] text-xs leading-tight">{{ product.name }}</p>
                  <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 text-slate-600 mt-1">
                    {{ product.category }}
                  </span>
                </td>

                <!-- Cost Price -->
                <td class="py-3.5 px-4 text-slate-600">
                  {{ formatRupiah(product.cost_price) }}
                </td>

                <!-- Selling Price -->
                <td class="py-3.5 px-4 font-bold text-[#121c2c]">
                  {{ formatRupiah(product.selling_price) }}
                </td>

                <!-- Stock / Min -->
                <td class="py-3.5 px-4 text-center">
                  <span 
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="product.stock <= 0 ? 'bg-red-100 text-red-700' : product.stock <= product.min_stock ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700'"
                  >
                    <span v-if="product.stock <= product.min_stock" class="material-symbols-outlined text-[13px]">warning</span>
                    {{ product.stock }} / Min {{ product.min_stock }}
                  </span>
                </td>

                <!-- Status -->
                <td class="py-3.5 px-4 text-center">
                  <span 
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="product.is_active ? 'bg-[#09ad95]/10 text-[#09ad95]' : 'bg-slate-100 text-slate-500'"
                  >
                    {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-4 text-right space-x-1">
                  <span v-if="!canManage" class="text-[10px] text-slate-400">Hanya lihat</span>
                  <button 
                    v-if="canManage"
                    @click="openEditModal(product)"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-[#5242e2] hover:bg-[#e8eeff] transition-colors"
                    title="Ubah Produk"
                  >
                    <span class="material-symbols-outlined text-[16px]">edit</span>
                  </button>

                  <button 
                    v-if="canManage"
                    @click="deleteProduct(product)"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                    title="Hapus / Nonaktifkan"
                  >
                    <span class="material-symbols-outlined text-[16px]">delete</span>
                  </button>
                </td>

              </tr>

              <tr v-if="products.data.length === 0">
                <td colspan="7" class="py-10 text-center text-slate-400">
                  Tidak ada produk ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="products.links && products.links.length > 3" class="p-4 border-t border-[#e9edf4] flex items-center justify-between text-xs">
          <span class="text-slate-500">
            Menampilkan {{ products.from || 0 }} - {{ products.to || 0 }} dari {{ products.total }} produk
          </span>
          <div class="flex items-center gap-1">
            <template v-for="(link, i) in products.links" :key="i">
              <Link 
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                class="px-2.5 py-1 rounded-lg border text-xs transition-colors"
                :class="link.active ? 'bg-[#5242e2] text-white border-[#5242e2] font-bold' : 'border-slate-200 text-slate-700 hover:bg-slate-100'"
              />
              <span v-else v-html="link.label" class="px-2 py-1 text-slate-400"></span>
            </template>
          </div>
        </div>

      </div>

    </div>

    <!-- ==================== MODAL FORM TAMBAH / EDIT PRODUK ==================== -->
    <div 
      v-if="showModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="showModal = false"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-[#e9edf4] animate-scaleIn">
        
        <div class="p-5 border-b border-[#e9edf4] flex items-center justify-between bg-slate-50">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[#5242e2] text-[20px]">{{ isEditing ? 'edit' : 'add_box' }}</span>
            <h3 class="text-sm font-bold text-[#121c2c]">
              {{ isEditing ? 'Ubah Data Produk' : 'Tambah Produk Baru' }}
            </h3>
          </div>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-700">
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>

        <form @submit.prevent="submitProductForm" class="p-5 space-y-3.5 text-xs">
          
          <!-- Barcode -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="font-bold text-[#121c2c]">Barcode / SKU Produk <span class="text-red-500">*</span></label>
              <button 
                type="button" 
                @click="generateRandomBarcode"
                class="text-[10px] text-[#5242e2] font-bold hover:underline"
              >
                + Buat Barcode Acak
              </button>
            </div>
            <input 
              v-model="productForm.barcode"
              type="text" 
              required
              placeholder="Contoh: 899123456789"
              class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 text-xs font-mono outline-none focus:border-[#5242e2]"
            />
          </div>

          <!-- Product Name -->
          <div>
            <label class="block font-bold text-[#121c2c] mb-1">Nama Produk <span class="text-red-500">*</span></label>
            <input 
              v-model="productForm.name"
              type="text" 
              required
              placeholder="Contoh: ON Gold Standard Whey 2 Lbs"
              class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 text-xs outline-none focus:border-[#5242e2]"
            />
          </div>

          <!-- Category & Stock Fields Row -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-[#121c2c] mb-1">Kategori <span class="text-red-500">*</span></label>
              <input 
                v-model="productForm.category"
                type="text" 
                required
                placeholder="Suplemen / Minuman..."
                class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 text-xs outline-none focus:border-[#5242e2]"
              />
            </div>
            <div>
              <label class="block font-bold text-[#121c2c] mb-1">Batas Minimal Stok</label>
              <input 
                v-model.number="productForm.min_stock"
                type="number" 
                min="0"
                required
                class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 text-xs outline-none focus:border-[#5242e2]"
              />
            </div>
          </div>

          <!-- Price & Stock Row -->
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-[#121c2c] mb-1">Harga Modal (HPP)</label>
              <input 
                v-model.number="productForm.cost_price"
                type="number" 
                min="0"
                required
                class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 text-xs outline-none focus:border-[#5242e2]"
              />
            </div>

            <div>
              <label class="block font-bold text-[#121c2c] mb-1">Harga Jual <span class="text-red-500">*</span></label>
              <input 
                v-model.number="productForm.selling_price"
                type="number" 
                min="0"
                required
                class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 text-xs font-bold text-[#121c2c] outline-none focus:border-[#5242e2]"
              />
            </div>

            <div>
              <label class="block font-bold text-[#121c2c] mb-1">Stok Saat Ini <span class="text-red-500">*</span></label>
              <input 
                v-model.number="productForm.stock"
                type="number" 
                min="0"
                required
                class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 text-xs outline-none focus:border-[#5242e2]"
              />
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block font-bold text-[#121c2c] mb-1">Keterangan Produk</label>
            <textarea 
              v-model="productForm.description"
              rows="2"
              placeholder="Deskripsi singkat produk..."
              class="w-full p-2.5 rounded-xl border border-[#c8c4d8]/50 text-xs outline-none focus:border-[#5242e2]"
            ></textarea>
          </div>

          <!-- Status toggle -->
          <div class="flex items-center gap-2 pt-1">
            <input 
              type="checkbox"
              id="is_active_toggle"
              v-model="productForm.is_active"
              class="rounded text-[#5242e2] focus:ring-[#5242e2]"
            />
            <label for="is_active_toggle" class="font-bold text-[#121c2c] cursor-pointer">
              Produk Aktif Dijual di Register Kasir
            </label>
          </div>

          <!-- Action buttons -->
          <div class="pt-3 border-t border-[#e9edf4] flex items-center justify-end gap-2">
            <button 
              type="button"
              @click="showModal = false"
              class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-100 transition-colors"
            >
              Batal
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 rounded-xl bg-[#5242e2] text-white font-bold hover:bg-[#4335ba] transition-colors shadow-md shadow-[#5242e2]/20 disabled:opacity-50"
            >
              {{ isSubmitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambahkan Produk') }}
            </button>
          </div>

        </form>

      </div>
    </div>

  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Hanya user dengan permission "manage_products" (admin & supervisor) yang melihat tombol tambah/ubah/hapus.
// Server juga mengecek permission ini, jadi menyembunyikan tombol hanya untuk kenyamanan tampilan.
const page = usePage();
const canManage = computed(() => (page.props.auth?.user?.permissions || []).includes('manage_products'));

const props = defineProps({
  products: Object,
  categories: Array,
  filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const onlyLowStock = ref(Boolean(props.filters?.low_stock));

const showModal = ref(false);
const isEditing = ref(false);
const editingProductId = ref(null);
const isSubmitting = ref(false);

const productForm = ref({
  barcode: '',
  name: '',
  category: 'Suplemen',
  cost_price: 0,
  selling_price: 0,
  stock: 10,
  min_stock: 5,
  description: '',
  is_active: true,
});

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(val) || 0);
};

const applyFilters = () => {
  router.get(route('products.index'), {
    search: searchQuery.value || undefined,
    category: selectedCategory.value || undefined,
    low_stock: onlyLowStock.value ? '1' : undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const openCreateModal = () => {
  isEditing.value = false;
  editingProductId.value = null;
  productForm.value = {
    barcode: '',
    name: '',
    category: 'Suplemen',
    cost_price: 0,
    selling_price: 0,
    stock: 10,
    min_stock: 5,
    description: '',
    is_active: true,
  };
  showModal.value = true;
};

const openEditModal = (product) => {
  isEditing.value = true;
  editingProductId.value = product.id;
  productForm.value = {
    barcode: product.barcode,
    name: product.name,
    category: product.category,
    cost_price: Number(product.cost_price),
    selling_price: Number(product.selling_price),
    stock: product.stock,
    min_stock: product.min_stock,
    description: product.description || '',
    is_active: Boolean(product.is_active),
  };
  showModal.value = true;
};

const generateRandomBarcode = () => {
  productForm.value.barcode = '899' + Math.floor(100000000 + Math.random() * 900000000);
};

const submitProductForm = () => {
  isSubmitting.value = true;

  if (isEditing.value) {
    router.put(route('products.update', editingProductId.value), productForm.value, {
      onSuccess: () => {
        showModal.value = false;
        isSubmitting.value = false;
      },
      onError: () => {
        isSubmitting.value = false;
      },
    });
  } else {
    router.post(route('products.store'), productForm.value, {
      onSuccess: () => {
        showModal.value = false;
        isSubmitting.value = false;
      },
      onError: () => {
        isSubmitting.value = false;
      },
    });
  }
};

const deleteProduct = (product) => {
  if (confirm(`Yakin ingin menghapus atau menonaktifkan produk '${product.name}'?`)) {
    router.delete(route('products.destroy', product.id));
  }
};
</script>

<style scoped>
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
</style>

