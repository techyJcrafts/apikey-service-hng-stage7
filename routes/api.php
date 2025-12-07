<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\AuthenticateJWT;
use Illuminate\Support\Facades\Route;

Route::post('auth/signup', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);

    Route::get('api-keys', [ApiKeyController::class, 'index']);
    Route::post('keys/create', [ApiKeyController::class, 'store']);
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy']);
});

Route::middleware([AuthenticateApiKey::class])->group(function () {
    Route::get('service', [ServiceController::class, 'index']);
});
