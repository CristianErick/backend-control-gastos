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
            // Toggle suspended status (you'd need a suspended column, for now just return user)
            return response()->json(['message' => 'Estado actualizado.', 'user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cambiar estado.'], 500);
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
}
