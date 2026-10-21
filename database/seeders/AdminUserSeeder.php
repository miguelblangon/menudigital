<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Solo crear si no existe ya un usuario admin
        if (!User::where('email', 'admin@menudigital.com')->exists()) {
            User::create([
                'name' => 'Administrador',
                'email' => 'admin@menudigital.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]);
        }
    }
}