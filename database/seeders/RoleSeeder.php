<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@ekyarnco.local'],
            [
                'name' => 'EK Yarn Admin',
                'phone' => '+92 300 0000001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (! $admin->email_verified_at) {
            $admin->update(['email_verified_at' => now()]);
        }
        $admin->assignRole($adminRole);

        $customer = User::firstOrCreate(
            ['email' => 'customer@ekyarnco.local'],
            [
                'name' => 'Demo Customer',
                'phone' => '+92 300 0000002',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (! $customer->email_verified_at) {
            $customer->update(['email_verified_at' => now()]);
        }
        $customer->assignRole($customerRole);
    }
}
