<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiKeyService;
use Illuminate\Support\Facades\Log;

/**
 * Middleware that accepts BOTH JWT (Sanctum) and API Key authentication
 */
class FlexibleAuth
{
    public function __construct(
        private ApiKeyService $apiKeyService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        // Try API Key first
        $apiKeyHeader = $request->header('x-api-key');
        if ($apiKeyHeader) {
            $apiKey = $this->apiKeyService->validateApiKey($apiKeyHeader);
            if ($apiKey) {
                $request->merge(['authenticated_api_key' => $apiKey]);
                $request->setUserResolver(fn() => $apiKey->user);

                Log::info('Request authenticated via API key', [
                    'api_key_id' => $apiKey->id,
                    'user_id' => $apiKey->user_id,
                ]);

                return $next($request);
            }
        }

        // Try JWT (Tymon JWTAuth)
        if ($request->bearerToken()) {
            try {
                if ($user = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate()) {
                    $request->setUserResolver(fn() => $user);

                    Log::info('Request authenticated via JWT', [
                        'user_id' => $user->id,
                    ]);

                    return $next($request);
                }
            } catch (\Exception $e) {
                // Token invalid or expired, just ignore and fall through to 401
            }
        }

        // No valid authentication
        Log::warning('Unauthenticated request', [
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Provide either Bearer token or x-api-key header.',
        ], 401);
    }
}
