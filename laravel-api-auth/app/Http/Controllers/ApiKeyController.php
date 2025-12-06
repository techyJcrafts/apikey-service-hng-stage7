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
        return response()->json(auth()->user()->apiKeys);
    }

    public function store(CreateApiKeyRequest $request, ApiKeyService $service): JsonResponse
    {
        $result = $service->createKey(auth()->user(), $request->name);

        return response()->json($result, 201);
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $apiKey->delete();

        return response()->json(['message' => 'API Key deleted successfully']);
    }
}
