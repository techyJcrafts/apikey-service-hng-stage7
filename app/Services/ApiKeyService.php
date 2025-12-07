<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ApiKeyService
{
    public function createKey(User $user, string $name, ?\DateTimeInterface $expiresAt = null): array
    {
        $plainKey = Str::random(64);

        $apiKey = $user->apiKeys()->create([
            'name' => $name,
            'key' => hash('sha256', $plainKey),
            'expires_at' => $expiresAt,
        ]);

        return [
            'key' => $plainKey,
            'api_key' => $apiKey
        ];
    }

    public function validateKey(string $plainKey): ?ApiKey
    {
        $hashedKey = hash('sha256', $plainKey);

        $apiKey = ApiKey::where('key', $hashedKey)->first();

        if (!$apiKey) {
            return null;
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return null;
        }

        return $apiKey;
    }

    public function recordUsage(ApiKey $apiKey, Request $request): void
    {
        $apiKey->update(['last_used_at' => now()]);

        $apiKey->usages()->create([
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'ip_address' => $request->ip(),
            'status_code' => http_response_code() ?: 200, // Fallback if not set yet
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
