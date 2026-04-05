<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'subscription_id' => $this->subscription_id,
            'amount'          => $this->amount,
            'currency'        => $this->currency->value,
            'status'          => $this->status->value,
            'transaction_id'  => $this->transaction_id,
            'metadata'        => $this->metadata,
            'paid_at'         => $this->paid_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
