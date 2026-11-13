<template>
  <AuthenticatedLayout>
    <div class="space-y-5">
      
      <!-- ==================== HEADER BAR ==================== -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-5 rounded-2xl border border-[#e9edf4] shadow-sm">
        <div>
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#6c5ffc] to-[#05c3fb] flex items-center justify-center text-white shadow-md shadow-[#6c5ffc]/20">
              <span class="material-symbols-outlined text-[24px]">point_of_sale</span>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h1 class="text-lg font-bold text-[#121c2c]">Kasir POS & Ritel Kebugaran</h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#09ad95]/10 text-[#09ad95] border border-[#09ad95]/20 flex items-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#09ad95] animate-ping"></span>
                  Register Aktif
                </span>
              </div>
              <p class="text-xs text-[#777587]">
                Kasir: <strong>{{ cashier.name }}</strong> • {{ formattedTodayDate }}
              </p>
            </div>
          </div>
        </div>

        <!-- Mode Toggle & Quick Metrics -->
        <div class="flex items-center gap-2">
          <!-- View Switcher -->
          <div class="bg-[#f0f3fb] p-1 rounded-xl flex items-center border border-[#c8c4d8]/30">
            <button 
              @click="activeView = 'register'"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
              :class="activeView === 'register' ? 'bg-white text-[#5242e2] shadow-sm' : 'text-[#777587] hover:text-[#121c2c]'"
            >
              <span class="material-symbols-outlined text-[16px]">shopping_cart</span>
              <span>Register Kasir</span>
            </button>
            <button 
              @click="activeView = 'history'"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
              :class="activeView === 'history' ? 'bg-white text-[#5242e2] shadow-sm' : 'text-[#777587] hover:text-[#121c2c]'"
            >
              <span class="material-symbols-outlined text-[16px]">receipt_long</span>
              <span>Riwayat & Void</span>
              <span v-if="stats.today_transactions_count > 0" class="px-1.5 py-0.2 rounded-full text-[9px] bg-[#6c5ffc]/15 text-[#6c5ffc] font-mono">
                {{ stats.today_transactions_count }}
              </span>
            </button>
          </div>

          <Link 
            :href="route('products.index')"
            class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-[#c8c4d8]/40 text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2] font-bold text-xs transition-colors"
            title="Kelola Master Produk & Stok"
          >
            <span class="material-symbols-outlined text-[16px]">inventory_2</span>
            <span>Katalog Produk</span>
          </Link>
        </div>
      </div>

      <!-- ==================== VIEW 1: POS REGISTER ==================== -->
      <div v-show="activeView === 'register'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: PRODUCT SEARCH, CATEGORIES & CATALOG (7 COLS) -->
        <div class="lg:col-span-7 space-y-4">
          
          <!-- Barcode Scanner Input & Keyword Search Bar -->
          <div class="bg-white p-4 rounded-2xl border border-[#e9edf4] shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row gap-2">
              
              <!-- Rapid Barcode Scanner Input -->
              <div class="relative flex-1">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[#6c5ffc]">barcode_scanner</span>
                <input 
                  ref="barcodeInputRef"
                  v-model="barcodeScanQuery"
                  @keydown.enter.prevent="handleBarcodeScan"
                  type="text" 
                  placeholder="Scan barcode USB scanner / ketik lalu Enter..."
                  class="w-full h-11 pl-9 pr-24 rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white focus:ring-2 focus:ring-[#6c5ffc]/20 text-xs font-mono text-[#121c2c] placeholder:text-[#777587] outline-none transition-all"
                />
                <button 
                  @click="handleBarcodeScan"
                  class="absolute right-1.5 top-1/2 -translate-y-1/2 px-2.5 py-1.5 rounded-lg bg-[#6c5ffc] hover:bg-[#5242e2] text-white text-[11px] font-bold transition-colors flex items-center gap-1"
                >
                  <span class="material-symbols-outlined text-[14px]">add</span>
                  <span>Scan</span>
                </button>
              </div>

              <!-- Keyword Search Filter -->
              <div class="relative sm:w-48">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[16px] text-[#777587]">search</span>
                <input 
                  v-model="searchKeyword"
                  type="text" 
                  placeholder="Filter nama..."
                  class="w-full h-11 pl-8 pr-3 rounded-xl bg-[#f0f3fb]/60 border border-[#c8c4d8]/40 focus:border-[#6c5ffc] focus:bg-white text-xs text-[#121c2c] outline-none"
                />
              </div>

            </div>

            <!-- Barcode Alert Feedback Message -->
            <div v-if="barcodeAlertMessage" class="p-2.5 rounded-xl text-xs font-semibold flex items-center justify-between" :class="barcodeAlertError ? 'bg-[#ffdad6]/60 text-[#ba1a1a] border border-[#ba1a1a]/20' : 'bg-[#09ad95]/10 text-[#09ad95] border border-[#09ad95]/20'">
              <div class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">{{ barcodeAlertError ? 'error' : 'check_circle' }}</span>
                <span>{{ barcodeAlertMessage }}</span>
              </div>
              <button @click="barcodeAlertMessage = ''" class="hover:opacity-75">
                <span class="material-symbols-outlined text-[14px]">close</span>
              </button>
            </div>

            <!-- Category Pills Filter -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 pt-1 scrollbar-none">
              <button 
                @click="selectedCategory = 'all'"
                class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all"
                :class="selectedCategory === 'all' ? 'bg-[#6c5ffc] text-white shadow-sm' : 'bg-[#f0f3fb] text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                Semua Produk ({{ products.length }})
              </button>
              <button 
                v-for="cat in categories" 
                :key="cat"
                @click="selectedCategory = cat"
                class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all"
                :class="selectedCategory === cat ? 'bg-[#6c5ffc] text-white shadow-sm' : 'bg-[#f0f3fb] text-[#474555] hover:bg-[#e8eeff] hover:text-[#5242e2]'"
              >
                {{ cat }}
              </button>
            </div>
          </div>

          <!-- Product Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 max-h-[620px] overflow-y-auto pr-1">
            <div 
              v-for="product in filteredProducts" 
              :key="product.id"
              @click="addToCart(product)"
              class="group relative bg-white p-3.5 rounded-2xl border border-[#e9edf4] hover:border-[#6c5ffc]/40 hover:shadow-md transition-all duration-200 cursor-pointer flex flex-col justify-between"
              :class="{ 'opacity-60 cursor-not-allowed': product.stock <= 0 }"
            >
              <div>
                <!-- Top Row: Category & Stock Badge -->
                <div class="flex items-center justify-between mb-2">
                  <span class="text-[9px] uppercase tracking-wider font-extrabold px-1.5 py-0.5 rounded bg-slate-100 text-[#5242e2]">
                    {{ product.category }}
                  </span>
                  
                  <span 
                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-md"
                    :class="product.stock <= 0 ? 'bg-red-100 text-red-600 font-extrabold' : product.stock <= product.min_stock ? 'bg-amber-100 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                  >
                    {{ product.stock <= 0 ? 'Habis' : `Stok: ${product.stock}` }}
                  </span>
                </div>

                <!-- Product Name -->
                <h4 class="text-xs font-bold text-[#121c2c] group-hover:text-[#5242e2] transition-colors line-clamp-2 leading-snug">
                  {{ product.name }}
                </h4>

                <!-- Barcode snippet -->
                <p class="text-[10px] text-[#777587] font-mono mt-1 flex items-center gap-1">
                  <span class="material-symbols-outlined text-[12px]">barcode</span>
                  {{ product.barcode }}
                </p>
              </div>

              <!-- Price & Add Button -->
              <div class="mt-3 pt-2.5 border-t border-[#f0f3f8] flex items-center justify-between">
                <span class="text-xs font-extrabold text-[#121c2c] group-hover:text-[#6c5ffc]">
                  {{ formatRupiah(product.selling_price) }}
                </span>

                <button 
                  :disabled="product.stock <= 0"
                  class="w-7 h-7 rounded-lg bg-[#e8eeff] group-hover:bg-[#6c5ffc] text-[#5242e2] group-hover:text-white flex items-center justify-center transition-colors"
                >
                  <span class="material-symbols-outlined text-[16px]">add</span>
                </button>
              </div>

              <!-- Out of Stock Overlay -->
              <div v-if="product.stock <= 0" class="absolute inset-0 bg-white/70 rounded-2xl flex items-center justify-center backdrop-blur-[1px]">
                <span class="px-2 py-1 bg-red-600 text-white text-[10px] font-extrabold uppercase rounded-lg shadow-sm">
                  Stok Habis
                </span>
              </div>
            </div>

            <!-- Empty Product Filter Search -->
            <div v-if="filteredProducts.length === 0" class="col-span-full py-12 text-center bg-white rounded-2xl border border-[#e9edf4]">
              <span class="material-symbols-outlined text-[36px] text-[#c8c4d8] block mb-2">search_off</span>
              <p class="text-xs font-bold text-[#474555]">Tidak ada produk yang cocok</p>
              <p class="text-[11px] text-[#777587] mt-0.5">Coba ubah kata kunci atau pilih kategori lain.</p>
            </div>
          </div>

        </div>

        <!-- RIGHT COLUMN: PINIA SHOPPING CART & CHECKOUT (5 COLS) -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-[#e9edf4] shadow-sm flex flex-col overflow-hidden">
          
          <!-- Cart Header -->
          <div class="p-4 border-b border-[#e9edf4] flex items-center justify-between bg-slate-50/70">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-[#6c5ffc] text-[20px]">shopping_bag</span>
              <h3 class="text-xs font-bold text-[#121c2c] uppercase tracking-wider">Keranjang Belanja</h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#6c5ffc]/15 text-[#5242e2]">
                {{ posStore.totalQuantity }} Item
              </span>
            </div>

            <button 
              v-if="posStore.cart.length > 0"
              @click="posStore.clearCart"
              class="text-[11px] text-[#ba1a1a] hover:underline font-semibold flex items-center gap-1"
            >
              <span class="material-symbols-outlined text-[14px]">delete_sweep</span>
              <span>Kosongkan</span>
            </button>
          </div>

          <!-- Customer Association (Optional Member) -->
          <div class="p-3 bg-[#f8fafc] border-b border-[#e9edf4] text-xs">
            <div v-if="!posStore.selectedMember" class="flex items-center justify-between">
              <span class="text-[#777587] flex items-center gap-1 text-[11px]">
                <span class="material-symbols-outlined text-[15px]">person</span>
                Pelanggan: <strong>Walk-in (Umum)</strong>
              </span>
              <button 
                @click="openMemberSearchModal = true"
                class="text-[11px] font-bold text-[#5242e2] hover:underline"
              >
                + Tautkan Member
              </button>
            </div>

            <div v-else class="flex items-center justify-between bg-white p-2 rounded-xl border border-[#e9edf4]">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#09ad95] text-[18px]">verified_user</span>
                <div>
                  <p class="font-bold text-[#121c2c] text-xs leading-none">{{ posStore.selectedMember.full_name }}</p>
                  <p class="text-[10px] text-[#777587] font-mono leading-none mt-1">{{ posStore.selectedMember.member_code }}</p>
                </div>
              </div>
              <button @click="posStore.clearMember" class="text-slate-400 hover:text-red-600">
                <span class="material-symbols-outlined text-[16px]">close</span>
              </button>
            </div>
          </div>

          <!-- Cart Items Scroll List -->
          <div class="p-4 flex-1 overflow-y-auto max-h-[290px] divide-y divide-[#f0f3f8] space-y-2">
            
            <div 
              v-for="item in posStore.cart" 
              :key="item.id"
              class="pt-2 flex items-center justify-between gap-2 text-xs"
            >
              <!-- Name & Price -->
              <div class="flex-1 min-w-0 pr-2">
                <p class="font-bold text-[#121c2c] truncate leading-tight">{{ item.name }}</p>
                <p class="text-[11px] text-[#777587] mt-0.5">
                  {{ formatRupiah(item.price) }}
                </p>
              </div>

              <!-- Quantity Stepper Controls -->
              <div class="flex items-center gap-1 bg-[#f0f3fb] p-1 rounded-xl border border-[#c8c4d8]/30">
                <button 
                  @click="posStore.decrement(item.id)"
                  class="w-6 h-6 rounded-lg bg-white text-[#474555] hover:bg-slate-100 flex items-center justify-center font-bold"
                  title="Kurangi"
                >
                  <span class="material-symbols-outlined text-[13px]">remove</span>
                </button>

                <input 
                  type="number"
                  min="1"
                  :max="item.stock"
                  v-model.number="item.quantity"
                  @change="posStore.setQuantity(item.id, item.quantity)"
                  class="w-8 text-center text-xs font-bold text-[#121c2c] bg-transparent outline-none"
                />

                <button 
                  @click="posStore.increment(item.id)"
                  :disabled="item.quantity >= item.stock"
                  class="w-6 h-6 rounded-lg bg-white text-[#474555] hover:bg-slate-100 flex items-center justify-center font-bold disabled:opacity-40"
                  title="Tambah"
                >
                  <span class="material-symbols-outlined text-[13px]">add</span>
                </button>
              </div>

              <!-- Subtotal & Remove -->
              <div class="text-right w-24">
                <p class="font-bold text-[#121c2c]">
                  {{ formatRupiah(item.price * item.quantity) }}
                </p>
                <button 
                  @click="posStore.removeItem(item.id)"
                  class="text-[10px] text-slate-400 hover:text-red-500 font-semibold"
                >
                  Hapus
                </button>
              </div>
            </div>

            <!-- Empty Cart State -->
            <div v-if="posStore.cart.length === 0" class="py-10 text-center text-slate-400 space-y-1">
              <span class="material-symbols-outlined text-[36px] text-slate-300">shopping_cart</span>
              <p class="text-xs font-semibold text-[#777587]">Keranjang belanja kosong</p>
              <p class="text-[11px] text-slate-400">Scan barcode atau klik produk di samping untuk menambahkan.</p>
            </div>

          </div>

          <!-- Financial Calculation Breakdown -->
          <div class="p-4 bg-slate-50/80 border-t border-[#e9edf4] space-y-2 text-xs">
            
            <!-- Subtotal -->
            <div class="flex items-center justify-between text-[#474555]">
              <span>Subtotal Produk</span>
              <span class="font-bold text-[#121c2c]">{{ formatRupiah(posStore.subtotal) }}</span>
            </div>

            <!-- Diskon Input Row -->
            <div class="flex items-center justify-between text-[#474555]">
              <div class="flex items-center gap-1.5">
                <span>Diskon</span>
                <div class="inline-flex rounded-lg border border-[#c8c4d8]/40 overflow-hidden text-[10px]">
                  <button 
                    @click="posStore.discountType = 'fixed'"
                    class="px-1.5 py-0.5 font-bold"
                    :class="posStore.discountType === 'fixed' ? 'bg-[#5242e2] text-white' : 'bg-white text-slate-600'"
                  >Rp</button>
                  <button 
                    @click="posStore.discountType = 'percent'"
                    class="px-1.5 py-0.5 font-bold"
                    :class="posStore.discountType === 'percent' ? 'bg-[#5242e2] text-white' : 'bg-white text-slate-600'"
                  >%</button>
                </div>
              </div>

              <div class="flex items-center gap-1">
                <input 
                  type="number"
                  min="0"
                  v-model.number="posStore.discountValue"
                  placeholder="0"
                  class="w-20 h-6 px-2 text-right rounded-lg border border-[#c8c4d8]/40 bg-white text-xs font-bold outline-none"
                />
                <span class="text-red-600 font-bold">-{{ formatRupiah(posStore.discountAmount) }}</span>
              </div>
            </div>

            <!-- Persetujuan supervisor: muncul jika diskon melebihi batas (config/pos.php) -->
            <div v-if="needsDiscountApproval" class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 space-y-1.5">
              <p class="text-[10px] font-semibold text-amber-800">
                Diskon di atas {{ maxDiscountPercentWithoutApproval }}% memerlukan persetujuan supervisor.
              </p>
              <input
                v-model="discountApproval.supervisor_email"
                type="email"
                placeholder="Email supervisor"
                class="w-full h-7 px-2 rounded-lg border border-amber-200 bg-white text-xs outline-none"
              />
              <input
                v-model="discountApproval.supervisor_pin"
                type="password"
                placeholder="PIN supervisor"
                autocomplete="off"
                class="w-full h-7 px-2 rounded-lg border border-amber-200 bg-white text-xs outline-none"
              />
            </div>

            <!-- Grand Total -->
            <div class="pt-2 border-t border-[#e9edf4] flex items-center justify-between">
              <span class="text-sm font-bold text-[#121c2c]">TOTAL TAGIHAN</span>
              <span class="text-lg font-extrabold text-[#5242e2]">{{ formatRupiah(posStore.totalAmount) }}</span>
            </div>

          </div>

          <!-- Payment Options & Tendered Amount -->
          <div class="p-4 bg-white border-t border-[#e9edf4] space-y-3">
            
            <!-- Payment Method Selector -->
            <div>
              <label class="block text-[11px] font-bold text-[#777587] uppercase mb-1.5">Metode Pembayaran</label>
              <div class="grid grid-cols-4 gap-1.5">
                <button 
                  v-for="m in paymentMethods" 
                  :key="m.id"
                  @click="posStore.setPaymentMethod(m.id)"
                  class="py-2 px-1 rounded-xl text-center border font-bold text-[11px] flex flex-col items-center justify-center gap-1 transition-all"
                  :class="posStore.paymentMethod === m.id ? 'bg-[#5242e2] text-white border-[#5242e2] shadow-sm' : 'bg-[#f8fafc] text-[#474555] border-[#e9edf4] hover:bg-slate-100'"
                >
                  <span class="material-symbols-outlined text-[16px]">{{ m.icon }}</span>
                  <span>{{ m.label }}</span>
                </button>
              </div>
            </div>

            <!-- Cash Tendered & Change Due (When Cash Selected) -->
            <div v-if="posStore.paymentMethod === 'cash'" class="space-y-2 pt-1">
              <div class="flex items-center justify-between">
                <label class="text-[11px] font-bold text-[#777587] uppercase">Uang Tunai Diterima</label>
                <span class="text-[10px] text-[#5242e2] font-semibold">Uang Pas: {{ formatRupiah(posStore.totalAmount) }}</span>
              </div>

              <!-- Quick cash buttons -->
              <div class="flex items-center gap-1.5">
                <button 
                  @click="posStore.setCashTendered(posStore.totalAmount)"
                  class="flex-1 py-1 rounded-lg text-[10px] font-bold bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#5242e2] border border-[#c8c4d8]/40"
                >Pas</button>
                <button 
                  @click="posStore.setCashTendered(20000)"
                  class="flex-1 py-1 rounded-lg text-[10px] font-bold bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#474555] border border-[#c8c4d8]/40"
                >20k</button>
                <button 
                  @click="posStore.setCashTendered(50000)"
                  class="flex-1 py-1 rounded-lg text-[10px] font-bold bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#474555] border border-[#c8c4d8]/40"
                >50k</button>
                <button 
                  @click="posStore.setCashTendered(100000)"
                  class="flex-1 py-1 rounded-lg text-[10px] font-bold bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#474555] border border-[#c8c4d8]/40"
                >100k</button>
                <button 
                  @click="posStore.setCashTendered(200000)"
                  class="flex-1 py-1 rounded-lg text-[10px] font-bold bg-[#f0f3fb] hover:bg-[#e8eeff] text-[#474555] border border-[#c8c4d8]/40"
                >200k</button>
              </div>

              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                <input 
                  type="number"
                  v-model.number="posStore.cashTendered"
                  class="w-full h-10 pl-9 pr-3 rounded-xl border border-[#c8c4d8]/40 bg-[#f8fafc] text-sm font-bold text-[#121c2c] focus:border-[#5242e2] focus:bg-white outline-none"
                />
              </div>

              <!-- Change Due Display (Uang Kembalian) -->
              <div 
                class="p-2.5 rounded-xl flex items-center justify-between border"
                :class="posStore.changeDue >= 0 && posStore.cashTendered >= posStore.totalAmount ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'"
              >
                <span class="text-xs font-bold">KEMBALIAN</span>
                <span class="text-sm font-extrabold font-mono">
                  {{ posStore.cashTendered < posStore.totalAmount ? `Kurang ${formatRupiah(posStore.totalAmount - posStore.cashTendered)}` : formatRupiah(posStore.changeDue) }}
                </span>
              </div>
            </div>

            <!-- Process Checkout Button -->
            <button 
              @click="submitCheckout"
              :disabled="isProcessingCheckout || !posStore.isPaymentValid"
              class="w-full h-12 rounded-xl bg-gradient-to-r from-[#6c5ffc] to-[#5242e2] hover:opacity-95 text-white font-bold text-sm shadow-lg shadow-[#6c5ffc]/25 flex items-center justify-center gap-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
            >
              <span v-if="isProcessingCheckout" class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
              <span v-else class="material-symbols-outlined text-[20px]">print</span>
              <span>{{ isProcessingCheckout ? 'Memproses Transaksi...' : `Bayar & Selesaikan (${formatRupiah(posStore.totalAmount)})` }}</span>
            </button>

          </div>

        </div>

      </div>

      <!-- ==================== VIEW 2: TODAY'S TRANSACTION HISTORY & VOID (AUDIT) ==================== -->
      <div v-show="activeView === 'history'" class="bg-white rounded-2xl border border-[#e9edf4] shadow-sm overflow-hidden p-5 space-y-4">
        
        <!-- History Header & Summary -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-[#e9edf4]">
          <div>
            <h3 class="text-sm font-bold text-[#121c2c]">Riwayat Transaksi Kasir Hari Ini</h3>
            <p class="text-xs text-[#777587]">Daftar transaksi ritel POS dan otorisasi pengawasan Void pembatalan.</p>
          </div>

          <!-- Summary Chips -->
          <div class="flex items-center gap-2 text-xs">
            <span class="px-3 py-1 rounded-xl bg-[#09ad95]/10 text-[#09ad95] font-bold border border-[#09ad95]/20">
              Total Penjualan: {{ formatRupiah(stats.today_sales_total) }}
            </span>
            <span class="px-3 py-1 rounded-xl bg-slate-100 text-[#474555] font-bold">
              {{ stats.today_transactions_count }} Berhasil
            </span>
            <span class="px-3 py-1 rounded-xl bg-red-50 text-red-600 font-bold border border-red-200">
              {{ stats.today_void_count }} Void
            </span>
          </div>
        </div>

        <!-- Transactions Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-[#f8fafc] text-[#777587] text-[10px] uppercase font-bold tracking-wider">
                <th class="py-2.5 px-3 rounded-l-lg">No. Invoice</th>
                <th class="py-2.5 px-3">Waktu</th>
                <th class="py-2.5 px-3">Kasir / Member</th>
                <th class="py-2.5 px-3">Item Pembelian</th>
                <th class="py-2.5 px-3">Total Bayar</th>
                <th class="py-2.5 px-3">Metode</th>
                <th class="py-2.5 px-3">Status</th>
                <th class="py-2.5 px-3 text-right rounded-r-lg">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#f0f3f8]">
              <tr v-for="trx in transactionsList" :key="trx.id" class="hover:bg-[#fbfcfe] transition-colors">
                
                <!-- Invoice -->
                <td class="py-3 px-3 font-mono font-bold text-[#5242e2]">
                  {{ trx.invoice_number }}
                </td>

                <!-- Time -->
                <td class="py-3 px-3 text-slate-600">
                  {{ formatTime(trx.created_at) }}
                </td>

                <!-- Cashier / Member -->
                <td class="py-3 px-3">
                  <p class="font-bold text-[#121c2c] leading-tight">{{ trx.cashier?.name || 'Kasir' }}</p>
                  <p v-if="trx.member" class="text-[10px] text-[#09ad95] font-medium">
                    Member: {{ trx.member.full_name }}
                  </p>
                </td>

                <!-- Items -->
                <td class="py-3 px-3">
                  <span class="inline-flex items-center gap-1 font-semibold text-[#474555]">
                    {{ trx.items?.length || 0 }} item ({{ countTotalItems(trx.items) }} unit)
                  </span>
                </td>

                <!-- Total -->
                <td class="py-3 px-3 font-bold text-[#121c2c]">
                  {{ formatRupiah(trx.total_amount) }}
                </td>

                <!-- Method -->
                <td class="py-3 px-3">
                  <span class="uppercase font-mono text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                    {{ trx.payment_method }}
                  </span>
                </td>

                <!-- Status -->
                <td class="py-3 px-3">
                  <span 
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="trx.status === 'COMPLETED' ? 'bg-[#09ad95]/10 text-[#09ad95] border border-[#09ad95]/20' : 'bg-red-100 text-red-700 border border-red-200'"
                  >
                    <span class="material-symbols-outlined text-[12px]">{{ trx.status === 'COMPLETED' ? 'check_circle' : 'cancel' }}</span>
                    {{ trx.status }}
                  </span>
                  
                  <p v-if="trx.status === 'VOID' && trx.void_reason" class="text-[9px] text-red-600 mt-0.5 max-w-xs truncate" :title="trx.void_reason">
                    Ket: {{ trx.void_reason }} (SPV: {{ trx.voided_by?.name || 'Supervisor' }})
                  </p>
                </td>

                <!-- Actions -->
                <td class="py-3 px-3">
                  <!-- Kedua tombol memakai bentuk yang sama (tinggi h-7, ikon + teks) supaya sejajar -->
                  <div class="flex items-center justify-end gap-1.5">
                    <!-- Lihat / cetak ulang struk -->
                    <button
                      @click="viewReceipt(trx)"
                      class="h-7 px-2 rounded-lg text-[10px] font-bold text-slate-600 bg-slate-50 hover:bg-[#e8eeff] hover:text-[#5242e2] border border-slate-200 transition-colors inline-flex items-center gap-1"
                      title="Lihat Struk"
                    >
                      <span class="material-symbols-outlined text-[14px]">receipt</span>
                      <span>Struk</span>
                    </button>

                    <!-- Void: hanya untuk transaksi COMPLETED -->
                    <button
                      v-if="trx.status === 'COMPLETED'"
                      @click="openVoidModal(trx)"
                      class="h-7 px-2 rounded-lg text-[10px] font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-colors inline-flex items-center gap-1"
                      title="Batalkan transaksi ini dengan otorisasi Supervisor"
                    >
                      <span class="material-symbols-outlined text-[14px]">shield</span>
                      <span>Void</span>
                    </button>

                    <!-- Tidak ada tombol Hapus: transaksi yang sudah selesai tidak boleh dihapus
                         (demi audit keuangan). Pembatalan resmi hanya lewat tombol Void. -->
                  </div>
                </td>

              </tr>

              <tr v-if="transactionsList.length === 0">
                <td colspan="8" class="py-8 text-center text-slate-400">
                  Belum ada transaksi ritel yang tercatat hari ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>

    </div>

    <!-- ==================== MODAL 1: RECEIPT / STRUK TRANSAKSI ==================== -->
    <!-- Teleport: modal dipindah langsung ke <body>, supaya saat dicetak
         semua elemen lain bisa disembunyikan (lihat style cetak di bawah). -->
    <Teleport to="body">
    <div
      v-if="activeReceipt"
      class="receipt-modal fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="activeReceipt = null"
    >
      <div class="receipt-card bg-white rounded-3xl max-w-sm w-full overflow-hidden shadow-2xl border border-[#e9edf4] animate-scaleIn">
        
        <!-- Thermal Receipt Body -->
        <div id="receipt-printable" class="p-6 font-mono text-xs text-[#121c2c] space-y-3 bg-[#fffefc]">
          <div class="text-center pb-3 border-b-2 border-dashed border-slate-300 space-y-1">
            <h2 class="text-sm font-extrabold uppercase tracking-wider">FITCORE GMS</h2>
            <p class="text-[10px] text-slate-500">Jl. Kebugaran Sehat No. 88, Jakarta</p>
            <p class="text-[10px] text-slate-500">Telp: (021) 555-8899</p>
          </div>

          <div class="text-[10px] space-y-0.5 text-slate-600 pb-2 border-b border-dashed border-slate-300">
            <div class="flex justify-between">
              <span>Invoice:</span>
              <strong class="text-[#121c2c]">{{ activeReceipt.invoice_number }}</strong>
            </div>
            <div class="flex justify-between">
              <span>Tanggal:</span>
              <span>{{ formatDateTime(activeReceipt.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Kasir:</span>
              <span>{{ activeReceipt.cashier?.name || cashier.name }}</span>
            </div>
            <div v-if="activeReceipt.member" class="flex justify-between text-[#09ad95]">
              <span>Member:</span>
              <span>{{ activeReceipt.member.full_name }}</span>
            </div>
          </div>

          <!-- Items list -->
          <div class="space-y-1.5 py-1 text-[11px]">
            <div v-for="item in activeReceipt.items" :key="item.id" class="space-y-0.5">
              <div class="font-bold text-[#121c2c]">{{ item.product_name }}</div>
              <div class="flex justify-between text-[10px] text-slate-600 pl-2">
                <span>{{ item.quantity }} x {{ formatRupiah(item.unit_price) }}</span>
                <span>{{ formatRupiah(item.subtotal) }}</span>
              </div>
            </div>
          </div>

          <!-- Totals -->
          <div class="pt-2 border-t-2 border-dashed border-slate-300 space-y-1 text-xs">
            <div class="flex justify-between text-slate-600">
              <span>Subtotal:</span>
              <span>{{ formatRupiah(activeReceipt.subtotal) }}</span>
            </div>
            <div v-if="activeReceipt.discount_amount > 0" class="flex justify-between text-red-600 font-bold">
              <span>Diskon:</span>
              <span>-{{ formatRupiah(activeReceipt.discount_amount) }}</span>
            </div>
            <div class="flex justify-between text-sm font-extrabold text-[#121c2c] pt-1 border-t border-slate-200">
              <span>TOTAL:</span>
              <span>{{ formatRupiah(activeReceipt.total_amount) }}</span>
            </div>
            <div class="flex justify-between text-[11px] text-slate-600 pt-1">
              <span>Bayar ({{ activeReceipt.payment_method?.toUpperCase() }}):</span>
              <span>{{ formatRupiah(activeReceipt.cash_tendered || activeReceipt.total_amount) }}</span>
            </div>
            <div v-if="activeReceipt.payment_method === 'cash'" class="flex justify-between text-[11px] font-bold text-emerald-700">
              <span>Kembalian:</span>
              <span>{{ formatRupiah(activeReceipt.change_due || 0) }}</span>
            </div>
          </div>

          <!-- Footer -->
          <div class="pt-3 border-t-2 border-dashed border-slate-300 text-center text-[10px] text-slate-500 space-y-0.5">
            <p class="font-bold">Terima Kasih Atas Kunjungan Anda!</p>
            <p>Barang yang sudah dibeli tidak dapat ditukar kecuali ada otorisasi Void.</p>
          </div>
        </div>

        <!-- Modal Action Buttons (tidak ikut dicetak) -->
        <div class="print:hidden p-4 bg-slate-50 border-t border-[#e9edf4] flex items-center justify-between gap-2">
          <button 
            @click="printReceipt"
            class="flex-1 py-2 px-3 rounded-xl bg-[#5242e2] text-white text-xs font-bold hover:bg-[#4335ba] transition-colors flex items-center justify-center gap-1.5 shadow-md shadow-[#5242e2]/20"
          >
            <span class="material-symbols-outlined text-[16px]">print</span>
            <span>Cetak Struk</span>
          </button>
          
          <button 
            @click="activeReceipt = null"
            class="py-2 px-4 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 transition-colors"
          >
            Tutup
          </button>
        </div>

      </div>
    </div>
    </Teleport>

    <!-- ==================== MODAL 2: SUPERVISOR VOID PROTECTION ==================== -->
    <div 
      v-if="selectedTransactionToVoid" 
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="selectedTransactionToVoid = null"
    >
      <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-red-200 animate-scaleIn">
        
        <!-- Header -->
        <div class="p-5 bg-gradient-to-r from-red-600 to-rose-700 text-white flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
              <span class="material-symbols-outlined text-[22px]">admin_panel_settings</span>
            </div>
            <div>
              <h3 class="text-sm font-extrabold uppercase tracking-wide">Otorisasi Void Transaksi</h3>
              <p class="text-[11px] text-white/80 font-mono">{{ selectedTransactionToVoid.invoice_number }}</p>
            </div>
          </div>
          <button @click="selectedTransactionToVoid = null" class="p-1 rounded-lg text-white/80 hover:bg-white/10">
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>

        <!-- Body Form -->
        <form @submit.prevent="submitVoidAuthorization" class="p-5 space-y-4 text-xs">
          
          <!-- Warning Notice Box -->
          <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-800 space-y-1">
            <div class="flex items-center gap-1.5 font-bold">
              <span class="material-symbols-outlined text-[16px]">warning</span>
              <span>Proteksi Keamanan Kasir</span>
            </div>
            <p class="text-[11px] leading-relaxed text-red-700">
              Transaksi yang dibatalkan akan otomatis mengembalikan seluruh kuantiti produk ke stok gudang, dan tercatat dalam log audit tidak dapat dihapus.
            </p>
          </div>

          <!-- Transaction Summary Info -->
          <div class="p-3 rounded-xl bg-[#f8fafc] border border-[#e9edf4] flex justify-between items-center">
            <div>
              <p class="text-[10px] text-[#777587]">Total Belanja yang Di-void:</p>
              <p class="text-sm font-extrabold text-[#121c2c]">{{ formatRupiah(selectedTransactionToVoid.total_amount) }}</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] text-[#777587]">Metode Pembayaran:</p>
              <p class="font-bold text-[#5242e2] uppercase">{{ selectedTransactionToVoid.payment_method }}</p>
            </div>
          </div>

          <!-- Error Alert if any -->
          <div v-if="voidErrorMessage" class="p-3 rounded-xl bg-red-100 text-red-800 font-bold border border-red-300 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">error</span>
            <span>{{ voidErrorMessage }}</span>
          </div>

          <!-- Supervisor Email / Username -->
          <div>
            <label class="block font-bold text-[#121c2c] mb-1">Email Supervisor</label>
            <input
              v-model="voidForm.supervisor_email"
              type="email"
              required
              placeholder="supervisor@fitcore.com"
              class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none text-xs text-[#121c2c]"
            />
          </div>

          <!-- Supervisor PIN / Password -->
          <div>
            <label class="block font-bold text-[#121c2c] mb-1">PIN / Password Supervisor</label>
            <input 
              v-model="voidForm.supervisor_pin"
              type="password"
              required
              placeholder="Ketik 6 digit PIN atau password..."
              class="w-full h-10 px-3 rounded-xl border border-[#c8c4d8]/50 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none text-xs text-[#121c2c]"
            />
          </div>

          <!-- Void Reason -->
          <div>
            <label class="block font-bold text-[#121c2c] mb-1">Alasan Pembatalan (Void Reason)</label>
            <textarea 
              v-model="voidForm.void_reason"
              required
              rows="2"
              placeholder="Contoh: Customer salah beli varian rasa suplemen / salah input kuantiti..."
              class="w-full p-2.5 rounded-xl border border-[#c8c4d8]/50 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 outline-none text-xs text-[#121c2c]"
            ></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="pt-2 flex items-center justify-end gap-2">
            <button 
              type="button"
              @click="selectedTransactionToVoid = null"
              class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-100 transition-colors"
            >
              Batal
            </button>
            <button 
              type="submit"
              :disabled="isProcessingVoid"
              class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold transition-colors flex items-center gap-1.5 shadow-md shadow-red-600/30 disabled:opacity-50"
            >
              <span v-if="isProcessingVoid" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span class="material-symbols-outlined text-[16px]">verified</span>
              <span>{{ isProcessingVoid ? 'Memvalidasi...' : 'Otorisasi & Eksekusi Void' }}</span>
            </button>
          </div>

        </form>

      </div>
    </div>

    <!-- ==================== MODAL 3: MEMBER SEARCH POPUP ==================== -->
    <div 
      v-if="openMemberSearchModal" 
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="openMemberSearchModal = false"
    >
      <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-[#e9edf4] animate-scaleIn">
        <div class="p-4 border-b border-[#e9edf4] flex items-center justify-between bg-slate-50">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[#6c5ffc]">group</span>
            <h4 class="text-xs font-bold text-[#121c2c]">Cari & Tautkan Member Pelanggan</h4>
          </div>
          <button @click="openMemberSearchModal = false" class="text-slate-400 hover:text-slate-700">
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </div>

        <div class="p-4 space-y-3">
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input 
              v-model="memberSearchQuery"
              @input="searchMembers"
              type="text"
              placeholder="Cari nama, kode member, atau no HP..."
              class="w-full h-10 pl-9 pr-3 rounded-xl border border-[#c8c4d8]/50 text-xs outline-none focus:border-[#6c5ffc]"
            />
          </div>

          <div class="max-h-56 overflow-y-auto divide-y divide-slate-100 text-xs">
            <div 
              v-for="m in memberSearchResults" 
              :key="m.id"
              @click="chooseMember(m)"
              class="p-2.5 hover:bg-[#f0f3fb] cursor-pointer rounded-xl flex items-center justify-between transition-colors"
            >
              <div>
                <p class="font-bold text-[#121c2c]">{{ m.full_name }}</p>
                <p class="text-[10px] text-[#777587] font-mono">{{ m.member_code }} • {{ m.phone }}</p>
              </div>
              <span class="text-[10px] font-bold text-[#5242e2] bg-[#5242e2]/10 px-2 py-0.5 rounded-full">Pilih</span>
            </div>

            <div v-if="memberSearchResults.length === 0 && memberSearchQuery" class="py-6 text-center text-slate-400 text-xs">
              Tidak ada member yang cocok.
            </div>
          </div>
        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from '@/route';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePosStore } from '@/Stores/posStore';

const props = defineProps({
  // Batas diskon (%) tanpa persetujuan supervisor, dari config/pos.php
  maxDiscountPercentWithoutApproval: {
    type: Number,
    default: 10,
  },
  products: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  recentTransactions: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({
      today_sales_total: 0,
      today_transactions_count: 0,
      today_void_count: 0,
    }),
  },
  cashier: {
    type: Object,
    default: () => ({ name: 'Kasir', email: 'cs@fitcore.com' }),
  },
});

const posStore = usePosStore();

// View State
const activeView = ref('register'); // 'register' | 'history'
const barcodeInputRef = ref(null);
const barcodeScanQuery = ref('');
const searchKeyword = ref('');
const selectedCategory = ref('all');

// Alert states
const barcodeAlertMessage = ref('');
const barcodeAlertError = ref(false);

// Checkout State
const isProcessingCheckout = ref(false);
const activeReceipt = ref(null);

// Persetujuan diskon besar oleh supervisor
const discountApproval = ref({
  supervisor_email: '',
  supervisor_pin: '',
});

// true jika diskon melebihi batas, sehingga kolom email + PIN supervisor wajib diisi.
// Server tetap mengecek ulang aturan ini; tampilan ini hanya membantu kasir.
const needsDiscountApproval = computed(() => {
  const maxDiscount = (posStore.subtotal * props.maxDiscountPercentWithoutApproval) / 100;
  return posStore.discountAmount > maxDiscount;
});

// History State
const transactionsList = ref([...props.recentTransactions]);

// Void Modal State
const selectedTransactionToVoid = ref(null);
const isProcessingVoid = ref(false);
const voidErrorMessage = ref('');
const voidForm = ref({
  supervisor_email: 'supervisor@fitcore.com',
  supervisor_pin: '',
  void_reason: '',
});

// Member Lookup State
const openMemberSearchModal = ref(false);
const memberSearchQuery = ref('');
const memberSearchResults = ref([]);
let memberSearchTimer = null;

// Payment Methods
const paymentMethods = [
  { id: 'cash', label: 'Tunai', icon: 'payments' },
  { id: 'qris', label: 'QRIS', icon: 'qr_code_2' },
  { id: 'card', label: 'Debit/Kartu', icon: 'credit_card' },
  { id: 'transfer', label: 'Transfer', icon: 'account_balance' },
];

// --- FILTERED PRODUCTS ---
const filteredProducts = computed(() => {
  return props.products.filter(p => {
    const matchCategory = selectedCategory.value === 'all' || p.category === selectedCategory.value;
    const matchKeyword = !searchKeyword.value || 
      p.name.toLowerCase().includes(searchKeyword.value.toLowerCase()) ||
      p.barcode.toLowerCase().includes(searchKeyword.value.toLowerCase());
    return matchCategory && matchKeyword;
  });
});

// --- DATE FORMATTERS ---
const formattedTodayDate = computed(() => {
  return new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
});

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(val) || 0);
};

const formatTime = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const countTotalItems = (items) => {
  if (!items) return 0;
  return items.reduce((sum, i) => sum + (i.quantity || 0), 0);
};

// --- CART INTERACTIONS ---
const addToCart = (product) => {
  const result = posStore.addItem(product, 1);
  if (!result.success) {
    barcodeAlertMessage.value = result.message;
    barcodeAlertError.value = true;
  }
};

const handleBarcodeScan = () => {
  const code = barcodeScanQuery.value.trim();
  if (!code) return;

  const result = posStore.scanBarcode(code, props.products);
  if (result.success) {
    barcodeAlertMessage.value = `Berhasil ditambahkan: ${result.product ? result.product.name : 'Produk'}`;
    barcodeAlertError.value = false;
  } else {
    barcodeAlertMessage.value = result.message;
    barcodeAlertError.value = true;
  }

  barcodeScanQuery.value = '';
  nextTick(() => {
    barcodeInputRef.value?.focus();
  });
};

// --- CHECKOUT SUBMISSION ---
const submitCheckout = async () => {
  if (!posStore.isPaymentValid || isProcessingCheckout.value) return;

  isProcessingCheckout.value = true;
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const payload = {
    items: posStore.cart.map(i => ({
      product_id: i.id,
      quantity: i.quantity,
    })),
    payment_method: posStore.paymentMethod,
    cash_tendered: posStore.paymentMethod === 'cash' ? posStore.cashTendered : posStore.totalAmount,
    discount_amount: posStore.discountAmount,
    tax_amount: posStore.taxAmount,
    member_id: posStore.selectedMember ? posStore.selectedMember.id : null,
    notes: posStore.notes,
    // Hanya dikirim jika diskon melebihi batas
    supervisor_email: needsDiscountApproval.value ? discountApproval.value.supervisor_email : null,
    supervisor_pin: needsDiscountApproval.value ? discountApproval.value.supervisor_pin : null,
  };

  try {
    const res = await fetch(route('pos.checkout'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();

    if (res.ok && data.success) {
      // 1. Deduct local products stock for instant feedback
      payload.items.forEach(item => {
        const p = props.products.find(prod => prod.id === item.product_id);
        if (p) {
          p.stock = Math.max(0, p.stock - item.quantity);
        }
      });

      // 2. Prepend to transactions list
      transactionsList.value.unshift(data.transaction);

      // 3. Set receipt modal
      activeReceipt.value = data.transaction;

      // 4. Clear cart & PIN supervisor (PIN tidak boleh tersimpan untuk transaksi berikutnya)
      posStore.clearCart();
      discountApproval.value = { supervisor_email: '', supervisor_pin: '' };
    } else {
      alert(data.message || 'Gagal memproses transaksi.');
    }
  } catch (err) {
    console.error('Checkout error:', err);
    alert('Terjadi kendala jaringan saat menghubungi server kasir.');
  } finally {
    isProcessingCheckout.value = false;
  }
};

// --- RECEIPT PRINTING ---
const viewReceipt = (trx) => {
  activeReceipt.value = trx;
};

const printReceipt = () => {
  window.print();
};

// --- SUPERVISOR VOID MODAL & LOGIC ---
const openVoidModal = (transaction) => {
  selectedTransactionToVoid.value = transaction;
  voidErrorMessage.value = '';
  voidForm.value.supervisor_pin = '';
  voidForm.value.void_reason = '';
};

const submitVoidAuthorization = async () => {
  if (!selectedTransactionToVoid.value || isProcessingVoid.value) return;

  isProcessingVoid.value = true;
  voidErrorMessage.value = '';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const trxId = selectedTransactionToVoid.value.id;

  try {
    const res = await fetch(route('pos.void', trxId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        supervisor_email: voidForm.value.supervisor_email,
        supervisor_pin: voidForm.value.supervisor_pin,
        void_reason: voidForm.value.void_reason,
      }),
    });

    const data = await res.json();

    if (res.ok && data.success) {
      // 1. Restore local product stock
      if (selectedTransactionToVoid.value.items) {
        selectedTransactionToVoid.value.items.forEach(item => {
          const p = props.products.find(prod => prod.id === item.product_id);
          if (p) {
            p.stock += item.quantity;
          }
        });
      }

      // 2. Update transaction status locally
      const idx = transactionsList.value.findIndex(t => t.id === trxId);
      if (idx > -1) {
        transactionsList.value[idx] = data.transaction;
      }

      alert(data.message || 'Transaksi berhasil di-VOID.');
      selectedTransactionToVoid.value = null;
    } else {
      voidErrorMessage.value = data.message || 'Otorisasi Void ditolak.';
    }
  } catch (err) {
    console.error('Void error:', err);
    voidErrorMessage.value = 'Gagal menghubungi server untuk otorisasi void.';
  } finally {
    isProcessingVoid.value = false;
  }
};

// --- MEMBER SEARCH LOOKUP ---
const searchMembers = () => {
  clearTimeout(memberSearchTimer);
  const q = memberSearchQuery.value.trim();
  if (!q) {
    memberSearchResults.value = [];
    return;
  }

  memberSearchTimer = setTimeout(async () => {
    try {
      const res = await fetch(`${route('members.quick-search')}?q=${encodeURIComponent(q)}`);
      if (res.ok) {
        const data = await res.json();
        memberSearchResults.value = data;
      }
    } catch (err) {
      console.warn('Member quick search error:', err);
    }
  }, 300);
};

const chooseMember = (member) => {
  posStore.setMember(member);
  openMemberSearchModal.value = false;
  memberSearchQuery.value = '';
  memberSearchResults.value = [];
};

// Lifecycle
onMounted(() => {
  nextTick(() => {
    barcodeInputRef.value?.focus();
  });
});
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

<!--
  Style CETAK STRUK. Sengaja TIDAK "scoped", karena harus bisa menyembunyikan
  elemen di luar komponen ini (sidebar, header layout, dll).

  Cara kerja saat window.print():
  1. Modal struk berada langsung di <body> (lewat <Teleport to="body">).
  2. Semua anak <body> LAINNYA (seluruh aplikasi: sidebar, POS, dll)
     disembunyikan dengan display: none, jadi tidak memakan tempat di kertas
     (tidak ada halaman kosong tambahan).
  3. Modal & kartunya "dinetralkan": posisi fixed, transform/animasi,
     overflow-hidden, blur, dan bayangan dimatikan. Kalau tidak, struk
     terpotong (hanya bagian atasnya yang tercetak).
  4. Tombol "Cetak Struk" & "Tutup" disembunyikan (class print:hidden).
  5. Lebar struk 80mm (ukuran kertas printer thermal).
-->
<style>
@media print {
  body {
    background: #fff !important;
  }

  body > *:not(.receipt-modal) {
    display: none !important;
  }

  .receipt-modal,
  .receipt-card {
    position: static !important;
    display: block !important;
    padding: 0 !important;
    transform: none !important;
    animation: none !important;
    overflow: visible !important;
    backdrop-filter: none !important;
    box-shadow: none !important;
    border: none !important;
    border-radius: 0 !important;
    background: transparent !important;
  }

  #receipt-printable {
    width: 80mm;
    margin: 0;
    padding: 10px;
    font-size: 11px;
    background: #fff !important;
  }
}
</style>

