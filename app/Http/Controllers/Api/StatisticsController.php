<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function gastosPorCategoria(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $gastosPorCategoria = $user->transactions()
                ->where('type', 'expense')
                ->with('category')
                ->get()
                ->groupBy('category.name')
                ->map(function ($transactions) {
                    return [
                        'total' => $transactions->sum('amount'),
                        'count' => $transactions->count(),
                        'category' => $transactions->first()->category ? [
                            'id' => $transactions->first()->category->id,
                            'name' => $transactions->first()->category->name,
                            'icon' => $transactions->first()->category->icon,
                            'color' => $transactions->first()->category->color,
                        ] : null,
                    ];
                })
                ->sortByDesc('total')
                ->values();

            return response()->json([
                'gastos_por_categoria' => $gastosPorCategoria,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener gastos por categoría.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function comparativaIngresosGastos(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $meses = $request->get('meses', 12);

            $datos = [];
            for ($i = $meses - 1; $i >= 0; $i--) {
                $fecha = now()->subMonths($i)->startOfMonth();
                $finMes = now()->subMonths($i)->endOfMonth();

                $ingresos = $user->transactions()
                    ->where('type', 'income')
                    ->whereBetween('date', [$fecha, $finMes])
                    ->sum('amount');

                $gastos = $user->transactions()
                    ->where('type', 'expense')
                    ->whereBetween('date', [$fecha, $finMes])
                    ->sum('amount');

                $datos[] = [
                    'mes' => $fecha->format('M Y'),
                    'mes_key' => $fecha->format('Y-m'),
                    'ingresos' => (float) $ingresos,
                    'gastos' => (float) $gastos,
                    'balance' => (float) ($ingresos - $gastos),
                ];
            }

            return response()->json([
                'comparativa' => $datos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener comparativa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function balanceHistorico(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $meses = $request->get('meses', 12);

            $balance = [];
            $balanceAcumulado = 0;

            for ($i = $meses - 1; $i >= 0; $i--) {
                $fecha = now()->subMonths($i)->startOfMonth();
                $finMes = now()->subMonths($i)->endOfMonth();

                $ingresos = $user->transactions()
                    ->where('type', 'income')
                    ->whereBetween('date', [$fecha, $finMes])
                    ->sum('amount');

                $gastos = $user->transactions()
                    ->where('type', 'expense')
                    ->whereBetween('date', [$fecha, $finMes])
                    ->sum('amount');

                $balanceMensual = $ingresos - $gastos;
                $balanceAcumulado += $balanceMensual;

                $balance[] = [
                    'mes' => $fecha->format('M Y'),
                    'mes_key' => $fecha->format('Y-m'),
                    'ingresos' => (float) $ingresos,
                    'gastos' => (float) $gastos,
                    'balance_mensual' => (float) $balanceMensual,
                    'balance_acumulado' => (float) $balanceAcumulado,
                ];
            }

            return response()->json([
                'balance_historico' => $balance,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener balance histórico.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dashboardCompleto(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $totalIncome = $user->transactions()->where('type', 'income')->sum('amount');
            $totalExpense = $user->transactions()->where('type', 'expense')->sum('amount');
            $balance = $totalIncome - $totalExpense;
            $totalTransactions = $user->transactions()->count();

            $recentTransactions = $user->transactions()
                ->with('category')
                ->orderBy('date', 'desc')
                ->take(5)
                ->get();

            $gastosPorCategoria = $user->transactions()
                ->where('type', 'expense')
                ->with('category')
                ->get()
                ->groupBy('category.name')
                ->map(function ($transactions) {
                    return [
                        'total' => $transactions->sum('amount'),
                        'count' => $transactions->count(),
                        'category' => $transactions->first()->category ? [
                            'id' => $transactions->first()->category->id,
                            'name' => $transactions->first()->category->name,
                            'icon' => $transactions->first()->category->icon,
                            'color' => $transactions->first()->category->color,
                        ] : null,
                    ];
                })
                ->sortByDesc('total')
                ->values();

            $meses = 6;
            $comparativa = [];
            for ($i = $meses - 1; $i >= 0; $i--) {
                $fecha = now()->subMonths($i)->startOfMonth();
                $finMes = now()->subMonths($i)->endOfMonth();

                $ingresos = $user->transactions()
                    ->where('type', 'income')
                    ->whereBetween('date', [$fecha, $finMes])
                    ->sum('amount');

                $gastos = $user->transactions()
                    ->where('type', 'expense')
                    ->whereBetween('date', [$fecha, $finMes])
                    ->sum('amount');

                $comparativa[] = [
                    'mes' => $fecha->format('M Y'),
                    'mes_key' => $fecha->format('Y-m'),
                    'ingresos' => (float) $ingresos,
                    'gastos' => (float) $gastos,
                    'balance' => (float) ($ingresos - $gastos),
                ];
            }

            return response()->json([
                'resumen' => [
                    'total_income' => (float) $totalIncome,
                    'total_expense' => (float) $totalExpense,
                    'balance' => (float) $balance,
                    'total_transactions' => $totalTransactions,
                ],
                'transacciones_recientes' => $recentTransactions,
                'gastos_por_categoria' => $gastosPorCategoria,
                'comparativa_mensual' => $comparativa,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener estadísticas del dashboard.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}