<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends FormRequest
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
            'account_code' => [
                'nullable',
                'string',
                'max:50',
                'unique:chart_of_accounts,account_code',
            ],
            'account_name' => [
                'required',
                'string',
                'max:150',
                'unique:chart_of_accounts,account_name',
            ],
            'account_type' => [
                'required',
                Rule::in([
                    'asset',
                    'liability',
                    'equity',
                    'income',
                    'expense',
                ]),
            ],
            'normal_balance' => [
                'required',
                Rule::in(['debit', 'credit']),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_of_accounts', 'id')
                    ->where('account_type', $this->input('account_type'))
                    ->where('is_active', true),
            ],
            'is_posting_allowed' => [
                'required',
                'boolean',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
