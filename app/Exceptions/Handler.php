<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log all exceptions with context
            Log::error('Exception occurred', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    }

    public function render($request, Throwable $e): \Illuminate\Http\Response|JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        // Custom exception handling for API
        if ($request->expectsJson() || $request->is('api*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    private function handleApiException($request, Throwable $e): JsonResponse
    {
        $status = 500;
        $message = 'Internal server error';
        $errors = null;

        // Validation errors
        if ($e instanceof ValidationException) {
            $status = 422;
            $message = 'Validation failed';
            $errors = $e->errors();
        }
        // Not found
        elseif ($e instanceof NotFoundHttpException) {
            $status = 404;
            $message = 'Resource not found';
        }
        // Method not allowed
        elseif ($e instanceof MethodNotAllowedHttpException) {
            $status = 405;
            $message = 'Method not allowed';
        }
        // Custom exceptions
        elseif (
            $e instanceof InsufficientBalanceException ||
            $e instanceof InvalidApiKeyException ||
            $e instanceof WalletNotFoundException ||
            $e instanceof TooManyApiKeysException ||
            $e instanceof InvalidTransferException ||
            $e instanceof DuplicateTransactionException
        ) {
            $status = $e->getCode();
            if ($status < 100 || $status > 599) {
                $status = 400; // Default to Bad Request if code is invalid
            }
            $message = $e->getMessage();
        }
        // Generic exceptions (hide details in production)
        elseif ($e instanceof \Exception) {
            if (config('app.debug')) {
                $message = $e->getMessage();
            }
        }

        $response = [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        return response()->json($response, $status);
    }
}
