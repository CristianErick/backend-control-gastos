<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $categories = Category::where('is_global', true)
                ->orWhere('user_id', $request->user()->id)
                ->get();

            return response()->json(['categories' => $categories], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar categorías.', 'error' => $e->getMessage()], 500);
        }
    }
}
