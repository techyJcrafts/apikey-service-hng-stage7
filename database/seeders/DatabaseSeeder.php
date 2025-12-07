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

        // Create an API key for the user
        $service = new ApiKeyService();
        $keyData = $service->createKey($user, 'Development Key');

        $this->command->info('User created: test@example.com / password');
        $this->command->info('API Key created: ' . $keyData['key']);
    }
}
