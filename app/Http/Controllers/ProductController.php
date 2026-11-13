<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the products catalog.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();

        $categories = Product::distinct()->pluck('category')->filter()->values();

        if ($request->wantsJson()) {
            return response()->json($products);
        }

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'low_stock']),
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:64', 'unique:products,barcode'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product = Product::create($validated);

        ActivityLog::record('product.created', "Menambahkan produk {$product->name} ({$product->barcode})", $product, [
            'selling_price' => (float) $product->selling_price,
            'stock' => $product->stock,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Produk '{$product->name}' berhasil ditambahkan.",
                'product' => $product,
            ], 201);
        }

        return redirect()->back()->with('success', "Produk '{$product->name}' berhasil ditambahkan ke katalog.");
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update($validated);

        ActivityLog::record('product.updated', "Mengubah produk {$product->name} ({$product->barcode})", $product, ActivityLog::changesOf($product));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Produk '{$product->name}' berhasil diperbarui.",
                'product' => $product,
            ]);
        }

        return redirect()->back()->with('success', "Produk '{$product->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified product from storage (or deactivate if it has sale history).
     */
    public function destroy(Product $product): RedirectResponse|JsonResponse
    {
        if ($product->transactionItems()->exists()) {
            $product->update(['is_active' => false]);
            $msg = "Produk '{$product->name}' telah dinonaktifkan karena memiliki riwayat transaksi.";

            ActivityLog::record('product.deactivated', "Menonaktifkan produk {$product->name} (punya riwayat transaksi)", $product);
        } else {
            $name = $product->name;
            $product->delete();
            $msg = "Produk '{$name}' berhasil dihapus.";

            ActivityLog::record('product.deleted', "Menghapus produk {$name}", $product, ['name' => $name, 'barcode' => $product->barcode]);
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }
}
