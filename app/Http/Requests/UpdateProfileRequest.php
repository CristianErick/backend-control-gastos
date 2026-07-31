<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'nullable|string|max:20',
            'currency' => 'nullable|string|max:3',
            'monthly_budget_limit' => 'nullable|numeric|min:0',
            'avatar' => 'nullable|image|max:2048',
        ];
    }
}
