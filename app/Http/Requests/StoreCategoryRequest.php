<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'type.required' => 'El tipo de categoría es obligatorio.',
            'type.in' => 'El tipo debe ser income o expense.',
        ];
    }
}
