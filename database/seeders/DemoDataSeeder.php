<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Profile;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Grab principal accounts: admin (id 1) and cliente (id 2). If missing, recreate.
        $admin = User::where('email', 'admin@optigas.com')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Administrador OptiGasto',
                'email' => 'admin@optigas.com',
                'password' => Hash::make('admin123'),
                'role' => 'administrador',
            ]);
        } else {
            $admin->update(['name' => 'Administrador OptiGasto', 'role' => 'administrador']);
        }
        $admin->update(['suspended' => false]);
        if (!$admin->profile) $admin->profile()->create(['currency' => 'PEN']);

        $cliente = User::where('email', 'cliente@optigas.com')->first();
        if (!$cliente) {
            $cliente = User::create([
                'name' => 'Cliente Demo',
                'email' => 'cliente@optigas.com',
                'password' => Hash::make('cliente123'),
                'role' => 'cliente',
            ]);
        } else {
            $cliente->update(['name' => 'Cliente Demo', 'role' => 'cliente']);
        }
        $cliente->update(['suspended' => false]);
        if (!$cliente->profile) $cliente->profile()->create(['currency' => 'PEN']);

        // Remove all other users (cascade deletes their data).
        User::whereNotIn('id', [$admin->id, $cliente->id])->delete();

        // Remove all transactions and goals belonging to the two principal accounts.
        Transaction::whereIn('user_id', [$admin->id, $cliente->id])->delete();
        SavingsGoal::whereIn('user_id', [$admin->id, $cliente->id])->delete();

        // Ensure global categories exist (idempotent).
        $categories = [
            ['name' => 'Salario', 'type' => 'income', 'icon' => 'work', 'color' => '#22c55e'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => 'code', 'color' => '#3b82f6'],
            ['name' => 'Inversiones', 'type' => 'income', 'icon' => 'trending_up', 'color' => '#8b5cf6'],
            ['name' => 'Ventas', 'type' => 'income', 'icon' => 'savings', 'color' => '#10b981'],
            ['name' => 'Alimentación', 'type' => 'expense', 'icon' => 'restaurant', 'color' => '#ef4444'],
            ['name' => 'Transporte', 'type' => 'expense', 'icon' => 'directions_bus', 'color' => '#f59e0b'],
            ['name' => 'Servicios', 'type' => 'expense', 'icon' => 'receipt', 'color' => '#6366f1'],
            ['name' => 'Entretenimiento', 'type' => 'expense', 'icon' => 'movie', 'color' => '#ec4899'],
            ['name' => 'Salud', 'type' => 'expense', 'icon' => 'local_hospital', 'color' => '#14b8a6'],
            ['name' => 'Electricidad', 'type' => 'expense', 'icon' => 'electricity', 'color' => '#facc15'],
        ];

        $catIds = [];
        foreach ($categories as $cat) {
            $c = Category::firstOrCreate(
                ['name' => $cat['name'], 'is_global' => true],
                array_merge($cat, ['is_global' => true])
            );
            $catIds[$cat['name']] = $c->id;
        }

        // Seed realistic transactions over the last 12 months for both accounts.
        $this->seedAdminData($admin->id, $catIds);
        $this->seedClientData($cliente->id, $catIds);

        // Demo savings goals for client.
        $cliente->savingsGoals()->create([
            'title' => 'Fondo de emergencia',
            'target_amount' => 5000,
            'current_amount' => 1800,
            'deadline' => now()->addMonths(6)->toDateString(),
        ]);
        $cliente->savingsGoals()->create([
            'title' => 'Viaje de vacaciones',
            'target_amount' => 3000,
            'current_amount' => 950,
            'deadline' => now()->addMonths(4)->toDateString(),
        ]);
    }

    private function seedClientData(int $userId, array $cat): void
    {
        $now = now();

        // Salary on the 1st of each month.
        $incomeDescs = [
            'Sueldo mensual', 'Salario deposito', 'Sueldo a tiempo', 'Salario mensual',
        ];
        $expensePools = [
            ['Alimentación', [45, 60, 80, 55, 90, 70, 65, 120]],
            ['Transporte', [15, 20, 25, 30, 18, 40, 50]],
            ['Servicios', [80, 110, 95]],
            ['Entretenimiento', [40, 60, 25, 90]],
            ['Salud', [35, 150, 20]],
            ['Electricidad', [70, 85, 60]],
        ];

        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);

            // Income: salary + occasional freelance/bonus.
            $salary = 2600 + $i % 2 * 200;
            Transaction::create([
                'user_id' => $userId,
                'category_id' => $cat['Salario'],
                'amount' => $salary,
                'description' => $incomeDescs[$i % count($incomeDescs)],
                'date' => $month->copy()->startOfMonth()->addDays(1),
                'type' => 'income',
            ]);

            if ($i % 3 === 0) {
                Transaction::create([
                    'user_id' => $userId,
                    'category_id' => $cat['Freelance'],
                    'amount' => rand(350, 850),
                    'description' => 'Proyecto freelance',
                    'date' => $month->copy()->addDays(rand(5, 20)),
                    'type' => 'income',
                ]);
            }

            // Expenses spread through the month.
            $day = 3;
            foreach ($expensePools as [$cname, $amounts]) {
                foreach ($amounts as $amt) {
                    Transaction::create([
                        'user_id' => $userId,
                        'category_id' => $cat[$cname],
                        'amount' => $amt,
                        'description' => $this->descFor($cname),
                        'date' => $month->copy()->addDays(min($day, 27)),
                        'type' => 'expense',
                    ]);
                    $day += 2;
                }
            }
        }
    }

    private function seedAdminData(int $adminId, array $cat): void
    {
        $now = now();

        $incomeDescs = [
            'Sueldo mensual', 'Salario a tiempo', 'Sueldo administrador', 'Sueldo a tiempo',
        ];
        $expensePools = [
            ['Alimentación', [60, 85, 110, 70, 95, 130]],
            ['Transporte', [20, 25, 35, 40, 22]],
            ['Servicios', [90, 120, 100, 140]],
            ['Entretenimiento', [50, 70, 30]],
            ['Salud', [40, 180, 25]],
            ['Electricidad', [80, 95, 70]],
        ];

        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);

            $salary = 3200 + $i % 2 * 300;
            Transaction::create([
                'user_id' => $adminId,
                'category_id' => $cat['Salario'],
                'amount' => $salary,
                'description' => $incomeDescs[$i % count($incomeDescs)],
                'date' => $month->copy()->startOfMonth()->addDays(1),
                'type' => 'income',
            ]);

            if ($i % 2 === 0) {
                Transaction::create([
                    'user_id' => $adminId,
                    'category_id' => $cat['Inversiones'],
                    'amount' => rand(400, 900),
                    'description' => 'Dividendos / renta',
                    'date' => $month->copy()->addDays(rand(6, 22)),
                    'type' => 'income',
                ]);
            }

            $day = 3;
            foreach ($expensePools as [$cname, $amounts]) {
                foreach ($amounts as $amt) {
                    Transaction::create([
                        'user_id' => $adminId,
                        'category_id' => $cat[$cname],
                        'amount' => $amt,
                        'description' => $this->descFor($cname),
                        'date' => $month->copy()->addDays(min($day, 27)),
                        'type' => 'expense',
                    ]);
                    $day += 2;
                }
            }
        }
    }

    private function descFor(string $cat): string
    {
        $map = [
            'Alimentación' => ['Compra en supermercado', 'Restaurante', 'Mercado', 'Cafetería'],
            'Transporte' => ['Pasajes', 'Combustible', 'Taxi', 'Peaje'],
            'Servicios' => ['Internet + cable', 'AGUA SEDAPAL', 'Luz', 'Plan celular'],
            'Entretenimiento' => ['Cine', 'Netflix', 'Streaming', 'Salida'],
            'Salud' => ['Farmacia', 'Consulta médica', 'Vitaminas'],
            'Electricidad' => ['Recibo de luz', 'Recibo de luz'],
        ];
        $opts = $map[$cat] ?? [$cat];
        return $opts[array_rand($opts)];
    }
}