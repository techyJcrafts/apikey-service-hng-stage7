<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    private string $secretKey;
    private string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
    }

    /**
     * Initialize Paystack transaction
     * CRITICAL: Amount must be in KOBO
     */
    public function initializeTransaction(array $data): array
    {
        Log::info('Paystack: Initializing transaction', [
            'email' => $data['email'],
            'amount_kobo' => (string) $data['amount'],
            'reference' => $data['reference'],
        ]);

        $response = Http::withoutVerifying()
            ->withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email' => $data['email'],
                'amount' => $data['amount'], // Must be in kobo!
                'reference' => $data['reference'],
                'callback_url' => $data['callback_url'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

        if (!$response->successful()) {
            Log::error('Paystack: Transaction initialization failed', [
                'response' => $response->json(),
            ]);
            throw new \Exception('Failed to initialize Paystack transaction');
        }

        $result = $response->json();

        Log::info('Paystack: Transaction initialized successfully', [
            'reference' => $data['reference'],
            'authorization_url' => $result['data']['authorization_url'],
        ]);

        return $result;
    }

    /**
     * Verify transaction (fallback for manual checks)
     */
    public function verifyTransaction(string $reference): array
    {
        Log::info('Paystack: Verifying transaction', ['reference' => $reference]);

        $response = Http::withoutVerifying()
            ->withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if (!$response->successful()) {
            Log::error('Paystack: Verification failed', [
                'reference' => $reference,
                'response' => $response->json(),
            ]);
            throw new \Exception('Failed to verify transaction');
        }

        return $response->json();
    }

    /**
     * Validate webhook signature
     * CRITICAL SECURITY: Always validate Paystack webhooks
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $computedSignature = hash_hmac('sha512', $payload, config('services.paystack.webhook_secret'));

        $isValid = hash_equals($computedSignature, $signature);

        if (!$isValid) {
            Log::warning('Paystack: Invalid webhook signature detected', [
                'received_signature' => $signature,
                'computed_signature' => $computedSignature,
            ]);
        }

        return $isValid;
    }
}
