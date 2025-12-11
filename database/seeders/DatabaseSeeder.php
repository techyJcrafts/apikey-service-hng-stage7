<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $walletService = new \App\Services\WalletService(new \App\Services\PaystackService());
        $apiKeyService = new ApiKeyService();

        // 1. Create Sender User
        $sender = User::create([
            'name' => 'Sender User',
            'email' => 'sender@example.com',
            'password' => bcrypt('password'),
        ]);
        $walletService->createWallet($sender);
        $senderKey = $apiKeyService->createApiKey($sender, 'Sender Key', ['wallet.read', 'wallet.fund', 'wallet.transfer'], '1Y');

        // 2. Create Receiver User
        $receiver = User::create([
            'name' => 'Receiver User',
            'email' => 'receiver@example.com',
            'password' => bcrypt('password'),
        ]);
        $walletService->createWallet($receiver);
        $receiverKey = $apiKeyService->createApiKey($receiver, 'Receiver Key', ['wallet.read', 'wallet.fund', 'wallet.transfer'], '1Y');

        $this->command->info('------------------------------------------------');
        $this->command->info('User 1 (Sender): sender@example.com / password');
        $this->command->info('API Key: ' . $senderKey['api_key']);
        $this->command->info('------------------------------------------------');
        $this->command->info('User 2 (Receiver): receiver@example.com / password');
        $this->command->info('API Key: ' . $receiverKey['api_key']);
        $this->command->info('------------------------------------------------');

        // 3. Admin User (All Permissions)
        $admin = User::create(['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => bcrypt('password')]);
        $walletService->createWallet($admin);
        $adminKey = $apiKeyService->createApiKey($admin, 'Admin Key', ['wallet.read', 'wallet.fund', 'wallet.transfer'], '1Y');

        // 4. Read-Only User (Read Only)
        $readonly = User::create(['name' => 'ReadOnly User', 'email' => 'readonly@example.com', 'password' => bcrypt('password')]);
        $walletService->createWallet($readonly);
        $readonlyKey = $apiKeyService->createApiKey($readonly, 'ReadOnly Key', ['wallet.read'], '1Y');

        // 5. Investor User (Read & Fund Only)
        $investor = User::create(['name' => 'Investor User', 'email' => 'investor@example.com', 'password' => bcrypt('password')]);
        $walletService->createWallet($investor);
        $investorKey = $apiKeyService->createApiKey($investor, 'Investor Key', ['wallet.read', 'wallet.fund'], '1Y');

        // 6. Transfer User (Transfer Only)
        $transferUser = User::create(['name' => 'Transfer User', 'email' => 'transfer@example.com', 'password' => bcrypt('password')]);
        $walletService->createWallet($transferUser);
        $transferKey = $apiKeyService->createApiKey($transferUser, 'Transfer Key', ['wallet.transfer'], '1Y');

        // 7. Fund User (Fund Only)
        $fundUser = User::create(['name' => 'Fund User', 'email' => 'fund@example.com', 'password' => bcrypt('password')]);
        $walletService->createWallet($fundUser);
        $fundKey = $apiKeyService->createApiKey($fundUser, 'Fund Key', ['wallet.fund'], '1Y');

        $this->command->info('User 3 (Admin): admin@example.com / key: ' . $adminKey['api_key']);
        $this->command->info('User 4 (ReadOnly): readonly@example.com / key: ' . $readonlyKey['api_key']);
        $this->command->info('User 5 (Investor): investor@example.com / key: ' . $investorKey['api_key']);
        $this->command->info('User 6 (Transfer): transfer@example.com / key: ' . $transferKey['api_key']);
        $this->command->info('User 7 (Fund): fund@example.com / key: ' . $fundKey['api_key']);
    }
}
