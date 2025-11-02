<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MortgageCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Setup: create authenticated user before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create user and authenticate with Sanctum
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * Test: Health check endpoint returns correct status
     */
    public function test_health_endpoint_returns_ok_status(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'service',
                'timestamp',
            ])
            ->assertJson([
                'status' => 'ok',
                'service' => 'mortgage-calculator-api',
            ]);
    }

    /**
     * Test: Calculate mortgage with fixed rate (duration_years)
     */
    public function test_calculates_mortgage_with_fixed_rate_and_duration(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'type' => 'fixed',
            'rate' => 3,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'monthly_payment',
                    'loan_amount',
                    'duration_months',
                    'annual_rate',
                    'method',
                    'currency',
                    'metadata' => [
                        'calculation_date',
                        'formula',
                    ],
                ],
            ]);

        $data = $response->json('data');

        // Validate types and values
        $this->assertIsFloat($data['monthly_payment']);
        $this->assertEquals(843.21, $data['monthly_payment']);
        $this->assertEquals(200000, $data['loan_amount']);
        $this->assertEquals(360, $data['duration_months']);
        $this->assertEquals(3, $data['annual_rate']);
    }

    /**
     * Test: Calculate mortgage with variable rate
     */
    public function test_calculates_mortgage_with_variable_rate(): void
    {
        $payload = [
            'loan_amount' => 180000,
            'duration_months' => 300,
            'type' => 'variable',
            'index_rate' => 2.5,
            'spread' => 1.2,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'loan_amount' => 180000,
                    'duration_months' => 300,
                    'annual_rate' => 3.7, // 2.5 + 1.2
                ],
            ]);
    }

    /**
     * Test: Validation - loan_amount is required
     */
    public function test_validates_loan_amount_is_required(): void
    {
        $payload = [
            'duration_months' => 360,
            'type' => 'fixed',
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['loan_amount']);
    }

    /**
     * Test: Validation - loan_amount minimum
     */
    public function test_validates_loan_amount_minimum(): void
    {
        $payload = [
            'loan_amount' => 4000, // Below 5.000
            'duration_months' => 360,
            'type' => 'fixed',
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['loan_amount']);
    }

    /**
     * Test: Validation - loan_amount maximum
     */
    public function test_validates_loan_amount_maximum(): void
    {
        $payload = [
            'loan_amount' => 15000000,
            'duration_months' => 360,
            'type' => 'fixed',
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['loan_amount']);
    }

    /**
     * Test: Validation - duration is required
     */
    public function test_validates_duration_is_required(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'type' => 'fixed',
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duration_months']);
    }

    /**
     * Test: Validation - duration_years minimum
     */
    public function test_validates_duration_months_minimum(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 0, // Minimum is 1
            'type' => 'fixed',
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duration_months']);
    }

    /**
     * Test: Validation - duration_years maximum
     */
    public function test_validates_duration_months_maximum(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 612, // Maximum is 480
            'type' => 'fixed',
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duration_months']);
    }

    /**
     * Test: Validation - type is required
     */
    public function test_validates_type_is_required(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * Test: Validation - type must be 'fixed' or 'variable'
     */
    public function test_validates_type_must_be_valid(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'type' => 'invalid_type',
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * Test: Validation - fixed rate requires 'rate' field
     */
    public function test_validates_fixed_rate_requires_rate_field(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'type' => 'fixed',
            // 'rate' is missing
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rate']);
    }

    /**
     * Test: Validation - variable rate requires 'index_rate' and 'spread'
     */
    public function test_validates_variable_rate_requires_index_rate_and_spread(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'type' => 'variable',
            // 'index_rate' and 'spread' are missing
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['index_rate', 'spread']);
    }

    /**
     * Test: Validation - rate cannot be negative
     */
    public function test_validates_rate_cannot_be_negative(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'type' => 'fixed',
            'rate' => -1.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rate']);
    }

    /**
     * Test: Calculation with extreme values (small loan)
     */
    public function test_calculates_with_minimum_values(): void
    {
        $payload = [
            'loan_amount' => 5000,
            'duration_months' => 60,
            'type' => 'fixed',
            'rate' => 0.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response->assertStatus(200);

        $monthlyPayment = $response->json('data.monthly_payment');
        $this->assertGreaterThan(0, $monthlyPayment);
        $this->assertLessThan($payload['loan_amount'], $monthlyPayment * 12); // Low interest
    }

    /**
     * Test: Calculation with extreme values (large loan)
     */
    public function test_calculates_with_maximum_values(): void
    {
        $payload = [
            'loan_amount' => 1000000,
            'duration_months' => 480,
            'type' => 'fixed',
            'rate' => 6.0,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response->assertStatus(200);

        $monthlyPayment = $response->json('data.monthly_payment');
        $this->assertGreaterThan(0, $monthlyPayment);
    }

    /**
     * Test: Higher payment for higher rate (same conditions)
     */
    public function test_higher_rate_results_in_higher_payment(): void
    {
        $basePayload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'type' => 'fixed',
        ];

        // Low rate
        $response1 = $this->postJson('/api/mortgage/calculate', array_merge($basePayload, ['rate' => 2.0]));
        $payment1 = $response1->json('data.monthly_payment');

        // High rate
        $response2 = $this->postJson('/api/mortgage/calculate', array_merge($basePayload, ['rate' => 5.0]));
        $payment2 = $response2->json('data.monthly_payment');

        $this->assertGreaterThan($payment1, $payment2);
    }
}
