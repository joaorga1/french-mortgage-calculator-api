<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MortgageCalculationTest extends TestCase
{
    /**
     * Test: Health check endpoint retorna status correto
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
     * Test: Calcular prestação com taxa fixa (duration_years)
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
        
        // Validar tipos e valores
        $this->assertIsFloat($data['monthly_payment']);
        $this->assertEquals(843.21, $data['monthly_payment']);
        $this->assertEquals(200000, $data['loan_amount']);
        $this->assertEquals(360, $data['duration_months']);
        $this->assertEquals(3, $data['annual_rate']);
    }

    /**
     * Test: Calcular prestação com taxa variável
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
     * Test: Validação - loan_amount é obrigatório
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
     * Test: Validação - loan_amount mínimo
     */
    public function test_validates_loan_amount_minimum(): void
    {
        $payload = [
            'loan_amount' => 4000, // Abaixo de 5.000
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
     * Test: Validação - loan_amount máximo
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
     * Test: Validação - duration é obrigatória
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
     * Test: Validação - duration_years mínimo
     */
    public function test_validates_duration_months_minimum(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 0, // Mínimo é 1
            'type' => 'fixed',
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duration_months']);
    }

    /**
     * Test: Validação - duration_years máximo
     */
    public function test_validates_duration_months_maximum(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 612, // Máximo é 480
            'type' => 'fixed',
            'rate' => 3.5,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duration_months']);
    }

    /**
     * Test: Validação - type é obrigatório
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
     * Test: Validação - type deve ser 'fixed' ou 'variable'
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
     * Test: Validação - taxa fixa requer campo 'rate'
     */
    public function test_validates_fixed_rate_requires_rate_field(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'type' => 'fixed',
            // 'rate' está ausente
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rate']);
    }

    /**
     * Test: Validação - taxa variável requer 'index_rate' e 'spread'
     */
    public function test_validates_variable_rate_requires_index_rate_and_spread(): void
    {
        $payload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'type' => 'variable',
            // 'index_rate' e 'spread' estão ausentes
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['index_rate', 'spread']);
    }

    /**
     * Test: Validação - rate não pode ser negativa
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
     * Test: Cálculo com valores extremos (empréstimo pequeno)
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
        $this->assertLessThan($payload['loan_amount'], $monthlyPayment * 12); // Juros baixos
    }

    /**
     * Test: Cálculo com valores extremos (empréstimo grande)
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
     * Test: Prestação maior para taxa maior (mesmas condições)
     */
    public function test_higher_rate_results_in_higher_payment(): void
    {
        $basePayload = [
            'loan_amount' => 200000,
            'duration_months' => 360,
            'type' => 'fixed',
        ];

        // Taxa baixa
        $response1 = $this->postJson('/api/mortgage/calculate', array_merge($basePayload, ['rate' => 2.0]));
        $payment1 = $response1->json('data.monthly_payment');

        // Taxa alta
        $response2 = $this->postJson('/api/mortgage/calculate', array_merge($basePayload, ['rate' => 5.0]));
        $payment2 = $response2->json('data.monthly_payment');

        $this->assertGreaterThan($payment1, $payment2);
    }
}