<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test registration validation errors
    */
    public function test_registration_requires_all_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_requires_valid_email()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password_confirmation()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_minimum_password_length()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_stores_hashed_password()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();

        // Password should be hashed (not in plain text)
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Test login validation errors
     */
    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_requires_valid_email_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_nonexistent_user()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test token functionality
     */
    public function test_logout_revokes_only_used_token()
    {
        $user = User::factory()->create();

        $user->createToken('device-1');
        $token2 = $user->createToken('device-2')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 2);

        // Logout with token2
        $this->withHeaders(['Authorization' => 'Bearer ' . $token2])
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        // Only 1 token remains (device-1)
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'device-1',
        ]);
    }

    public function test_logout_removes_token_from_database()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_cannot_access_protected_routes_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-123',
        ])->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_cannot_logout_twice_with_same_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // First logout
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // First logout
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        // Token was deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * Test response structure
     */
    public function test_register_returns_correct_token_type()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'token_type' => 'Bearer',
                ],
            ]);
    }

    public function test_me_endpoint_returns_user_timestamps()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                    ],
                ],
            ]);
    }
}
