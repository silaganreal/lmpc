<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],

            'data_of_birth' => ['nullable', 'date'],
            'sex' => ['nullable', Rule::in(['male', 'female'])],

            'civil_status' => [
                'nullable',
                Rule::in([
                    'single',
                    'married',
                    'widowed',
                    'separated',
                    'divorced',
                ]),
            ],

            'nationality' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            
            'tin' => ['nullable', 'string', 'max:50'],

            'mobile_number' => ['nullable', 'string', 'max:30'],
            'telephone_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],

            'residence_type' => ['nullable', 'string', 'max:100'],

            'membership_type' => [
                'nullable',
                Rule::in(['regular', 'associate']),
            ],

            'date_joined' => ['nullable', 'date'],

            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'active',
                    'inactive',
                    'suspended',
                    'terminated',
                ])
            ]
        ];
    }
}
