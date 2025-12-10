<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Wallet",
 *     description="Wallet operations endpoints"
 * )
 */
class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/wallet/balance",
     *     tags={"Wallet"},
     *     summary="Get wallet balance",
     *     security={{"bearerAuth":{}}, {"apiKey":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Wallet balance",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="balance", type="number", example=15000.00),
     *                 @OA\Property(property="currency", type="string", example="NGN"),
     *                 @OA\Property(property="wallet_number", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function balance(Request $request)
    {
        $wallet = $request->user()->wallet;

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => (string) $wallet->balance,
                'currency' => $wallet->currency,
                'wallet_number' => $wallet->wallet_number,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/wallet/deposit",
     *     tags={"Wallet"},
     *     summary="Initialize deposit",
     *     security={{"bearerAuth":{}}, {"apiKey":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", example=5000, description="Amount in Naira")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Deposit initialized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="reference", type="string"),
     *                 @OA\Property(property="authorization_url", type="string", description="Paystack checkout URL")
     *             )
     *         )
     *     )
     * )
     */
    public function deposit(DepositRequest $request)
    {
        $data = $this->walletService->initializeDeposit(
            $request->user(),
            $request->getAmount()
        );

        return response()->json([
            'success' => true,
            'message' => 'Deposit initialized. Complete payment on Paystack.',
            'data' => $data,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/wallet/deposit/{reference}/status",
     *     tags={"Wallet"},
     *     summary="Check deposit status",
     *     security={{"bearerAuth":{}}, {"apiKey":{}}},
     *     @OA\Parameter(name="reference", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Transaction status")
     * )
     */
    public function depositStatus(Request $request, string $reference)
    {
        $status = $this->walletService->getTransactionStatus($reference);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/wallet/transactions",
     *     tags={"Wallet"},
     *     summary="Get transaction history",
     *     security={{"bearerAuth":{}}, {"apiKey":{}}},
     *     @OA\Response(response=200, description="List of transactions")
     * )
     */
    public function transactions(Request $request)
    {
        $transactions = $request->user()->wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($txn) {
                return [
                    'id' => $txn->id,
                    'type' => $txn->type,
                    'amount' => (string) $txn->amount,
                    'status' => $txn->status,
                    'reference' => $txn->reference,
                    'balance_before' => (string) $txn->balance_before,
                    'balance_after' => (string) $txn->balance_after,
                    'metadata' => $txn->metadata,
                    'created_at' => $txn->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }
}
