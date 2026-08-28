<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * All invoices platform-wide, including ones with no payment attempt at
     * all (e.g. abandoned right after the customer reached the hosted page,
     * before clicking Pay). The admin Payments list only shows AppUserPayment
     * records — an invoice like that never appears there, which is exactly
     * why "Total Invoices" and "Total Payments" on the dashboard don't match.
     */
    public function index(Request $request): View
    {
        $query = Invoice::with(['merchant', 'consumer']);

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->get('merchant_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('consumer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%");
                  });
            });
        }

        $sortBy = in_array($request->get('sort_by'), ['created_at', 'updated_at', 'total_fee', 'status']) ? $request->get('sort_by') : 'created_at';
        $sortDir = strtolower($request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $invoices = $query->orderBy($sortBy, $sortDir)->paginate($request->get('per_page', 15))->withQueryString();
        $merchants = Merchant::orderBy('name')->get(['id', 'name']);

        return view('admin.invoices.index', compact('invoices', 'merchants'));
    }
}
