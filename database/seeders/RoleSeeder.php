<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user if not exists
        User::firstOrCreate(
            ['email' => 'admin@optigas.com'],
            [
                'name' => 'Administrador OptiGasto',
                'email' => 'admin@optigas.com',
                'password' => Hash::make('admin123'),
                'role' => 'administrador',
                'email_verified_at' => now(),
            ]
        );

        // Create test client user
        User::firstOrCreate(
            ['email' => 'cliente@optigas.com'],
            [
                'name' => 'Cliente Demo',
                'email' => 'cliente@optigas.com',
                'password' => Hash::make('cliente123'),
                'role' => 'cliente',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Roles y usuarios por defecto creados:');
        $this->command->info('Admin: admin@optigas.com / admin123');
        $this->command->info('Cliente: cliente@optigas.com / cliente123');
    }
}