<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@dprd.go.id'],
            [
                'name' => 'Admin DPRD',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'user@dprd.go.id'],
            [
                'name' => 'User DPRD',
                'password' => Hash::make('user123'),
                'is_admin' => false,
            ]
        );
    }
}
