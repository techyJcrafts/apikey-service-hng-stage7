<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\WalletNotFoundException;
use App\Exceptions\InvalidTransferException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletService
{
    public function __construct(
        private PaystackService $paystackService
    ) {
    }

    /**
     * Create wallet for user (auto-generated on user creation)
     */
    public function createWallet(User $user): Wallet
    {
        Log::info('Creating wallet for user', ['user_id' => $user->id]);

        $walletNumber = $this->generateUniqueWalletNumber();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_number' => $walletNumber,
            'balance' => 0.00,
            'currency' => 'NGN',
        ]);

        Log::info('Wallet created successfully', [
            'user_id' => $user->id,
            'wallet_number' => $walletNumber,
        ]);

        return $wallet;
    }

    /**
     * Generate unique 14-digit wallet number
     */
    private function generateUniqueWalletNumber(): string
    {
        do {
            $number = '45' . str_pad(random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (Wallet::where('wallet_number', $number)->exists());

        return $number;
    }

    /**
     * Initialize deposit via Paystack
     * CRITICAL: Converts Naira to Kobo before sending to Paystack
     */
    public function initializeDeposit(User $user, string $amountInNaira): array
    {
        Log::info('Initializing deposit', [
            'user_id' => $user->id,
            'amount_naira' => $amountInNaira,
        ]);

        $wallet = $user->wallet;
        if (!$wallet) {
            throw new WalletNotFoundException();
        }

        DB::beginTransaction();
        try {
            $reference = 'DEP_' . strtoupper(Str::random(20));
            $amountInKobo = (int) ($amountInNaira * 100);

            // Create pending transaction
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'amount' => $amountInNaira,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance, // Unchanged until webhook
                'reference' => $reference,
                'status' => 'pending',
                'metadata' => [
                    'amount_kobo' => $amountInKobo,
                    'initiated_at' => now()->toIso8601String(),
                ],
            ]);

            // Call Paystack
            $paystackResponse = $this->paystackService->initializeTransaction([
                'email' => $user->email,
                'amount' => $amountInKobo, // KOBO!
                'reference' => $reference,
                'callback_url' => config('app.url') . '/wallet/deposit/callback',
                'metadata' => [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'transaction_id' => $transaction->id,
                ],
            ]);

            DB::commit();

            Log::info('Deposit initialized successfully', [
                'reference' => $reference,
                'amount_naira' => $amountInNaira,
                'amount_kobo' => $amountInKobo,
            ]);

            return [
                'reference' => $reference,
                'authorization_url' => $paystackResponse['data']['authorization_url'],
                'access_code' => $paystackResponse['data']['access_code'],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deposit initialization failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process successful payment webhook from Paystack
     * CRITICAL: This is the ONLY place where wallets are credited
     * CRITICAL: Idempotent - can be called multiple times safely
     */
    public function processSuccessfulPayment(array $webhookData): void
    {
        $reference = $webhookData['reference'];
        $amountInKobo = $webhookData['amount'];
        $amountInNaira = $amountInKobo / 100;

        Log::info('Processing webhook payment', [
            'reference' => $reference,
            'amount_kobo' => $amountInKobo,
            'amount_naira' => $amountInNaira,
        ]);

        $transaction = Transaction::where('reference', $reference)->first();

        if (!$transaction) {
            Log::error('Webhook: Transaction not found', ['reference' => $reference]);
            throw new \Exception('Transaction not found');
        }

        // IDEMPOTENCY: Check if already processed
        if ($transaction->isSuccessful()) {
            Log::info('Webhook: Transaction already processed', ['reference' => $reference]);
            return; // Safe to ignore duplicate webhook
        }

        DB::beginTransaction();
        try {
            // Lock wallet row to prevent race conditions
            $wallet = Wallet::where('id', $transaction->wallet_id)
                ->lockForUpdate()
                ->first();

            $newBalance = $wallet->balance + $amountInNaira;

            // Update transaction
            $transaction->update([
                'status' => 'success',
                'balance_after' => $newBalance,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'paystack_response' => $webhookData,
                    'completed_at' => now()->toIso8601String(),
                ]),
            ]);

            // Credit wallet
            $wallet->update(['balance' => $newBalance]);

            DB::commit();

            Log::info('Webhook: Wallet credited successfully', [
                'reference' => $reference,
                'wallet_id' => $wallet->id,
                'old_balance' => $wallet->balance - $amountInNaira,
                'new_balance' => $newBalance,
                'amount_credited' => $amountInNaira,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook: Payment processing failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Transfer funds between wallets
     * CRITICAL: Atomic operation with row locking
     */
    public function transfer(Wallet $senderWallet, string $recipientWalletNumber, string $amount): Transfer
    {
        Log::info('Initiating transfer', [
            'sender_wallet' => $senderWallet->wallet_number,
            'recipient_wallet' => $recipientWalletNumber,
            'amount' => $amount,
        ]);

        // Find recipient
        $recipientWallet = Wallet::where('wallet_number', $recipientWalletNumber)->first();
        if (!$recipientWallet) {
            throw new WalletNotFoundException('Recipient wallet not found');
        }

        // Self-transfer check
        if ($recipientWallet->id === $senderWallet->id) {
            throw new InvalidTransferException('Cannot transfer to your own wallet');
        }

        // Balance check
        if ($senderWallet->balance < $amount) {
            Log::warning('Transfer failed: Insufficient balance', [
                'sender_wallet' => $senderWallet->wallet_number,
                'balance' => $senderWallet->balance,
                'requested_amount' => $amount,
            ]);
            throw new InsufficientBalanceException();
        }

        DB::beginTransaction();
        try {
            // Lock BOTH wallets (alphabetically by ID to prevent deadlock)
            $walletIds = [$senderWallet->id, $recipientWallet->id];
            sort($walletIds);

            $wallets = Wallet::whereIn('id', $walletIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $senderWallet = $wallets[$senderWallet->id];
            $recipientWallet = $wallets[$recipientWallet->id];

            // Re-check balance after lock
            if ($senderWallet->balance < $amount) {
                throw new InsufficientBalanceException();
            }

            $reference = 'TRF_' . strtoupper(Str::random(20));

            // Deduct from sender
            $senderNewBalance = $senderWallet->balance - $amount;
            $senderTransaction = Transaction::create([
                'wallet_id' => $senderWallet->id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'balance_before' => $senderWallet->balance,
                'balance_after' => $senderNewBalance,
                'reference' => $reference,
                'status' => 'success',
                'metadata' => [
                    'recipient_wallet' => $recipientWallet->wallet_number,
                    'recipient_name' => $recipientWallet->user->name,
                ],
            ]);
            $senderWallet->update(['balance' => $senderNewBalance]);

            // Credit recipient
            $recipientNewBalance = $recipientWallet->balance + $amount;
            $recipientTransaction = Transaction::create([
                'wallet_id' => $recipientWallet->id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'balance_before' => $recipientWallet->balance,
                'balance_after' => $recipientNewBalance,
                'reference' => $reference,
                'status' => 'success',
                'metadata' => [
                    'sender_wallet' => $senderWallet->wallet_number,
                    'sender_name' => $senderWallet->user->name,
                ],
            ]);
            $recipientWallet->update(['balance' => $recipientNewBalance]);

            // Create transfer record
            $transfer = Transfer::create([
                'sender_wallet_id' => $senderWallet->id,
                'receiver_wallet_id' => $recipientWallet->id,
                'amount' => $amount,
                'reference' => $reference,
                'status' => 'success',
                'sender_transaction_id' => $senderTransaction->id,
                'receiver_transaction_id' => $recipientTransaction->id,
            ]);

            DB::commit();

            Log::info('Transfer completed successfully', [
                'reference' => $reference,
                'sender_wallet' => $senderWallet->wallet_number,
                'recipient_wallet' => $recipientWallet->wallet_number,
                'amount' => $amount,
                'sender_new_balance' => $senderNewBalance,
                'recipient_new_balance' => $recipientNewBalance,
            ]);

            return $transfer;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transfer failed', [
                'sender_wallet' => $senderWallet->wallet_number,
                'recipient_wallet' => $recipientWalletNumber,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get transaction status (manual verification fallback)
     * WARNING: This should NOT credit wallets!
     */
    public function getTransactionStatus(string $reference): array
    {
        $transaction = Transaction::where('reference', $reference)->first();

        if (!$transaction) {
            throw new \Exception('Transaction not found');
        }

        return [
            'reference' => $transaction->reference,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'type' => $transaction->type,
            'created_at' => $transaction->created_at->toIso8601String(),
        ];
    }
}
