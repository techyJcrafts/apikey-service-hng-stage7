<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenExpiredException $e, Request $request) {
            return response()->json(['error' => 'Token has expired'], 401);
        });
        $exceptions->render(function (TokenInvalidException $e, Request $request) {
            return response()->json(['error' => 'Token is invalid'], 401);
        });
        $exceptions->render(function (TokenBlacklistedException $e, Request $request) {
            return response()->json(['error' => 'Token has been blacklisted'], 401);
        });
        $exceptions->render(function (JWTException $e, Request $request) {
            return response()->json(['error' => 'Token absent or invalid'], 401);
        });
    })->create();
