<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Payment\RecordPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Subscription;
use App\Services\Api\PaymentService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    use HttpResponse;
    public function __construct(protected PaymentService $paymentService) {}

    /**
     * List payments for a subscription.
     */
    public function index(Subscription $subscription): AnonymousResourceCollection|JsonResponse
    {
        if ($subscription->user_id !== auth()->id()) {
            return $this->errorResponse(null, 403, 'Unauthorized.');
        }

        $payments = $subscription->payments()
            ->latest()
            ->paginate(15);
        
        return $this->paginatedResponse($payments, PaymentResource::class);
    }

    /**
     * Record a payment for a subscription.
     */
    public function store(RecordPaymentRequest $request, Subscription $subscription): JsonResponse
    {
        if ($subscription->user_id !== auth()->id()) {
            return $this->errorResponse(null, 403, 'Unauthorized.');
        }

        try {
            $payment = $this->paymentService->recordPayment(
                subscription:  $subscription,
                amount:        $request->amount,
                currency:      $request->currency,
                status:        PaymentStatus::from($request->status),
                metadata:      $request->metadata,
            );

            $subscription->refresh();

            return response()->json([
                'message' => 'Payment recorded successfully.',
                'data'    => [
                    'payment'      => new PaymentResource($payment),
                    'subscription' => [
                        'id'     => $subscription->id,
                        'status' => $subscription->status->value,
                        'grace_period_ends_at' => $subscription->grace_period_ends_at?->toIso8601String(),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse(null, 422, $e->getMessage());
        }
    }
}
