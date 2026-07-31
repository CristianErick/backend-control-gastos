<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'reference_image' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.min' => 'El monto debe ser mayor a cero.',
            'description.required' => 'La descripción es obligatoria.',
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'Ingrese una fecha válida.',
            'type.required' => 'El tipo de transacción es obligatorio.',
            'type.in' => 'El tipo debe ser income o expense.',
        ];
    }
}
