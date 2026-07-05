<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SyncSeedAccountPasswords extends Command
{
    protected $signature = 'accounts:sync-passwords';

    protected $description = 'Set seed account passwords from SEED_ADMIN_PASSWORD / SEED_CUSTOMER_PASSWORD env vars (runs on deploy)';

    public function handle(): int
    {
        $accounts = [
            'admin@ekyarnco.local' => env('SEED_ADMIN_PASSWORD'),
            'customer@ekyarnco.local' => env('SEED_CUSTOMER_PASSWORD'),
        ];

        foreach ($accounts as $email => $password) {
            if (! $password) {
                continue;
            }

            $user = User::where('email', $email)->first();

            if ($user && ! Hash::check($password, $user->password)) {
                $user->update(['password' => Hash::make($password)]);
                $this->info("Password updated for {$email}");
            }
        }

        return self::SUCCESS;
    }
}
