<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService extends Service
{
    public function getAllByMerchant(int $merchantId, array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc', int $perPage = 15)
    {
        $query = Product::where('merchant_id', $merchantId);

        // Apply state filter - default to 'active' if not specified
        if (isset($filters['state']) && $filters['state'] !== '') {
            // If state is explicitly set (active or archived), filter by it
            $query->where('state', $filters['state']);
        } elseif (!isset($filters['state'])) {
            // Default: show only active products when no filter is provided
            $query->where('state', 'active');
        }
        // If state is empty string (All Statuses selected), show all products

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        if (isset($filters['min_fee'])) {
            $query->where('fee', '>=', $filters['min_fee']);
        }

        if (isset($filters['max_fee'])) {
            $query->where('fee', '<=', $filters['max_fee']);
        }

        // Apply sorting
        $allowedSorts = ['name', 'fee', 'created_at', 'updated_at'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Apply pagination
        return $query->paginate($perPage)->withQueryString();
    }

    public function getById(int $id, ?int $merchantId = null): ?Product
    {
        $query = Product::where('id', $id);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->first();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function toggleState(Product $product): Product
    {
        $newState = $product->state === 'active' ? 'archived' : 'active';
        $product->update(['state' => $newState]);
        return $product->fresh();
    }

    public function getPaymentsForProduct(Product $product, ?int $merchantId = null): Collection
    {
        // Get all invoice details for this product
        $invoiceDetailIds = $product->invoiceDetails()->pluck('invoice_id');
        
        // Get all payments for invoices that contain this product
        $query = \App\Models\AppUserPayment::whereIn('invoice_id', $invoiceDetailIds)
            ->with(['invoice.consumer', 'invoice.invoiceDetails' => function ($q) use ($product) {
                $q->where('product_id', $product->id);
            }]);

        // Filter by merchant if provided
        if ($merchantId) {
            $query->whereHas('invoice', function ($q) use ($merchantId) {
                $q->where('merchant_id', $merchantId);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}

