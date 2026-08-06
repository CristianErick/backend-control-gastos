<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users(): JsonResponse
    {
        try {
            $users = User::with('profile')->get();
            return response()->json(['users' => $users], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar usuarios.'], 500);
        }
    }

    public function toggleUserStatus(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->suspended = !$user->suspended;
            $user->save();
            return response()->json([
                'message' => 'Estado actualizado.',
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cambiar estado.'], 500);
        }
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'password' => 'nullable|string|min:8',
            ]);

            $updates = ['name' => $validated['name']];
            if (!empty($validated['password'])) {
                $updates['password'] = $validated['password'];
            }
            $user->update($updates);

            return response()->json(['message' => 'Usuario actualizado.', 'user' => $user->load('profile')], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar usuario.'], 500);
        }
    }

    public function updateUserRole(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id === $request->user()->id) {
                return response()->json(['message' => 'No puedes modificar tu propio rol.'], 403);
            }

            $validated = $request->validate([
                'role' => 'required|in:cliente,administrador',
            ]);

            $user->update(['role' => $validated['role']]);

            return response()->json(['message' => 'Rol actualizado.', 'user' => $user], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar rol.'], 500);
        }
    }

    public function deleteUser(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id === 1 || (string) $user->id === (string) $request->user()->id) {
                return response()->json(['message' => 'No puedes eliminar la cuenta principal de administrador.'], 403);
            }

            $user->transactions()->delete();
            $user->savingsGoals()->delete();
            $user->profile()->delete();
            $user->delete();

            return response()->json(['message' => 'Usuario eliminado.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar usuario.'], 500);
        }
    }

    public function categories(): JsonResponse
    {
        try {
            $categories = Category::all();
            return response()->json(['categories' => $categories], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar categorías.'], 500);
        }
    }

    public function storeCategory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'type' => 'required|in:income,expense',
                'icon' => 'nullable|string|max:50',
                'color' => 'nullable|string|max:7',
            ]);

            $category = Category::create(array_merge($validated, ['is_global' => true]));

            return response()->json(['message' => 'Categoría creada.', 'category' => $category], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear categoría.'], 500);
        }
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'type' => 'required|in:income,expense',
                'icon' => 'nullable|string|max:50',
                'color' => 'nullable|string|max:7',
            ]);

            $category->update($validated);

            return response()->json(['message' => 'Categoría actualizada.', 'category' => $category], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar categoría.'], 500);
        }
    }

    public function deleteCategory(Category $category): JsonResponse
    {
        try {
            $category->delete();
            return response()->json(['message' => 'Categoría eliminada.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar categoría.'], 500);
        }
    }

    public function transactions(): JsonResponse
    {
        try {
            $transactions = Transaction::with(['user', 'category'])->orderBy('date', 'desc')->get();
            return response()->json(['transactions' => $transactions], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar transacciones.'], 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $totalUsers = User::count();
            $totalTransactions = Transaction::count();
            $totalIncome = Transaction::where('type', 'income')->sum('amount');
            $totalExpense = Transaction::where('type', 'expense')->sum('amount');

            return response()->json([
                'totalUsers' => $totalUsers,
                'totalTransactions' => $totalTransactions,
                'totalIncome' => $totalIncome,
                'totalExpense' => $totalExpense,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener estadísticas.'], 500);
        }
    }

    public function resetDemoData(Request $request): JsonResponse
    {
        try {
            $expected = env('DEMO_RESET_KEY', 'optigas-demo-2026');
            $provided = (string) $request->header('X-Demo-Key', '');

            if ($provided !== $expected) {
                return response()->json(['message' => 'Clave de reseteo inválida.'], 403);
            }

            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => \Database\Seeders\DemoDataSeeder::class,
                '--force' => true,
            ]);

            return response()->json(['message' => 'Datos demo regenerados.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al regenerar datos demo.', 'error' => $e->getMessage()], 500);
        }
    }
}
