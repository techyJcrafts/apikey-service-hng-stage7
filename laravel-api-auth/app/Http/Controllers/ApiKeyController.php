<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateApiKeyRequest;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;

class ApiKeyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(auth('api')->user()->apiKeys);
    }

    public function store(CreateApiKeyRequest $request, ApiKeyService $service): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated in controller'], 401);
            }
            $expiresAt = $request->expires_at ? now()->addDays((int) $request->expires_at) : null;
            $result = $service->createKey($user, $request->name, $expiresAt);
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->user_id !== auth('api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $apiKey->delete();

        return response()->json(['message' => 'API Key deleted successfully']);
    }
}
