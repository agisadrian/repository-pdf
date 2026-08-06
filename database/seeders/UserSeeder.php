<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun admin buat login ke dashboard
        User::updateOrCreate(
            ['email' => 'admin@repo.test'],
            [
                'name' => 'Admin Repository',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Akun user biasa (contoh)
        User::updateOrCreate(
            ['email' => 'user@repo.test'],
            [
                'name' => 'User Contoh',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );
    }
}
