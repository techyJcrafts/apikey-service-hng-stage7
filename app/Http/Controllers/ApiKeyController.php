<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateApiKeyRequest;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;

use OpenApi\Attributes as OA;

class ApiKeyController extends Controller
{
    #[OA\Get(
        path: '/api/api-keys',
        summary: 'List API keys',
        tags: ['API Keys'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of API keys')
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(auth('api')->user()->apiKeys);
    }

    #[OA\Post(
        path: '/api/keys/create',
        summary: 'Create a new API key',
        tags: ['API Keys'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'expires_at', type: 'integer', description: 'Days until expiration (optional)'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'API Key created'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function store(CreateApiKeyRequest $request, ApiKeyService $service): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated in controller'], 401);
            }

            if ($user->apiKeys()->count() >= 5) {
                return response()->json(['error' => 'You have reached the maximum limit of 5 API keys.'], 422);
            }

            $expiresAt = $request->expires_at ? now()->addDays((int) $request->expires_at) : null;
            $result = $service->createKey($user, $request->name, $expiresAt);
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    #[OA\Delete(
        path: '/api/api-keys/{apiKey}',
        summary: 'Delete an API key',
        tags: ['API Keys'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'apiKey', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'API Key deleted'),
            new OA\Response(response: 403, description: 'Unauthorized')
        ]
    )]
    public function destroy(ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->user_id !== auth('api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $apiKey->delete();

        return response()->json(['message' => 'API Key deleted successfully']);
    }
}
