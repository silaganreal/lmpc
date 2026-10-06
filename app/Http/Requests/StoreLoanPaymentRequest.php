<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'payment_method' => [
                'required',
                'string',
                'in:cash,salary_deduction,bank,check',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
