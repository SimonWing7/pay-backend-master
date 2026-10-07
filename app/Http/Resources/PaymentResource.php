<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get transaction_id from nymcard_metadata
        $nymcardMetadata = $this->nymcard_metadata ?? [];
        $transactionId = $nymcardMetadata['transaction_id'] ?? null;

        // Get customer name (consumer name)
        $customerName = $this->invoice && $this->invoice->consumer
            ? $this->invoice->consumer->name
            : null;

        // Get product names from invoice details
        $productNames = [];
        if ($this->invoice && $this->invoice->invoiceDetails) {
            $productNames = $this->invoice->invoiceDetails
                ->map(function ($detail) {
                    return $detail->product ? $detail->product->name : ($detail->title ?? 'N/A');
                })
                ->filter()
                ->values()
                ->toArray();
        }

        // Get amount (total fee)
        $amount = $this->invoice ? $this->invoice->total_fee : 0;

        // Get status as string
        // The mobile app only knows Initiated/Complete/Failed — report an
        // abandoned checkout as Failed so its existing handling still works.
        $status = $this->status === \App\Enums\PaymentStatus::Abandoned ? 'Failed' : $this->status->label();

        return [
            'id' => $this->id,
            'reference' => $transactionId,
            'customer_name' => $customerName,
            'product_name' => !empty($productNames) ? implode(', ', $productNames) : 'N/A',
            'amount' => $amount,
            'status' => $status,
        ];
    }
}
