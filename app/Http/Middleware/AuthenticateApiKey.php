<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ApiKeyService;

class AuthenticateApiKey
{
    protected $apiKeyService;

    public function __construct(ApiKeyService $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-KEY');

        if (!$key) {
            return response()->json(['error' => 'API Key required'], 401);
        }

        $apiKey = $this->apiKeyService->validateKey($key);

        if (!$apiKey) {
            return response()->json(['error' => 'Invalid API Key'], 401);
        }

        $this->apiKeyService->recordUsage($apiKey, $request);

        return $next($request);
    }
}
