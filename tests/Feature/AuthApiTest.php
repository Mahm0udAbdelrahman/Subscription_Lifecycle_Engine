<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use Illuminate\Support\Facades\Http;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'v3.api.beon.chat/*' => Http::response(['message' => 'OTP sent'], 200),
        ]);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'phone'                 => '1234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'fcm_token'             => 'sample-fcm-token',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'phone'],
                    'otp',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'phone', 'fcm_token']);
    }

    public function test_register_validates_unique_email(): void
    {
        $email = 'john@example.com';
        User::factory()->create([
            'email' => $email,
            'phone' => '1234567890',
        ]);

        $response = $this->postJson('/api/register', [
            'name'                  => 'John Doe',
            'email'                 => $email,
            'phone'                 => '0987654321',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'fcm_token'             => 'sample-token',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'phone'    => '1234567890',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'phone'     => '1234567890',
            'password'  => 'password123',
            'fcm_token' => 'sample-fcm-token',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['user', 'token'],
            ]);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create([
            'phone'    => '1234567890',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'phone'    => '1234567890',
            'password' => 'wrongpassword',
            'fcm_token' => 'sample-token',
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'logout');

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_unauthenticated_cannot_access_protected_routes(): void
    {
        $response = $this->postJson('/api/logout');
        $response->assertUnauthorized();

        $response = $this->getJson('/api/subscriptions');
        $response->assertUnauthorized();
    }
}
