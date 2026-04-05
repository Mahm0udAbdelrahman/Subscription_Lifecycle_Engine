<?php

namespace App\Http\Requests\Api\Payment;

use App\Enums\Currency;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;


class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'currency'       => ['required', 'string', Rule::in(array_column(Currency::cases(), 'value'))],
            'status'         => ['required', 'string', Rule::in(array_column(PaymentStatus::cases(), 'value'))],
            'metadata'       => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Payment status must be one of: succeeded, failed, refunded.',
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
