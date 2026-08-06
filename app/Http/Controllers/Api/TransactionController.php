<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = $request->user()->transactions()->with('category');

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            $transactions = $query->orderBy('date', 'desc')->paginate(15);

            return response()->json($transactions, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar transacciones.', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'required|string|max:500',
                'date' => 'required|date',
                'type' => 'required|in:income,expense',
                'reference_image' => 'nullable|string',
            ]);

            $validated['reference_image'] = $this->sanitizeReferenceImage($request->input('reference_image'));

            $transaction = $request->user()->transactions()->create($validated);

            return response()->json([
                'message' => 'Transacción creada exitosamente.',
                'transaction' => $transaction->load('category'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear transacción.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        try {
            if ($transaction->user_id !== $request->user()->id) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }

            return response()->json(['transaction' => $transaction->load('category')], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener transacción.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        try {
            if ($transaction->user_id !== $request->user()->id) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }

            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'required|string|max:500',
                'date' => 'required|date',
                'type' => 'required|in:income,expense',
                'reference_image' => 'nullable|string',
            ]);

            $validated['reference_image'] = $this->sanitizeReferenceImage($request->input('reference_image'));

            $transaction->update($validated);

            return response()->json([
                'message' => 'Transacción actualizada exitosamente.',
                'transaction' => $transaction->fresh()->load('category'),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar transacción.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        try {
            if ($transaction->user_id !== $request->user()->id) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }

            $transaction->delete();

            return response()->json(['message' => 'Transacción eliminada exitosamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar transacción.', 'error' => $e->getMessage()], 500);
        }
    }

    private function sanitizeReferenceImage(?string $image): ?string
    {
        if ($image === null || trim($image) === '') {
            return null;
        }

        return str_starts_with($image, 'data:image/') ? $image : null;
    }
}
