<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckApiKeyPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $apiKey = $request->get('authenticated_api_key');

        // If no API key (JWT user), allow all
        if (!$apiKey) {
            return $next($request);
        }

        // Check permission
        if (!$apiKey->hasPermission($permission)) {
            Log::warning('API key permission denied', [
                'api_key_id' => $apiKey->id,
                'required_permission' => $permission,
                'has_permissions' => $apiKey->permissions,
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'required' => $permission,
                'your_permissions' => $apiKey->permissions,
            ], 403);
        }

        return $next($request);
    }
}
