<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Create demo categories if they don't exist
        $categories = [
            ['name' => 'Salario', 'type' => 'income', 'icon' => 'work', 'color' => '#22c55e', 'is_global' => true],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => 'code', 'color' => '#3b82f6', 'is_global' => true],
            ['name' => 'Inversiones', 'type' => 'income', 'icon' => 'trending_up', 'color' => '#8b5cf6', 'is_global' => true],
            ['name' => 'Alimentación', 'type' => 'expense', 'icon' => 'restaurant', 'color' => '#ef4444', 'is_global' => true],
            ['name' => 'Transporte', 'type' => 'expense', 'icon' => 'directions_bus', 'color' => '#f59e0b', 'is_global' => true],
            ['name' => 'Servicios', 'type' => 'expense', 'icon' => 'receipt', 'color' => '#6366f1', 'is_global' => true],
            ['name' => 'Entretenimiento', 'type' => 'expense', 'icon' => 'movie', 'color' => '#ec4899', 'is_global' => true],
            ['name' => 'Salud', 'type' => 'expense', 'icon' => 'local_hospital', 'color' => '#14b8a6', 'is_global' => true],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name'], 'is_global' => true], $cat);
        }
    }
}