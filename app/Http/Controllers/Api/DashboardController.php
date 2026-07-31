<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function obtenerEstadisticas(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $totalIncome = $user->transactions()
                ->where('type', 'income')
                ->sum('amount');

            $totalExpense = $user->transactions()
                ->where('type', 'expense')
                ->sum('amount');

            $balance = $totalIncome - $totalExpense;

            $recentTransactions = $user->transactions()
                ->with('category')
                ->orderBy('date', 'desc')
                ->take(5)
                ->get();

            $totalTransactions = $user->transactions()->count();

            return response()->json([
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $balance,
                'total_transactions' => $totalTransactions,
                'recent_transactions' => $recentTransactions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener estadísticas.', 'error' => $e->getMessage()], 500);
        }
    }
}
