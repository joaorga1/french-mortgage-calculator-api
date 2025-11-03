<?php

namespace App\Services;

use App\Models\Simulation;
use Illuminate\Support\Facades\DB;

class SimulationService
{
    public function __construct(
        private MortgageCalculatorService $calculator
    ) {
    }

    public function create(array $validated, int $userId): Simulation
    {
        DB::beginTransaction();

        try {
            $calculatedData = $this->calculator->getCalculatedData($validated);

            $totalAmount = $calculatedData['monthly_payment'] * $calculatedData['duration_months'];
            $totalInterest = $totalAmount - $calculatedData['loan_amount'];

            $simulation = Simulation::create([
                'user_id' => $userId,
                'loan_amount' => $calculatedData['loan_amount'],
                'duration_months' => $calculatedData['duration_months'],
                'rate_type' => $validated['type'],
                'annual_rate' => $calculatedData['annual_rate'],
                'index_rate' => $validated['index_rate'] ?? null,
                'spread' => $validated['spread'] ?? null,
                'monthly_payment' => $calculatedData['monthly_payment'],
                'total_amount' => round($totalAmount, 2),
                'total_interest' => round($totalInterest, 2),
            ]);

            DB::commit();

            return $simulation;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
