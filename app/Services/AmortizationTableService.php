<?php

namespace App\Services;

use App\Models\Simulation;

class AmortizationTableService
{
    /**
     * Generate amortization table month by month
     */
    public function generate(Simulation $simulation): array
    {
        $table = [];

        $balance = (float) $simulation->loan_amount;
        $monthlyRate = ($simulation->annual_rate / 12) / 100;
        $n = $simulation->duration_months;

        if ($monthlyRate > 0) {
            $monthlyPayment = $balance * ($monthlyRate * pow(1 + $monthlyRate, $n)) / (pow(1 + $monthlyRate, $n) - 1);
        } else {
            $monthlyPayment = $balance / $n;
        }

        for ($month = 1; $month <= $n; $month++) {
            // Calcular com valores REAIS (precisão total)
            $interest = $balance * $monthlyRate;
            $principal = $monthlyPayment - $interest;
            $balance = $balance - $principal;

            // Arredondar APENAS para apresentação
            $table[] = [
                'month' => $month,
                'payment' => round($monthlyPayment, 2),
                'amortised' => round($principal, 2),
                'interest' => round($interest, 2),
                'balance' => round(max(0, $balance), 2),
            ];
        }

        return $table;
    }
}
