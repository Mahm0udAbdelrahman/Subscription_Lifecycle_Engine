<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'user_id'               => $this->user_id,
            'plan'                  => new PlanResource($this->whenLoaded('plan')),
            'status'                => $this->status->value,
            'status_label'          => $this->status->label(),
            'is_accessible'         => $this->isAccessible(),
            'billing_cycle'         => $this->billing_cycle->value,
            'currency'              => $this->currency->value,
            'price'                 => $this->price,
            'trial_ends_at'         => $this->trial_ends_at?->toIso8601String(),
            'starts_at'             => $this->starts_at?->toIso8601String(),
            'ends_at'               => $this->ends_at?->toIso8601String(),
            'canceled_at'           => $this->canceled_at?->toIso8601String(),
            'grace_period_ends_at'  => $this->grace_period_ends_at?->toIso8601String(),
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
