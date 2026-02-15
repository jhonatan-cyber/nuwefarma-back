<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('create', \App\Models\Cliente::class);
    }

    public function rules(): array
    {
        return [
            'ci' => [
                'required',
                'string',
                'max:20',
                'unique:clientes,ci',
            ],
            'nombre' => [
                'required',
                'string',
                'max:100',
            ],
            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s()]*$/',
            ],
            'estado' => [
                'nullable',
                'string',
                Rule::in(['activo', 'inactivo']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ci.required' => 'La cédula de identidad es obligatoria.',
            'ci.string' => 'La cédula debe ser una cadena de texto.',
            'ci.max' => 'La cédula no puede exceder 20 caracteres.',
            'ci.unique' => 'Ya existe un cliente con esta cédula.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'apellidos.required' => 'El apellido es obligatorio.',
            'apellidos.string' => 'El apellido debe ser una cadena de texto.',
            'apellidos.max' => 'El apellido no puede exceder 100 caracteres.',
            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres.',
            'telefono.regex' => 'El formato del teléfono no es válido.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ci' => 'cédula de identidad',
            'nombre' => 'nombre',
            'apellidos' => 'apellido',
            'telefono' => 'teléfono',
            'estado' => 'estado',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('estado')) {
            $this->merge(['estado' => 'activo']);
        }
    }

    public function getValidatedData(): array
    {
        return $this->validated();
    }
}
