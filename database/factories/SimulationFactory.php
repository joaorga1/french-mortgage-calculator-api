<?php

namespace Database\Factories;

use App\Models\Simulation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SimulationFactory extends Factory
{
    protected $model = Simulation::class;

    public function definition(): array
    {
        $loanAmount = $this->faker->numberBetween(50000, 500000);
        $durationMonths = $this->faker->randomElement([120, 180, 240, 300, 360]);
        $annualRate = $this->faker->randomFloat(2, 2, 6);
        $monthlyRate = ($annualRate / 12) / 100;

        // Calculate correct monthly_payment
        $monthlyPayment = $loanAmount * ($monthlyRate * pow(1 + $monthlyRate, $durationMonths)) / (pow(1 + $monthlyRate, $durationMonths) - 1);
        $totalAmount = $monthlyPayment * $durationMonths;
        $totalInterest = $totalAmount - $loanAmount;

        return [
            'user_id' => User::factory(),
            'loan_amount' => $loanAmount,
            'duration_months' => $durationMonths,
            'rate_type' => $this->faker->randomElement(['fixed', 'variable']),
            'annual_rate' => $annualRate,
            'index_rate' => null,
            'spread' => null,
            'monthly_payment' => round($monthlyPayment, 2),
            'total_amount' => round($totalAmount, 2),
            'total_interest' => round($totalInterest, 2),
        ];
    }

    /**
     * Variable rate simulation
     */
    public function variable(): static
    {
        return $this->state(function (array $attributes) {
            $indexRate = $this->faker->randomFloat(2, 1, 4);
            $spread = $this->faker->randomFloat(2, 0.5, 2);
            $annualRate = $indexRate + $spread;

            $monthlyRate = ($annualRate / 12) / 100;
            $monthlyPayment = $attributes['loan_amount'] * ($monthlyRate * pow(1 + $monthlyRate, $attributes['duration_months'])) / (pow(1 + $monthlyRate, $attributes['duration_months']) - 1);
            $totalAmount = $monthlyPayment * $attributes['duration_months'];

            return [
                'rate_type' => 'variable',
                'annual_rate' => $annualRate,
                'index_rate' => $indexRate,
                'spread' => $spread,
                'monthly_payment' => round($monthlyPayment, 2),
                'total_amount' => round($totalAmount, 2),
                'total_interest' => round($totalAmount - $attributes['loan_amount'], 2),
            ];
        });
    }
}
