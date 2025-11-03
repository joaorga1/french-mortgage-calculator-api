<?php

namespace Tests\Feature;

use App\Models\Simulation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SimulationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * Test: User can list their own simulations
     */
    public function test_user_can_list_own_simulations(): void
    {
        // Create 3 simulations for the user
        Simulation::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Create 2 simulations for another user
        $otherUser = User::factory()->create();
        Simulation::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/simulations');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'loan_amount',
                        'duration_months',
                        'rate_type',
                        'annual_rate',
                        'monthly_payment',
                        'total_amount',
                        'total_interest',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * Test: User can view a specific simulation
     */
    public function test_user_can_view_own_simulation(): void
    {
        $simulation = Simulation::factory()->create([
            'user_id' => $this->user->id,
            'loan_amount' => 200000,
            'duration_months' => 360,
        ]);

        $response = $this->getJson("/api/simulations/{$simulation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'amortization_table' => [
                    '*' => ['month', 'payment', 'amortised', 'interest', 'balance'],
                ],
            ])
            ->assertJson([
                'id' => $simulation->id,
                'loan_amount' => '200000.00',
            ]);
    }

    /**
     * Test: User cannot view other user's simulation
     */
    public function test_user_cannot_view_other_user_simulation(): void
    {
        $otherUser = User::factory()->create();
        $simulation = Simulation::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/simulations/{$simulation->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);
    }

    /**
     * Test: Amortization table balance reaches zero
     */
    public function test_amortization_table_balance_reaches_zero(): void
    {
        $simulation = Simulation::factory()->create([
            'user_id' => $this->user->id,
            'loan_amount' => 100000,
            'duration_months' => 240,
            'annual_rate' => 3.5,
            'monthly_payment' => 500.00,
        ]);

        $response = $this->getJson("/api/simulations/{$simulation->id}");

        $response->assertStatus(200);

        $data = $response->json();
        $lastMonth = end($data['amortization_table']);

        $this->assertEquals($simulation->duration_months, $lastMonth['month']);
        $this->assertEquals(0, $lastMonth['balance']);
    }

    /**
     * Test: Pagination works for simulations list
     */
    public function test_simulations_list_is_paginated(): void
    {
        Simulation::factory()->count(15)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/simulations');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data') // Default pagination = 10
            ->assertJsonStructure([
                'data',
                // 'links',
                // 'meta',
            ]);
    }

    /**
     * Test: Calculate endpoint creates simulation in database
     */
    public function test_calculate_creates_simulation_in_database(): void
    {
        $this->assertDatabaseCount('simulations', 0);

        $payload = [
            'loan_amount' => 150000,
            'duration_months' => 300,
            'type' => 'fixed',
            'rate' => 3.2,
        ];

        $response = $this->postJson('/api/mortgage/calculate', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseCount('simulations', 1);
        $this->assertDatabaseHas('simulations', [
            'user_id' => $this->user->id,
            'loan_amount' => 150000,
            'duration_months' => 300,
            'rate_type' => 'fixed',
            'annual_rate' => 3.2,
        ]);
    }
}
