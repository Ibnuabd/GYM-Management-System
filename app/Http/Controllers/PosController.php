<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\PosVoidLog;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    /**
     * Batas percobaan PIN supervisor yang gagal sebelum dikunci.
     */
    private const MAX_SUPERVISOR_PIN_ATTEMPTS = 5;

    /**
     * Lama penguncian PIN supervisor (detik) = 15 menit.
     */
    private const SUPERVISOR_PIN_LOCKOUT_SECONDS = 15 * 60;

    /**
     * Pesan saat transaksi sudah pernah di-VOID (dipakai di 2 tempat).
     */
    private const ALREADY_VOIDED_MESSAGE = 'Transaksi ini sudah dibatalkan (VOID) sebelumnya.';

    /**
     * Display the POS Cashier retail register interface.
     */
    public function index(Request $request): Response
    {
        $products = Product::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $categories = Product::active()
            ->distinct()
            ->pluck('category')
            ->values();

        $recentTransactions = PosTransaction::with(['items.product', 'cashier', 'member', 'voidedBy'])
            ->today()
            ->latest('id')
            ->take(25)
            ->get();

        $todayCompleted = PosTransaction::today()->completed();
        $todayVoid = PosTransaction::today()->voided();

        $stats = [
            'today_sales_total' => (float) $todayCompleted->sum('total_amount'),
            'today_transactions_count' => $todayCompleted->count(),
            'today_void_count' => $todayVoid->count(),
            'total_items_sold' => (int) PosTransactionItem::whereHas('transaction', fn ($q) => $q->today()->completed())->sum('quantity'),
        ];

        return Inertia::render('Pos/Index', [
            'products' => $products,
            'categories' => $categories,
            'recentTransactions' => $recentTransactions,
            'stats' => $stats,
            // Batas diskon tanpa persetujuan supervisor (persen), dipakai halaman POS
            // untuk menampilkan kolom email + PIN supervisor saat diskon melebihi batas.
            'maxDiscountPercentWithoutApproval' => (float) config('pos.max_discount_percent_without_approval'),
            'cashier' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }

    /**
     * Rapid product search by barcode or keyword.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');

        $products = Product::active()
            ->search($query)
            ->take(20)
            ->get();

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Checkout POS transaction with atomic inventory deduction wrapped in DB::transaction.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,qris,card,transfer'],
            'cash_tendered' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            // Hanya wajib diisi jika diskon melebihi batas (lihat config/pos.php)
            'supervisor_email' => ['nullable', 'string', 'email'],
            'supervisor_pin' => ['nullable', 'string'],
        ]);

        try {
            $transaction = DB::transaction(function () use ($validated, $request) {
                $subtotal = 0;
                $itemsToInsert = [];

                // 1. Lock rows and validate inventory atomically
                foreach ($validated['items'] as $item) {
                    /** @var Product $product */
                    $product = Product::where('id', $item['product_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    // Produk yang sudah dinonaktifkan tidak boleh dijual lagi.
                    if (! $product->is_active) {
                        throw new \InvalidArgumentException(
                            "Produk '{$product->name}' sudah tidak aktif dan tidak dapat dijual."
                        );
                    }

                    $qty = (int) $item['quantity'];

                    if ($product->stock < $qty) {
                        throw new \InvalidArgumentException(
                            "Stok tidak mencukupi untuk '{$product->name}'. Tersedia: {$product->stock}, diminta: {$qty}."
                        );
                    }

                    // Atomic inventory deduction
                    $product->decrement('stock', $qty);

                    $itemSubtotal = $product->selling_price * $qty;
                    $subtotal += $itemSubtotal;

                    $itemsToInsert[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'cost_price' => $product->cost_price,
                        'unit_price' => $product->selling_price,
                        'quantity' => $qty,
                        'subtotal' => $itemSubtotal,
                    ];
                }

                $discount = (float) ($validated['discount_amount'] ?? 0);

                // Diskon besar wajib disetujui supervisor. Dicek SETELAH subtotal dihitung
                // dari harga di database (bukan harga kiriman browser).
                // null = diskon kecil (tidak perlu persetujuan), berisi User = supervisor yang menyetujui.
                $discountApprovedBy = null;
                if ($discount > $this->maxDiscountWithoutApproval($subtotal)) {
                    $discountApprovedBy = $this->ensureDiscountIsApprovedBySupervisor($request, $validated);
                }

                $tax = (float) ($validated['tax_amount'] ?? 0);
                $totalAmount = max(0, $subtotal - $discount + $tax);

                $paymentMethod = $validated['payment_method'];
                $cashTendered = $paymentMethod === 'cash' ? (float) ($validated['cash_tendered'] ?? $totalAmount) : $totalAmount;

                if ($paymentMethod === 'cash' && $cashTendered < $totalAmount) {
                    throw new \InvalidArgumentException('Uang tunai yang diserahkan kurang dari total belanja.');
                }

                $changeDue = $paymentMethod === 'cash' ? max(0, $cashTendered - $totalAmount) : 0;

                // 2. Create POS Transaction
                $posTransaction = PosTransaction::create([
                    'invoice_number' => PosTransaction::generateInvoiceNumber(),
                    'user_id' => $request->user()->id,
                    'member_id' => $validated['member_id'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'cash_tendered' => $cashTendered,
                    'change_due' => $changeDue,
                    'status' => 'COMPLETED',
                    'notes' => $validated['notes'] ?? null,
                ]);

                // 3. Insert transaction items
                foreach ($itemsToInsert as $item) {
                    $posTransaction->items()->create($item);
                }

                // 4. Log Aktivitas (di dalam transaksi: jika gagal/rollback, log ikut batal).
                ActivityLog::record('pos.checkout', "Transaksi kasir {$posTransaction->invoice_number} sebesar ".self::rupiah($totalAmount), $posTransaction, [
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'items_count' => count($itemsToInsert),
                ]);

                if ($discountApprovedBy !== null) {
                    ActivityLog::record('pos.discount_approved', 'Diskon '.self::rupiah($discount)." pada {$posTransaction->invoice_number} disetujui oleh {$discountApprovedBy->name}", $posTransaction, [
                        'subtotal' => $subtotal,
                        'discount_amount' => $discount,
                        'approved_by_id' => $discountApprovedBy->id,
                        'approved_by' => $discountApprovedBy->name,
                    ]);
                }

                return $posTransaction->load(['items.product', 'cashier', 'member']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diproses.',
                'transaction' => $transaction,
            ], 201);
        } catch (HttpResponseException $e) {
            // Response yang sudah disiapkan (misal: diskon ditolak supervisor). Transaksi sudah di-rollback.
            return $e->getResponse();
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            // Detail error (bisa berisi query SQL / lokasi file) HANYA dicatat ke log server,
            // tidak dikirim ke browser. Cek detailnya di storage/logs/laravel.log.
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses transaksi. Silakan coba lagi atau hubungi admin.',
            ], 500);
        }
    }

    /**
     * Authorize and execute transaction cancellation (VOID) with supervisor protection.
     *
     * Wajib disetujui supervisor (email + PIN), lihat authorizeSupervisor().
     */
    public function voidTransaction(Request $request, PosTransaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'supervisor_email' => ['required', 'string', 'email'],
            'supervisor_pin' => ['required', 'string'],
            'void_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'supervisor_email.email' => 'Masukkan email supervisor yang valid.',
        ]);

        // Cek cepat di awal agar kasir langsung tahu tanpa perlu memasukkan PIN.
        // Catatan: cek ini BELUM aman dari request bersamaan, jadi status dicek
        // sekali lagi di dalam DB::transaction() setelah baris transaksi dikunci.
        if ($transaction->status === 'VOID') {
            return response()->json([
                'success' => false,
                'message' => self::ALREADY_VOIDED_MESSAGE,
            ], 422);
        }

        // 1-2. Cek email + PIN supervisor (lihat authorizeSupervisor() di bawah).
        $supervisor = $this->authorizeSupervisor($request, $validated['supervisor_email'], $validated['supervisor_pin']);
        if ($supervisor instanceof JsonResponse) {
            return $supervisor; // ditolak: kirim pesan error-nya ke kasir
        }

        try {
            $transaction = DB::transaction(function () use ($transaction, $supervisor, $validated, $request) {
                // 3. Ambil ulang transaksi dari database sambil MENGUNCI barisnya.
                //    Jika ada 2 request void bersamaan, request kedua harus menunggu
                //    sampai request pertama selesai, lalu ia akan melihat status "VOID".
                //    (Memanggil $transaction->lockForUpdate() langsung pada model TIDAK mengunci apa pun,
                //    karena hanya membuat query yang tidak pernah dijalankan.)
                /** @var PosTransaction $lockedTransaction */
                $lockedTransaction = PosTransaction::whereKey($transaction->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 4. Cek status pada data yang sudah dikunci (ini pengecekan yang benar-benar aman).
                if ($lockedTransaction->status === 'VOID') {
                    throw new \InvalidArgumentException(self::ALREADY_VOIDED_MESSAGE);
                }

                $itemsSummary = [];

                // 5. Kembalikan stok setiap produk (baris produk juga dikunci).
                foreach ($lockedTransaction->items as $item) {
                    /** @var Product|null $product */
                    $product = Product::where('id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        $product->increment('stock', $item->quantity);
                    }

                    $itemsSummary[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'restored_quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                }

                // 6. Ubah status transaksi menjadi VOID.
                $lockedTransaction->update([
                    'status' => 'VOID',
                    'voided_by' => $supervisor->id,
                    'void_reason' => $validated['void_reason'],
                    'voided_at' => Carbon::now(),
                ]);

                // 7. Simpan log audit pembatalan.
                PosVoidLog::create([
                    'pos_transaction_id' => $lockedTransaction->id,
                    'invoice_number' => $lockedTransaction->invoice_number,
                    'cashier_user_id' => $lockedTransaction->user_id,
                    'supervisor_user_id' => $supervisor->id,
                    'total_amount' => $lockedTransaction->total_amount,
                    'items_summary' => $itemsSummary,
                    'void_reason' => $validated['void_reason'],
                    'ip_address' => $request->ip(),
                    'created_at' => Carbon::now(),
                ]);

                // 8. Log Aktivitas.
                ActivityLog::record('pos.voided', "VOID transaksi {$lockedTransaction->invoice_number} (".self::rupiah((float) $lockedTransaction->total_amount)."), disetujui {$supervisor->name}", $lockedTransaction, [
                    'total_amount' => (float) $lockedTransaction->total_amount,
                    'void_reason' => $validated['void_reason'],
                    'approved_by_id' => $supervisor->id,
                    'approved_by' => $supervisor->name,
                ]);

                return $lockedTransaction;
            });

            $transaction->load(['items.product', 'cashier', 'member', 'voidedBy']);

            return response()->json([
                'success' => true,
                'message' => "Transaksi {$transaction->invoice_number} berhasil di-VOID dan seluruh stok telah dikembalikan.",
                'transaction' => $transaction,
            ]);
        } catch (\InvalidArgumentException $e) {
            // Kesalahan yang memang boleh dibaca kasir (misal: transaksi sudah di-VOID).
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            // Detail error hanya dicatat ke log server, tidak dikirim ke browser.
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pembatalan karena kesalahan sistem. Silakan coba lagi atau hubungi admin.',
            ], 500);
        }
    }

    /**
     * Direct deletion is explicitly disabled on finalized orders.
     */
    public function destroy(PosTransaction $transaction): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Pesanan yang telah difinalisasi tidak dapat dihapus secara langsung. Gunakan prosedur Void resmi dengan otorisasi Supervisor.',
        ], 403);
    }

    /**
     * Nominal diskon terbesar yang boleh diberikan kasir tanpa persetujuan supervisor.
     *
     * Contoh: batas 10% dan subtotal Rp200.000 => maksimal Rp20.000.
     */
    private function maxDiscountWithoutApproval(float $subtotal): float
    {
        $maxPercent = (float) config('pos.max_discount_percent_without_approval');

        return $subtotal * $maxPercent / 100;
    }

    /**
     * Pastikan diskon besar sudah disetujui supervisor.
     *
     * Jika belum/ditolak, lempar HttpResponseException berisi pesan error.
     * Exception ini membatalkan (rollback) DB::transaction checkout, termasuk pengurangan stok.
     *
     * @param  array<string, mixed>  $validated
     * @return User Supervisor yang menyetujui diskon.
     */
    private function ensureDiscountIsApprovedBySupervisor(Request $request, array $validated): User
    {
        $maxPercent = (float) config('pos.max_discount_percent_without_approval');

        if (blank($validated['supervisor_email'] ?? null) || blank($validated['supervisor_pin'] ?? null)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'requires_supervisor_approval' => true,
                'message' => "Diskon di atas {$maxPercent}% dari subtotal memerlukan persetujuan supervisor. Isi email dan PIN supervisor.",
            ], 403));
        }

        $supervisor = $this->authorizeSupervisor($request, $validated['supervisor_email'], $validated['supervisor_pin']);
        if ($supervisor instanceof JsonResponse) {
            throw new HttpResponseException($supervisor);
        }

        return $supervisor;
    }

    /**
     * Format angka menjadi Rupiah untuk teks log, contoh: 150000 -> "Rp 150.000".
     */
    private static function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Periksa email + PIN supervisor untuk aksi yang butuh persetujuan atasan
     * (void transaksi dan diskon besar).
     *
     * Hasil:
     * - User         => supervisor valid, aksi boleh dilanjutkan.
     * - JsonResponse => ditolak, kirim response ini ke kasir.
     *
     * PIN supervisor hanya 6 digit, jadi mudah ditebak jika boleh dicoba tanpa batas.
     * Setelah 5 kali gagal (per email supervisor + kasir yang mencoba), otorisasi dikunci 15 menit.
     * Penghitungnya dipakai bersama untuk void dan diskon, jadi tidak bisa "ditambah" dengan berganti aksi.
     */
    private function authorizeSupervisor(Request $request, string $supervisorEmail, string $supervisorPin): User|JsonResponse
    {
        $supervisorEmail = Str::lower(trim($supervisorEmail));

        // Kunci unik penghitung gagal, contoh: "pos-supervisor-pin|supervisor@fitcore.com|kasir-3"
        $throttleKey = 'pos-supervisor-pin|'.$supervisorEmail.'|kasir-'.$request->user()->id;

        // Tolak dulu jika sudah terlalu banyak percobaan gagal.
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_SUPERVISOR_PIN_ATTEMPTS)) {
            $minutesLeft = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan PIN yang salah. Otorisasi supervisor dikunci, coba lagi dalam {$minutesLeft} menit.",
            ], 429);
        }

        // Cari supervisor HANYA berdasarkan email (nama tidak unik, jadi tidak dipakai).
        $supervisor = User::where('email', $supervisorEmail)->first();

        if (! $supervisor) {
            return $this->rejectSupervisorAuthorization($throttleKey, 'Akun Supervisor tidak ditemukan.', 422);
        }

        // Akun yang dinonaktifkan admin tidak boleh memberi otorisasi (issue #27).
        if (! $supervisor->is_active) {
            return $this->rejectSupervisorAuthorization($throttleKey, 'Akun Supervisor ini sudah dinonaktifkan.', 403);
        }

        // Permission "pos_void" = izin memberi otorisasi supervisor di kasir (admin & supervisor).
        if (! $supervisor->hasPermissionTo('pos_void')) {
            return $this->rejectSupervisorAuthorization($throttleKey, 'Akun ini tidak memiliki hak akses otorisasi supervisor (pos_void).', 403);
        }

        if (! $supervisor->verifySupervisorPin($supervisorPin)) {
            return $this->rejectSupervisorAuthorization($throttleKey, 'PIN / Password Supervisor tidak valid.', 422);
        }

        // Berhasil: hitungan gagal di-reset ke 0.
        RateLimiter::clear($throttleKey);

        return $supervisor;
    }

    /**
     * Catat 1 percobaan otorisasi supervisor yang gagal, lalu buat pesan error untuk kasir.
     */
    private function rejectSupervisorAuthorization(string $throttleKey, string $message, int $status): JsonResponse
    {
        RateLimiter::hit($throttleKey, self::SUPERVISOR_PIN_LOCKOUT_SECONDS);

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
