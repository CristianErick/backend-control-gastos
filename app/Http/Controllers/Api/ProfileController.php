<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    public function updateName(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $user = $request->user();
            $user->name = $validated['name'];
            $user->save();

            return response()->json([
                'message' => 'Nombre actualizado exitosamente.',
                'user' => $user->fresh()->load('profile'),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el nombre.', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();

            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'La contraseña actual es incorrecta.'], 422);
            }

            $user->password = Hash::make($validated['password']);
            $user->save();

            return response()->json(['message' => 'Contraseña actualizada exitosamente.'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la contraseña.', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'avatar' => 'required|string',
            ]);

            $avatar = $validated['avatar'];

            if (!str_starts_with($avatar, 'data:image/')) {
                return response()->json(['message' => 'El avatar debe ser una imagen en base64.'], 422);
            }

            $user = $request->user();
            $user->avatar = $avatar;
            $user->save();

            return response()->json([
                'message' => 'Foto de perfil actualizada exitosamente.',
                'user' => $user->fresh()->load('profile'),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la foto de perfil.', 'error' => $e->getMessage()], 500);
        }
    }
}
