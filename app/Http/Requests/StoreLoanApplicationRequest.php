<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
            ],

            'loan_product_id' => [
                'required',
                'integer',
                'exists:loan_products,id',
            ],

            'principal_amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'term_months' => [
                'required',
                'integer',
                'gt:0',
            ],

            'purpose' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
