<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create a wallet for the user
        $walletService = new \App\Services\WalletService(new \App\Services\PaystackService());
        $walletService->createWallet($user);

        // Create an API key for the user
        $service = new ApiKeyService();
        $keyData = $service->createApiKey(
            $user,
            'Development Key',
            ['wallet.read', 'wallet.fund', 'wallet.transfer'], // Default permissions
            '1Y' // Default expiry
        );

        $this->command->info('User created: test@example.com / password');
        $this->command->info('API Key created: ' . $keyData['api_key']);
    }
}
