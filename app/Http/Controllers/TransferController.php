<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Transfer",
 *     description="P2P transfer endpoints"
 * )
 */
class TransferController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {
    }

    /**
     * @OA\Post(
     *     path="/wallet/transfer",
     *     tags={"Transfer"},
     *     summary="Transfer to another wallet",
     *     security={{"bearerAuth":{}}, {"apiKey":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"wallet_number", "amount"},
     *             @OA\Property(property="wallet_number", type="string", example="45123456789012"),
     *             @OA\Property(property="amount", type="number", example=3000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfer successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="reference", type="string"),
     *                 @OA\Property(property="amount", type="number"),
     *                 @OA\Property(property="recipient_wallet", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function transfer(TransferRequest $request)
    {
        $transfer = $this->walletService->transfer(
            $request->user()->wallet,
            $request->wallet_number,
            $request->getAmount()
        );

        return response()->json([
            'success' => true,
            'status' => 201,
            'message' => 'Transfer completed successfully',
            'data' => [
                'reference' => $transfer->reference,
                'amount' => (string) $transfer->amount,
                'sender_wallet' => $transfer->senderWallet->wallet_number,
                'recipient_wallet' => $transfer->receiverWallet->wallet_number,
                'created_at' => $transfer->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
