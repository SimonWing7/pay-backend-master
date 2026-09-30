<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductReminderController extends Controller
{
    public function store(Request $request, int $productId): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $product = Product::where('id', $productId)->where('merchant_id', $merchantId)->first();

        if (!$product) {
            abort(404, 'Product not found');
        }

        $validator = Validator::make($request->all(), [
            'send_date' => 'required|date|after_or_equal:today',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $product->reminders()->create($validator->validated());

        return redirect()->route('merchant.products.show', $product->id)
            ->with('success', 'Reminder scheduled.');
    }

    public function destroy(Request $request, int $productId, int $reminderId): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $product = Product::where('id', $productId)->where('merchant_id', $merchantId)->first();

        if (!$product) {
            abort(404, 'Product not found');
        }

        $reminder = ProductReminder::where('id', $reminderId)->where('product_id', $product->id)->first();

        if (!$reminder) {
            abort(404, 'Reminder not found');
        }

        if ($reminder->sent_at) {
            return redirect()->route('merchant.products.show', $product->id)
                ->with('error', 'This reminder has already been sent and cannot be removed.');
        }

        $reminder->delete();

        return redirect()->route('merchant.products.show', $product->id)
            ->with('success', 'Reminder removed.');
    }
}
