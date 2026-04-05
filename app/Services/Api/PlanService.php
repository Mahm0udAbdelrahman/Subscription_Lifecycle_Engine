<?php

namespace App\Services\Api;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public function getAllPlans()
    {
        return Plan::active()
            ->with(['prices' => fn ($q) => $q->active()])
            ->latest()
            ->paginate(15);
    }

    public function createPlan(array $data): Plan
    {
        return DB::transaction(function () use ($data) {

            $plan = Plan::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'trial_period_days' => $data['trial_period_days'] ?? 0,
            ]);

            foreach ($data['prices'] as $price) {
                $plan->prices()->create($price);
            }

            return $plan->load('prices');
        });
    }

    public function getPlan(Plan $plan): Plan
    {
        return $plan->load('prices');
    }

    public function updatePlan(Plan $plan, array $data): Plan
    {
        DB::transaction(function () use ($plan, $data) {

            $plan->update([
                'name' => $data['name'] ?? $plan->name,
                'description' => $data['description'] ?? $plan->description,
                'trial_period_days' => $data['trial_period_days'] ?? $plan->trial_period_days,
                'is_active' => $data['is_active'] ?? $plan->is_active,
            ]);

            if (isset($data['prices'])) {
                $plan->prices()->delete();

                foreach ($data['prices'] as $price) {
                    $plan->prices()->create($price);
                }
            }
        });

        return $plan->fresh('prices');
    }

    public function deactivatePlan(Plan $plan): void
    {
        $plan->update(['is_active' => false]);
    }
}