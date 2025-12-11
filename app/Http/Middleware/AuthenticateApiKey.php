<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiKeyService;
use Illuminate\Support\Facades\Log;

class AuthenticateApiKey
{
    public function __construct(
        private ApiKeyService $apiKeyService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $apiKeyHeader = $request->header('x-api-key');

        if (!$apiKeyHeader) {
            Log::warning('API request without x-api-key header', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'API key required. Include x-api-key header.',
            ], 401);
        }

        $apiKey = $this->apiKeyService->validateApiKey($apiKeyHeader);

        if (!$apiKey) {
            Log::warning('Invalid API key used', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired API key',
            ], 401);
        }

        // Attach API key and user to request
        $request->merge(['authenticated_api_key' => $apiKey]);
        $request->setUserResolver(fn() => $apiKey->user);

        Log::info('API request authenticated', [
            'api_key_id' => $apiKey->id,
            'user_id' => $apiKey->user_id,
            'path' => $request->path(),
        ]);

        return $next($request);
    }
}
