<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('profile');
            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener perfil.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'nullable|string|max:20',
                'currency' => 'nullable|string|max:3',
                'monthly_budget_limit' => 'nullable|numeric|min:0',
            ]);

            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json(['message' => 'Perfil no encontrado.'], 404);
            }

            $profile->update($validated);

            return response()->json([
                'message' => 'Perfil actualizado exitosamente.',
                'user' => $user->fresh()->load('profile'),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar perfil.', 'error' => $e->getMessage()], 500);
        }
    }
}
