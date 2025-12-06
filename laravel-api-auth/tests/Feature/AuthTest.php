<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);
    }

    public function test_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);
    }
}
