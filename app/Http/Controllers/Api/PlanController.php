<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PlanResource;
use App\Traits\HttpResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Plan\StorePlanRequest;
use App\Http\Requests\Api\Plan\UpdatePlanRequest;
use App\Models\Plan;
use App\Services\Api\PlanService;

class PlanController extends Controller
{
    use   HttpResponse;
    public function __construct(public PlanService $planService) {}

    public function index()
    {
        $plans = $this->planService->getAllPlans();
        return $this->paginatedResponse($plans, PlanResource::class);
    }

    public function store(StorePlanRequest $request)
    {
        $plan = $this->planService->createPlan($request->validated());
        return $this->createdResponse(PlanResource::make($plan), __('Plan created successfully', [], request()->header('Accept-language')));
    }

    public function show(Plan $plan)
    {
        $plan = $this->planService->getPlan($plan);
        return $this->okResponse(PlanResource::make($plan), __('Plan retrieved successfully', [], request()->header('Accept-language')));
    }

    public function update(Plan $plan, UpdatePlanRequest $request)
    {
        $plan = $this->planService->updatePlan($plan, $request->validated());
        return $this->okResponse(PlanResource::make($plan), __('Plan updated successfully', [], request()->header('Accept-language')));
    }

    public function destroy(Plan $plan)
    {
        $this->planService->deactivatePlan($plan);
        return $this->okResponse(null, __('Plan deactivated successfully.', [], request()->header('Accept-language')));
    }
}
