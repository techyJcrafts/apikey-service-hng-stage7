<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\CheckApiKeyPermission;
use App\Http\Middleware\FlexibleAuth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Public Routes ---

// Authentication
Route::post('auth/signup', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

// Google OAuth
Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Webhooks
Route::post('wallet/paystack/webhook', [WebhookController::class, 'handlePaystackWebhook']);

// --- Protected Routes (JWT Only) ---
// These are primarily for user management and session handling
Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    // API Key Management
    Route::prefix('keys')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index']);
        Route::post('/create', [ApiKeyController::class, 'createApiKey']);
        Route::post('/rollover', [ApiKeyController::class, 'rolloverApiKey']);
        Route::delete('/{id}', [ApiKeyController::class, 'revokeApiKey']);
    });
});

// --- Flexible Protected Routes (JWT or API Key) ---
// These routes can be accessed by a logged-in user OR a service with a valid API Key
Route::middleware([FlexibleAuth::class, 'throttle:100,1'])->group(function () {

    // Wallet Operations
    Route::prefix('wallet')->group(function () {

        // Balance
        Route::get('/balance', [WalletController::class, 'balance'])
            ->middleware(CheckApiKeyPermission::class . ':wallet.read');

        // Deposit
        Route::post('/deposit', [WalletController::class, 'deposit'])
            ->middleware(CheckApiKeyPermission::class . ':wallet.fund');

        Route::get('/deposit/{reference}/status', [WalletController::class, 'depositStatus'])
            ->middleware(CheckApiKeyPermission::class . ':wallet.read');

        // Transfer
        Route::post('/transfer', [TransferController::class, 'transfer'])
            ->middleware(CheckApiKeyPermission::class . ':wallet.transfer');

        // Transactions History
        Route::get('/transactions', [WalletController::class, 'transactions'])
            ->middleware(CheckApiKeyPermission::class . ':wallet.read');
    });
});
