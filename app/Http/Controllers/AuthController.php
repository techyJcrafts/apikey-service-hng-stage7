<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\SignupRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Client;
use Laravel\Socialite\Two\GoogleProvider;
use Tymon\JWTAuth\JWTGuard;

use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/signup',
        summary: 'Register a new user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created successfully'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function register(SignupRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'status' => 201,
            'message' => 'User created successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 201);
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login user and get JWT',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials')
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Invalid credentials'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    #[OA\Get(
        path: '/api/me',
        summary: 'Get authenticated user profile',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'User profile')
        ]
    )]
    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'User profile successfully retrieved',
            'data' => auth('api')->user()
        ]);
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout user',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully')
        ]
    )]
    public function logout(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Successfully logged out'
        ]);
    }

    #[OA\Post(
        path: '/api/refresh',
        summary: 'Refresh JWT token',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'New token generated')
        ]
    )]
    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        return $this->respondWithToken($guard->refresh());
    }

    protected function respondWithToken($token): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $guard->factory()->getTTL() * 60
            ]
        ]);
    }
    #[OA\Get(
        path: '/api/auth/google',
        summary: 'Redirect to Google OAuth',
        description: 'Click the link below to login with Google. DO NOT use Try it out.',
        tags: ['Auth'],
        externalDocs: new OA\ExternalDocumentation(
            description: 'LOGIN WITH GOOGLE HERE',
            url: '/api/auth/google'
        ),
        responses: [
            new OA\Response(response: 302, description: 'Redirect to Google')
        ]
    )]
    public function redirectToGoogle()
    {
        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');
        $driver->setHttpClient(new Client(['verify' => false]));

        return $driver->stateless()
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    #[OA\Get(
        path: '/api/auth/google/callback',
        summary: 'Handle Google OAuth callback',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Login successful with JWT')
        ]
    )]
    public function handleGoogleCallback(): JsonResponse
    {
        try {
            /** @var GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $driver->setHttpClient(new Client(['verify' => false]));
            $googleUser = $driver->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(\Illuminate\Support\Str::random(24)), // Random password
                    'email_verified_at' => now(),
                ]
            );

            // Auto-create wallet if not exists
            if (!$user->wallet) {
                app(\App\Services\WalletService::class)->createWallet($user);
            }

            $token = JWTAuth::fromUser($user);

            return $this->respondWithToken($token);

        } catch (\Exception $e) {
            Log::error('Google Auth Failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Authentication failed. Please try again or contact support.',
                'debug_message' => config('app.debug') ? $e->getMessage() : null
            ], 401);
        }
    }
}
