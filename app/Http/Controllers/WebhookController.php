<?php

namespace App\Http\Controllers;

use App\Services\PaystackService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Webhooks",
 *     description="External webhooks (Paystack)"
 * )
 */
class WebhookController extends Controller
{
    public function __construct(
        private PaystackService $paystackService,
        private WalletService $walletService
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/wallet/paystack/webhook",
     *     tags={"Webhooks"},
     *     summary="Paystack webhook handler",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event", type="string", example="charge.success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Webhook processed")
     * )
     */
    public function handlePaystackWebhook(Request $request)
    {
        // CRITICAL: Validate signature
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (!$this->paystackService->validateWebhookSignature($payload, $signature)) {
            Log::critical('Paystack webhook: INVALID SIGNATURE', [
                'ip' => $request->ip(),
                'signature' => $signature,
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $data['reference'] ?? null,
        ]);

        // Only process successful charges
        if ($event !== 'charge.success') {
            return response()->json(['status' => 'ignored']);
        }

        try {
            $this->walletService->processSuccessfulPayment($data);

            return response()->json(['status' => true]);
        } catch (\Throwable $e) {
            Log::error('Webhook processing failed', [
                'event' => $event,
                'reference' => $data['reference'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return error details for debugging (since we are in dev/test mode context)
            return response()->json([
                'status' => 'error_logged',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 200); // Keep 200 to satisfy Paystack, but header/body will show error
        }
    }
}
