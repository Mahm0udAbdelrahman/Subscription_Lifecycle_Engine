<?php

namespace App\Http\Requests\Api\Subscription;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;


class StoreSubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id'        => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle'  => ['required', Rule::enum(BillingCycle::class)],
            'currency'       => ['required', Rule::enum(Currency::class)],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => $validator->errors()->first(),
                'type' => 'error',
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
