<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateApiKeyRequest;
use App\Http\Requests\RolloverKeyRequest;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="API Keys",
 *     description="API key management endpoints"
 * )
 */
class ApiKeyController extends Controller
{
    public function __construct(
        private ApiKeyService $apiKeyService
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/keys/create",
     *     tags={"API Keys"},
     *     summary="Create new API key",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "permissions", "expiry"},
     *             @OA\Property(property="name", type="string", example="wallet-service"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string", enum={"deposit", "transfer", "read"})),
     *             @OA\Property(property="expiry", type="string", example="1D", description="1H, 1D, 1M, or 1Y")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="API key created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="api_key", type="string", description="SAVE THIS! It's shown only once"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function create(CreateApiKeyRequest $request)
    {
        $data = $this->apiKeyService->createApiKey(
            $request->user(),
            $request->name,
            $request->permissions,
            $request->expiry
        );

        return response()->json([
            'success' => true,
            'status' => 201,
            'message' => 'API key created successfully. Save it now - you won\'t see it again!',
            'data' => $data,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/keys/rollover",
     *     tags={"API Keys"},
     *     summary="Rollover expired API key",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"expired_key_id", "expiry"},
     *             @OA\Property(property="expired_key_id", type="string"),
     *             @OA\Property(property="expiry", type="string", example="1M")
     *         )
     *     ),
     *     @OA\Response(response=201, description="API key rolled over")
     * )
     */
    public function rollover(RolloverKeyRequest $request)
    {
        $data = $this->apiKeyService->rolloverApiKey(
            $request->user(),
            $request->expired_key_id,
            $request->expiry
        );

        return response()->json([
            'success' => true,
            'status' => 201,
            'message' => 'API key rolled over successfully',
            'data' => $data,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/keys",
     *     tags={"API Keys"},
     *     summary="List all API keys",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of API keys")
     * )
     */
    public function index(Request $request)
    {
        $apiKeys = $request->user()->apiKeys()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'permissions' => $key->permissions,
                    'expires_at' => $key->expires_at->toIso8601String(),
                    'is_active' => $key->isValid(),
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'created_at' => $key->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'API keys retrieved successfully',
            'data' => $apiKeys,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/keys/{id}",
     *     tags={"API Keys"},
     *     summary="Revoke API key",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="API key revoked")
     * )
     */
    public function revoke(Request $request, string $id)
    {
        $apiKey = $request->user()->apiKeys()->findOrFail($id);
        $apiKey->revoke();

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'API key revoked successfully',
        ]);
    }
}
