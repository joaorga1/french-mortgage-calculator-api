<?php

namespace App\Services;

use InvalidArgumentException;

class MortgageCalculatorService
{
    public function getCalculatedData(array $data): array
    {
        $annualRate = $this->calculateRate($data);

        $monthlyPayment = $this->calculateMonthlyPayment(
            loanAmount: $data['loan_amount'],
            durationMonths: $data['duration_months'],
            annualRate: $annualRate
        );

        $result = [
            'monthly_payment' => $monthlyPayment,
            'loan_amount' => $data['loan_amount'],
            'duration_months' => $data['duration_months'],
            'annual_rate' => $annualRate,
        ];

        return $result;
    }
    /**
     * Calcula a prestação mensal usando amortização francesa
     */
    public function calculateMonthlyPayment(
        float $loanAmount,
        int $durationMonths,
        float $annualRate
    ): float {
        if ($loanAmount <= 0) {
            throw new InvalidArgumentException('Loan amount must be greater than zero');
        }
    
        if ($durationMonths <= 0) {
            throw new InvalidArgumentException('Duration must be greater than zero');
        }
    
        if ($annualRate < 0) {
            throw new InvalidArgumentException('Annual rate cannot be negative');
        }

        // Caso especial: taxa 0%
        if ($annualRate == 0) {
            return round($loanAmount / $durationMonths, 2);
        }
        // Calcular taxa mensal
        $monthlyRate = ($annualRate / 12) / 100;

        // Fórmula francesa: M = P * [i(1 + i)^n] / [(1 + i)^n - 1]
        $factor = pow(1 + $monthlyRate, $durationMonths);
        $monthlyPayment = $loanAmount * ($monthlyRate * $factor) / ($factor - 1);

        return round($monthlyPayment, 2);
    }

    public function calculateRate(array $data): float
    {
        // Validações básicas (FormRequest já validou os detalhes)
        if (!isset($data['type'])) {
            throw new InvalidArgumentException('Rate type is required');
        }

        $annualRate = match ($data['type']) {
            'fixed' => $data['rate'] ?? throw new InvalidArgumentException('Rate is required for fixed type'),
            'variable' => ($data['index_rate'] ?? throw new InvalidArgumentException('Index rate is required'))
                        + ($data['spread'] ?? throw new InvalidArgumentException('Spread is required')),
            default => throw new InvalidArgumentException('Invalid rate type'),
        };

        return round($annualRate, 2);
    }
}
