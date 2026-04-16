<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => 'required|string|max:4000',
            'per_page' => 'sometimes|integer|min:1|max:200',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required|string|in:user,assistant',
            'history.*.content' => 'required|string|max:4000',
        ];
    }
}
