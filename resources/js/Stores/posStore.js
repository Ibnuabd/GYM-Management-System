import { defineStore } from 'pinia';

export const usePosStore = defineStore('pos', {
  state: () => ({
    cart: [],
    discountType: 'fixed', // 'fixed' (Rp) | 'percent' (%)
    discountValue: 0,
    taxPercentage: 0,
    paymentMethod: 'cash', // 'cash' | 'qris' | 'card' | 'transfer'
    cashTendered: 0,
    selectedMember: null,
    notes: '',
  }),

  getters: {
    // Total count of unique products in cart
    uniqueItemCount: (state) => state.cart.length,

    // Total quantity of units in cart
    totalQuantity: (state) => state.cart.reduce((sum, item) => sum + item.quantity, 0),

    // Subtotal before discounts & taxes
    subtotal: (state) => {
      return state.cart.reduce((sum, item) => sum + (Number(item.price) * item.quantity), 0);
    },

    // Calculated discount amount
    discountAmount: (state) => {
      const sub = state.cart.reduce((sum, item) => sum + (Number(item.price) * item.quantity), 0);
      if (state.discountType === 'percent') {
        const pct = Math.min(100, Math.max(0, Number(state.discountValue) || 0));
        return Math.round((sub * pct) / 100);
      }
      return Math.min(sub, Math.max(0, Number(state.discountValue) || 0));
    },

    // Calculated tax amount
    taxAmount: (state) => {
      const sub = state.cart.reduce((sum, item) => sum + (Number(item.price) * item.quantity), 0);
      const disc = state.discountType === 'percent'
        ? Math.round((sub * Math.min(100, Math.max(0, Number(state.discountValue) || 0))) / 100)
        : Math.min(sub, Math.max(0, Number(state.discountValue) || 0));
      const taxable = Math.max(0, sub - disc);
      return Math.round((taxable * (Number(state.taxPercentage) || 0)) / 100);
    },

    // Final grand total
    totalAmount(state) {
      return Math.max(0, this.subtotal - this.discountAmount + this.taxAmount);
    },

    // Cash change due to customer
    changeDue(state) {
      if (state.paymentMethod !== 'cash') {
        return 0;
      }
      return Math.max(0, (Number(state.cashTendered) || 0) - this.totalAmount);
    },

    // Validation whether payment requirements are fulfilled
    isPaymentValid(state) {
      if (state.cart.length === 0) return false;
      if (state.paymentMethod === 'cash') {
        return (Number(state.cashTendered) || 0) >= this.totalAmount;
      }
      return true;
    },
  },

  actions: {
    /**
     * Add product to cart or increment quantity if already present.
     */
    addItem(product, qty = 1) {
      if (!product || product.stock <= 0) {
        return { success: false, message: `Stok produk '${product?.name}' habis.` };
      }

      const existingIndex = this.cart.findIndex(i => i.id === product.id);

      if (existingIndex > -1) {
        const item = this.cart[existingIndex];
        const newQty = item.quantity + qty;

        if (newQty > product.stock) {
          return {
            success: false,
            message: `Jumlah melebihi sisa stok '${product.name}' (Maks: ${product.stock}).`,
          };
        }

        item.quantity = newQty;
      } else {
        if (qty > product.stock) {
          return {
            success: false,
            message: `Jumlah melebihi sisa stok '${product.name}' (Maks: ${product.stock}).`,
          };
        }

        this.cart.push({
          id: product.id,
          barcode: product.barcode,
          name: product.name,
          category: product.category,
          price: Number(product.selling_price),
          cost_price: Number(product.cost_price),
          stock: product.stock,
          quantity: qty,
          image_url: product.image_url,
        });
      }

      // Default cash tendered if it was matching total before
      if (this.paymentMethod === 'cash') {
        this.cashTendered = this.totalAmount;
      }

      return { success: true };
    },

    /**
     * Rapid scan barcode handler: look up product in active inventory and append.
     */
    scanBarcode(barcode, productsList) {
      const cleanBarcode = String(barcode).trim();
      if (!cleanBarcode) return { success: false, message: 'Barcode kosong.' };

      const product = productsList.find(p => p.barcode === cleanBarcode);

      if (!product) {
        return {
          success: false,
          message: `Produk dengan barcode '${cleanBarcode}' tidak ditemukan dalam katalog.`,
        };
      }

      return this.addItem(product, 1);
    },

    /**
     * Set explicit item quantity in cart.
     */
    setQuantity(productId, quantity) {
      const item = this.cart.find(i => i.id === productId);
      if (!item) return;

      const qty = parseInt(quantity, 10);
      if (isNaN(qty) || qty <= 0) {
        this.removeItem(productId);
        return;
      }

      if (qty > item.stock) {
        item.quantity = item.stock;
        return;
      }

      item.quantity = qty;

      if (this.paymentMethod === 'cash' && this.cashTendered < this.totalAmount) {
        this.cashTendered = this.totalAmount;
      }
    },

    /**
     * Increment item quantity by 1.
     */
    increment(productId) {
      const item = this.cart.find(i => i.id === productId);
      if (!item) return;

      if (item.quantity < item.stock) {
        item.quantity++;
        if (this.paymentMethod === 'cash' && this.cashTendered < this.totalAmount) {
          this.cashTendered = this.totalAmount;
        }
      }
    },

    /**
     * Decrement item quantity by 1, removing when reaching 0.
     */
    decrement(productId) {
      const item = this.cart.find(i => i.id === productId);
      if (!item) return;

      if (item.quantity > 1) {
        item.quantity--;
      } else {
        this.removeItem(productId);
      }
    },

    /**
     * Remove item completely from cart.
     */
    removeItem(productId) {
      this.cart = this.cart.filter(i => i.id !== productId);
      if (this.cart.length === 0) {
        this.cashTendered = 0;
      } else if (this.paymentMethod === 'cash' && this.cashTendered > this.totalAmount) {
        this.cashTendered = this.totalAmount;
      }
    },

    /**
     * Clear current shopping cart and reset transaction state.
     */
    clearCart() {
      this.cart = [];
      this.discountValue = 0;
      this.discountType = 'fixed';
      this.cashTendered = 0;
      this.selectedMember = null;
      this.notes = '';
      this.paymentMethod = 'cash';
    },

    /**
     * Set payment method and auto-sync tendered amount.
     */
    setPaymentMethod(method) {
      this.paymentMethod = method;
      if (method === 'cash') {
        this.cashTendered = this.totalAmount;
      } else {
        this.cashTendered = this.totalAmount;
      }
    },

    /**
     * Set cash tendered with quick suggestion buttons (e.g. Pas, 50k, 100k, 200k).
     */
    setCashTendered(amount) {
      this.cashTendered = Math.max(0, Number(amount) || 0);
    },

    /**
     * Link transaction to a gym member.
     */
    setMember(member) {
      this.selectedMember = member;
    },

    /**
     * Remove linked gym member.
     */
    clearMember() {
      this.selectedMember = null;
    },
  },
});

