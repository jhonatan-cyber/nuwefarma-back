<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PaginationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'sort' => 'sometimes|string',
            'order' => 'sometimes|string|in:asc,desc',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'El número de página debe ser un entero.',
            'page.min' => 'El número de página debe ser mayor a 0.',
            'per_page.integer' => 'Los elementos por página debe ser un entero.',
            'per_page.min' => 'Los elementos por página debe ser mayor a 0.',
            'per_page.max' => 'Los elementos por página no puede ser mayor a 100.',
            'sort.string' => 'El campo de ordenamiento debe ser una cadena de texto.',
            'order.in' => 'La dirección de ordenamiento debe ser asc o desc.',
        ];
    }

    /**
     * Get pagination parameters with defaults
     */
    public function getPaginationParams(): array
    {
        return [
            'page' => $this->get('page', 1),
            'per_page' => min($this->get('per_page', 15), 100),
            'sort' => $this->get('sort', 'created_at'),
            'order' => $this->get('order', 'desc'),
        ];
    }
}
