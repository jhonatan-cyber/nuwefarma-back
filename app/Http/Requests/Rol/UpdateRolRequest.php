<?php

namespace App\Http\Requests\Rol;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Administrador');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:100', Rule::unique('roles,nombre')->ignore($this->rol)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'permiso_id' => ['nullable', 'array'],
            'permiso_id.*' => ['string', 'max:100'],
            'estado' => ['nullable', 'string', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un rol con este nombre.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del rol',
            'descripcion' => 'descripción',
            'permiso_id' => 'permisos',
            'estado' => 'estado',
        ];
    }
}
