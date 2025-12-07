<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJWT
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (!$user = auth('api')->authenticate()) {
                return response()->json(['error' => 'User not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        }

        return $next($request);
    }
}
