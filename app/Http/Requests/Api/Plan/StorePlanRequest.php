<?php

namespace App\Http\Requests\Api\Plan;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'trial_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],

            'prices'                  => ['required', 'array', 'min:1'],
            'prices.*.billing_cycle'  => ['required', 'string', Rule::in(array_column(BillingCycle::cases(), 'value'))],
            'prices.*.currency'       => ['required', 'string', Rule::in(array_column(Currency::cases(), 'value'))],
            'prices.*.price'          => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'prices.required'                  => 'At least one price entry is required.',
            'prices.*.billing_cycle.required'   => 'Each price must specify a billing cycle.',
            'prices.*.billing_cycle.in'        => 'Billing cycle must be one of: monthly, yearly.',
            'prices.*.currency.required'       => 'Each price must specify a currency.',
            'prices.*.currency.in'             => 'Currency must be one of: AED, USD, EGP.',
            'prices.*.price.required'          => 'Each price must have an amount.',
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
