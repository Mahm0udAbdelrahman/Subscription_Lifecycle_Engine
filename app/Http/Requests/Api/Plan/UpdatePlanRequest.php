<?php

namespace App\Http\Requests\Api\Plan;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;


class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['sometimes', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'trial_period_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'is_active'         => ['sometimes', 'boolean'],

            'prices'                  => ['sometimes', 'array', 'min:1'],
            'prices.*.billing_cycle'  => ['required_with:prices', 'string', Rule::in(array_column(BillingCycle::cases(), 'value'))],
            'prices.*.currency'       => ['required_with:prices', 'string', Rule::in(array_column(Currency::cases(), 'value'))],
            'prices.*.price'          => ['required_with:prices', 'numeric', 'min:0.01', 'max:999999.99'],
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
