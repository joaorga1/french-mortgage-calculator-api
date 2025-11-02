<?php

namespace Tests\Unit;

use App\Services\MortgageCalculatorService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MortgageCalculatorServiceTest extends TestCase
{
    private MortgageCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new MortgageCalculatorService();
    }

    public function test_calculates_monthly_payment_correctly(): void
    {
        $result = $this->calculator->calculateMonthlyPayment(
            loanAmount: 200000,
            durationMonths: 360,
            annualRate: 3.0
        );

        $this->assertEquals(843.21, $result);
    }

    public function test_throws_exception_for_negative_loan_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Loan amount must be greater than zero');

        $this->calculator->calculateMonthlyPayment(
            loanAmount: -10000,
            durationMonths: 360,
            annualRate: 3.0
        );
    }

    public function test_throws_exception_for_zero_loan_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Loan amount must be greater than zero');

        $this->calculator->calculateMonthlyPayment(
            loanAmount: 0,
            durationMonths: 360,
            annualRate: 3.0
        );
    }

    public function test_throws_exception_for_zero_duration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration must be greater than zero');

        $this->calculator->calculateMonthlyPayment(
            loanAmount: 200000,
            durationMonths: 0,
            annualRate: 3.0
        );
    }

    public function test_throws_exception_for_negative_duration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration must be greater than zero');

        $this->calculator->calculateMonthlyPayment(
            loanAmount: 200000,
            durationMonths: -12,
            annualRate: 3.0
        );
    }

    public function test_throws_exception_for_negative_rate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Annual rate cannot be negative');

        $this->calculator->calculateMonthlyPayment(
            loanAmount: 200000,
            durationMonths: 360,
            annualRate: -3.0
        );
    }

    public function test_higher_loan_amount_means_higher_payment(): void
    {
        $paymentSmallLoan = $this->calculator->calculateMonthlyPayment(
            loanAmount: 50000,
            durationMonths: 360,
            annualRate: 3.0
        );
        $paymentLargeLoan = $this->calculator->calculateMonthlyPayment(
            loanAmount: 500000,
            durationMonths: 360,
            annualRate: 3.0
        );
        $this->assertGreaterThan($paymentSmallLoan, $paymentLargeLoan);
    }

    public function test_higher_rate_means_higher_payment(): void
    {
        $payment3Percent = $this->calculator->calculateMonthlyPayment(
            loanAmount: 200000,
            durationMonths: 360,
            annualRate: 3.0
        );

        $payment5Percent = $this->calculator->calculateMonthlyPayment(
            loanAmount: 200000,
            durationMonths: 360,
            annualRate: 5.0
        );

        $this->assertGreaterThan($payment3Percent, $payment5Percent);
    }
}