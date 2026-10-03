<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'rafael@teste.com',
            'password' => '12345678',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'rafael@teste.com',
            'password' => '12345678',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'token_type',
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'rafael@teste.com',
            'password' => '12345678',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'rafael@teste.com',
            'password' => 'senha-errada',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Credenciais inválidas.',
            ]);
    }

    public function test_rooms_requires_authentication(): void
    {
        $response = $this->getJson('/api/rooms');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_rooms(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/rooms');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        RateLimiter::clear('127.0.0.1');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->postJson('/api/login', [
                'email' => 'usuario-inexistente@teste.com',
                'password' => 'senha-errada',
            ]);

            $response->assertStatus(401);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'usuario-inexistente@teste.com',
            'password' => 'senha-errada',
        ]);

        $response->assertStatus(429);
    }
}
