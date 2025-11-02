<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MortgageCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'monthly_payment' => $this['monthly_payment'],
            'loan_amount' => $this['loan_amount'],
            'duration_months' => $this['duration_months'],
            'annual_rate' => $this['annual_rate'],
            'method' => 'french_amortization',
            'currency' => 'EUR',
            'metadata' => [
                'calculation_date' => now()->toIso8601String(),
                'formula' => 'M = P * [i(1 + i)^n] / [(1 + i)^n - 1]',
            ],
        ];
    }
}
