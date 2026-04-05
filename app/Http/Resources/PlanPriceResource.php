<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'billing_cycle' => $this->billing_cycle->value,
            'currency'      => $this->currency->value,
            'price'         => $this->price,
            'is_active'     => $this->is_active,
        ];
    }
}
