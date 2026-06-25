<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentInitiationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->nymcard_token ?? $this->token,
            'user_id' => $this->appUser->uuid,
            'payment_id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'resource_id' => $this->nymcard_resource_id,
        ];
    }
}
