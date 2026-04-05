<?php

namespace App\Http\Controllers\Api;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\SubscriptionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Subscription\StoreSubscribeRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Api\SubscriptionService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionController extends Controller
{
    use   HttpResponse;
    public function __construct(protected SubscriptionService $subscriptionService,) {}


    public function index(): JsonResponse
    {
        $subscriptions = auth()->user()
            ->subscriptions()
            ->with('plan.prices')
            ->latest()
            ->paginate(15);
        return $this->paginatedResponse($subscriptions, SubscriptionResource::class);
    }

    /**
     * Subscribe to a plan.
     */
    public function store(StoreSubscribeRequest $request): JsonResponse
    {
        $plan = Plan::findOrFail($request->plan_id);
        $billingCycle = BillingCycle::from($request->billing_cycle);
        $currency = Currency::from($request->currency);

        try {
            $subscription = $this->subscriptionService->subscribe(
                auth()->user(),
                $plan,
                $billingCycle,
                $currency
            );

            return $this->createdResponse(new SubscriptionResource($subscription->load('plan.prices')));
        } catch (SubscriptionException $e) {
            return $this->errorResponse(null, $e->getCode(), $e->getMessage());
        }
    }


    public function show(Subscription $subscription): SubscriptionResource|JsonResponse
    {
        if ($subscription->user_id !== auth()->id()) {
            return $this->errorResponse(null, 403, 'Unauthorized.');
        }

        return $this->successResponse(new SubscriptionResource($subscription->load('plan.prices')));
    }


    public function cancel(Subscription $subscription): JsonResponse
    {
        if ($subscription->user_id !== auth()->id()) {
            return $this->errorResponse(null, 403, 'Unauthorized.');
        }

        try {
            $subscription = $this->subscriptionService->cancel($subscription);

            return $this->successResponse(new SubscriptionResource($subscription->load('plan.prices')));
        } catch (InvalidStateTransitionException $e) {
            return $this->errorResponse(null, 422, $e->getMessage());
        }
    }
}
