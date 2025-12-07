<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_api_key()
    {
        $user = User::factory()->create();
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/keys/create', [
                'name' => 'Test Key',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'key',
                'api_key' => ['id', 'name'],
            ]);
    }

    public function test_user_can_list_api_keys()
    {
        $user = User::factory()->create();
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
        ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Existing Key',
            'key' => hash('sha256', 'some-key'),
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/api-keys');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_user_can_delete_api_key()
    {
        $user = User::factory()->create();
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Key to Delete',
            'key' => hash('sha256', 'some-key'),
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/api-keys/{$apiKey->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'API Key deleted successfully']);

        $this->assertSoftDeleted('api_keys', ['id' => $apiKey->id]);
    }

    public function test_api_key_can_access_service()
    {
        $user = User::factory()->create();
        $plainKey = 'valid-key';
        ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Service Key',
            'key' => hash('sha256', $plainKey),
        ]);

        $response = $this->withHeader('X-API-KEY', $plainKey)
            ->getJson('/api/service');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_invalid_api_key_cannot_access_service()
    {
        $response = $this->withHeader('X-API-KEY', 'invalid-key')
            ->getJson('/api/service');

        $response->assertStatus(401);
    }
}
