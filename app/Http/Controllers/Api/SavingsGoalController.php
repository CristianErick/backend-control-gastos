<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavingsGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsGoalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $goals = $user->savingsGoals()->orderBy('deadline')->get();

            $monthStart = now()->startOfMonth();
            $monthlyIncome = (float) $user->transactions()
                ->where('type', 'income')
                ->where('date', '>=', $monthStart)
                ->sum('amount');

            $monthlyExpense = (float) $user->transactions()
                ->where('type', 'expense')
                ->where('date', '>=', $monthStart)
                ->sum('amount');

            $monthlyBalance = $monthlyIncome - $monthlyExpense;

            $goals->each(function ($goal) use ($monthlyBalance) {
                $goal->current_amount = $monthlyBalance;
            });

            return response()->json(['savings_goals' => $goals], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar metas.', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'target_amount' => 'required|numeric|min:1',
                'current_amount' => 'nullable|numeric|min:0',
                'deadline' => 'required|date|after:today',
            ]);

            $validated['current_amount'] = $validated['current_amount'] ?? 0;

            $goal = $request->user()->savingsGoals()->create($validated);

            return response()->json([
                'message' => 'Meta de ahorro creada exitosamente.',
                'savings_goal' => $goal,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear meta.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        try {
            if ($savingsGoal->user_id !== $request->user()->id) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'target_amount' => 'required|numeric|min:1',
                'current_amount' => 'nullable|numeric|min:0',
                'deadline' => 'required|date|after:today',
            ]);

            $savingsGoal->update($validated);

            return response()->json([
                'message' => 'Meta de ahorro actualizada exitosamente.',
                'savings_goal' => $savingsGoal->fresh(),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar meta.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, SavingsGoal $savingsGoal): JsonResponse
    {
        try {
            if ($savingsGoal->user_id !== $request->user()->id) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }

            $savingsGoal->delete();

            return response()->json(['message' => 'Meta de ahorro eliminada exitosamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar meta.', 'error' => $e->getMessage()], 500);
        }
    }
}
