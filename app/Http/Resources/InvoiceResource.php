<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'total_fee' => $this->total_fee,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'merchant' => $this->merchant ? [
                'id' => $this->merchant->id,
                'name' => $this->merchant->name,
                'email' => $this->merchant->email,
            ] : null,
            'consumer' => $this->consumer ? [
                'id' => $this->consumer->id,
                'name' => $this->consumer->name,
                'email' => $this->consumer->email,
                'mobile_number' => $this->consumer->mobile_number,
            ] : null,
            'invoice_details' => $this->invoiceDetails->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'product' => $detail->product ? [
                        'id' => $detail->product->id,
                        'uuid' => $detail->product->uuid,
                        'name' => $detail->product->name,
                        'description' => $detail->product->description,
                        'fee' => $detail->product->fee,
                    ] : null,
                    'fee' => $detail->fee,
                    'title' => $detail->title,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
