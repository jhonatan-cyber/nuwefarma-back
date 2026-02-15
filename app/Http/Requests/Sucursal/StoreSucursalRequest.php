<?php

namespace App\Http\Requests\Sucursal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Administrador');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'pais' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'gerente_id' => ['nullable', 'uuid', 'exists:usuarios,id'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estado' => ['nullable', 'string', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la sucursal es obligatorio.',
            'email.email' => 'El email debe ser válido.',
            'gerente_id.exists' => 'El gerente seleccionado no existe.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la sucursal',
            'direccion' => 'dirección',
            'ciudad' => 'ciudad',
            'pais' => 'país',
            'telefono' => 'teléfono',
            'email' => 'correo electrónico',
            'gerente_id' => 'gerente',
            'descripcion' => 'descripción',
            'estado' => 'estado',
        ];
    }
}
