<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalculateMortgageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // API pública
    }

    public function rules(): array
    {
        return [
            'loan_amount' => [
                'required',
                'numeric',
                'min:5000',
                'max:10000000',
            ],
            'duration_months' => [
                'required',
                'integer',
                'min:60', // 5 years
                'max:480', // 40 years
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['fixed', 'variable']),
            ],
            'rate' => [
                'required_if:type,fixed',
                'numeric',
                'min:0',
                'max:100',
            ],
            'index_rate' => [
                'required_if:type,variable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'spread' => [
                'required_if:type,variable',
                'numeric',
                'min:0',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'loan_amount.required' => 'Loan amount is required',
            'loan_amount.numeric' => 'Loan amount must be a number',
            'loan_amount.min' => 'Loan amount must be greater than 5000€',
            'loan_amount.max' => 'Loan amount must be less than 10000000€',

            'duration_months.required' => 'Duration is required',
            'duration_months.integer' => 'Duration must be an integer',
            'duration_months.min' => 'Duration must be greater than 60 months',
            'duration_months.max' => 'Duration must be less than 480 months',

            'type.required' => 'Type is required',
            'type.in' => 'Type must be "fixed" or "variable"',

            'rate.required_if' => 'Rate is required for type "fixed"',
            'rate.numeric' => 'Rate must be a number',
            'rate.min' => 'Rate must be greater than 0%',
            'rate.max' => 'Rate must be less than 100%',

            'index_rate.required_if' => 'Index rate is required for type "variable"',
            'index_rate.numeric' => 'Index rate must be a number',
            'index_rate.min' => 'Index rate must be greater than 0%',
            'index_rate.max' => 'Index rate must be less than 100%',

            'spread.required_if' => 'Spread is required for type "variable"',
            'spread.numeric' => 'Spread must be a number',
            'spread.min' => 'Spread must be greater than 0%',
            'spread.max' => 'Spread must be less than 100%',
        ];
    }
}
