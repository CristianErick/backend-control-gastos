<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/google', [AuthController::class, 'loginWithGoogle']);
Route::post('/register/google', [AuthController::class, 'registerWithGoogle']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/user/avatar', [ProfileController::class, 'updateAvatar']);
    Route::put('/user/name', [ProfileController::class, 'updateName']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);

    Route::get('/dashboard/stats', [DashboardController::class, 'obtenerEstadisticas']);

    // Statistics routes
    Route::get('/statistics/gastos-por-categoria', [StatisticsController::class, 'gastosPorCategoria']);
    Route::get('/statistics/comparativa-ingresos-gastos', [StatisticsController::class, 'comparativaIngresosGastos']);
    Route::get('/statistics/balance-historico', [StatisticsController::class, 'balanceHistorico']);
    Route::get('/statistics/dashboard-completo', [StatisticsController::class, 'dashboardCompleto']);

    Route::apiResource('transactions', TransactionController::class);
    Route::apiResource('categories', CategoryController::class)->only(['index']);
    Route::apiResource('savings-goals', SavingsGoalController::class);

    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        Route::get('/categories', [AdminController::class, 'categories']);
        Route::post('/categories', [AdminController::class, 'storeCategory']);
        Route::put('/categories/{category}', [AdminController::class, 'updateCategory']);
        Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory']);
        Route::get('/transactions', [AdminController::class, 'transactions']);
    });
});
