<?php

namespace Tests\Unit;

use App\Models\Simulation;
use App\Services\AmortizationTableService;
use Tests\TestCase;

class AmortizationTableServiceTest extends TestCase
{
    private AmortizationTableService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AmortizationTableService();
    }

    /**
     * Test: Generate table with correct number of months
     */
    public function test_generates_correct_number_of_months(): void
    {
        $simulation = new Simulation([
            'loan_amount' => 100000,
            'duration_months' => 120,
            'annual_rate' => 3.6,
            'monthly_payment' => 1000,
        ]);

        $table = $this->service->generate($simulation);

        $this->assertCount(120, $table);
        $this->assertEquals(1, $table[0]['month']);
        $this->assertEquals(120, $table[119]['month']);
    }

    /**
     * Test: Balance reaches zero at the end
     */
    public function test_balance_reaches_zero(): void
    {
        $simulation = new Simulation([
            'loan_amount' => 50000,
            'duration_months' => 60,
            'annual_rate' => 4.0,
            'monthly_payment' => 920.41,
        ]);

        $table = $this->service->generate($simulation);

        $lastMonth = end($table);

        $this->assertEquals(0, $lastMonth['balance']);
    }

    /**
     * Test: First month has maximum balance
     */
    public function test_first_month_has_highest_balance(): void
    {
        $simulation = new Simulation([
            'loan_amount' => 80000,
            'duration_months' => 240,
            'annual_rate' => 3.5,
            'monthly_payment' => 500,
        ]);

        $table = $this->service->generate($simulation);

        $this->assertGreaterThan($table[1]['balance'], $table[0]['balance']);
        $this->assertGreaterThan($table[100]['balance'], $table[50]['balance']);
    }

    /**
     * Test: Payment is constant throughout
     */
    public function test_payment_is_constant(): void
    {
        $simulation = new Simulation([
            'loan_amount' => 100000,
            'duration_months' => 180,
            'annual_rate' => 3.8,
            'monthly_payment' => 750,
        ]);

        $table = $this->service->generate($simulation);

        $payments = array_column($table, 'payment');
        $uniquePayments = array_unique($payments);

        $this->assertCount(1, $uniquePayments, 'Payment should be constant');
    }

    /**
     * Test: Interest decreases over time
     */
    public function test_interest_decreases_over_time(): void
    {
        $simulation = new Simulation([
            'loan_amount' => 100000,
            'duration_months' => 120,
            'annual_rate' => 4.0,
            'monthly_payment' => 1000,
        ]);

        $table = $this->service->generate($simulation);

        $this->assertGreaterThan($table[50]['interest'], $table[10]['interest']);
        $this->assertGreaterThan($table[100]['interest'], $table[50]['interest']);
    }
}
