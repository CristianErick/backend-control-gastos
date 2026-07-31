<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create(['currency' => 'PEN']);

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Usuario registrado exitosamente.',
                'user' => $user->load('profile'),
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar usuario.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'Credenciales inválidas.',
                ], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Inicio de sesión exitoso.',
                'user' => $user->load('profile'),
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al iniciar sesión.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function loginWithGoogle(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id_token' => 'required_without:google_id|string',
                'google_id' => 'required_without:id_token|string',
                'email' => 'required_without:id_token|string|email',
                'name' => 'required_without:id_token|string',
            ]);

            $googleId = $request->google_id;
            $email = $request->email;
            $name = $request->name;
            $avatar = $request->avatar;

            if ($request->filled('id_token')) {
                $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $request->id_token,
                ]);

                if ($response->failed()) {
                    return response()->json(['message' => 'Token de Google inválido.'], 401);
                }

                $payload = $response->json();
                $clientId = config('services.google.client_id');

                if ($clientId && ($payload['aud'] ?? '') !== $clientId) {
                    return response()->json(['message' => 'Token no corresponde a esta aplicación.'], 401);
                }

                $googleId = $payload['sub'];
                $email = $payload['email'];
                $name = $payload['name'] ?? explode('@', $email)[0];
                $avatar = $payload['picture'] ?? null;
            }

            $user = User::where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                return response()->json([
                    'requires_registration' => true,
                    'email' => $email,
                    'name' => $name,
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                ], 200);
            } else {
                $updates = [];
                if (!$user->google_id) {
                    $updates['google_id'] = $googleId;
                }
                if ($avatar && !$user->avatar) {
                    $updates['avatar'] = $avatar;
                }
                if (!empty($updates)) {
                    $user->update($updates);
                }
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Inicio de sesión con Google exitoso.',
                'user' => $user->load('profile'),
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al iniciar sesión con Google.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function registerWithGoogle(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'google_id' => 'required|string',
                'email' => 'required|string|email|max:255|unique:users',
                'name' => 'required|string|max:255',
                'password' => 'required|string|min:8|confirmed',
                'avatar' => 'nullable|string',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'google_id' => $validated['google_id'],
                'avatar' => $validated['avatar'] ?? null,
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create(['currency' => 'PEN']);

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Cuenta creada exitosamente.',
                'user' => $user->load('profile'),
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear cuenta con Google.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Sesión cerrada exitosamente.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cerrar sesión.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
