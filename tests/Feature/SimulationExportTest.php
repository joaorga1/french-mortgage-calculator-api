<?php

namespace Tests\Feature;

use App\Models\Simulation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SimulationExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Simulation $simulation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->simulation = Simulation::factory()->create([
            'user_id' => $this->user->id,
            'loan_amount' => 100000,
            'duration_months' => 120,
        ]);
    }

    /**
     * Test: Export to CSV
     */
    public function test_export_to_csv(): void
    {
        $response = $this->getJson("/api/simulations/{$this->simulation->id}/export?format=csv");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', "attachment; filename=simulation_{$this->simulation->id}.csv");

        $content = $response->getContent();

        // Verify CSV headers
        $this->assertStringContainsString('Month,Payment,Amortised,Interest,Balance', $content);

        // Verify first line of data
        $this->assertStringContainsString('1,', $content);
    }

    /**
     * Test: Export to Excel
     */
    public function test_export_to_excel(): void
    {
        $response = $this->get("/api/simulations/{$this->simulation->id}/export?format=excel");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload("simulation_{$this->simulation->id}.xlsx");
    }

    /**
     * Test: Export defaults to CSV when format not specified
     */
    public function test_export_defaults_to_csv(): void
    {
        $response = $this->getJson("/api/simulations/{$this->simulation->id}/export");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test: Export rejects invalid format
     */
    public function test_export_rejects_invalid_format(): void
    {
        $response = $this->getJson("/api/simulations/{$this->simulation->id}/export?format=pdf");

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid format. Use csv or excel']);
    }

    /**
     * Test: User cannot export other user's simulation
     */
    public function test_user_cannot_export_other_user_simulation(): void
    {
        $otherUser = User::factory()->create();
        $otherSimulation = Simulation::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/simulations/{$otherSimulation->id}/export?format=csv");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);
    }
}
