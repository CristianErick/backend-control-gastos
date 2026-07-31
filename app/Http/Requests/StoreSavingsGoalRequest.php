<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavingsGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:1',
            'current_amount' => 'nullable|numeric|min:0',
            'deadline' => 'required|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título de la meta es obligatorio.',
            'target_amount.required' => 'El monto objetivo es obligatorio.',
            'target_amount.min' => 'El monto objetivo debe ser mayor a cero.',
            'deadline.required' => 'La fecha límite es obligatoria.',
            'deadline.after' => 'La fecha límite debe ser posterior a hoy.',
        ];
    }
}
