<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('update', $this->route('proveedor'));
    }

    public function rules(): array
    {
        $proveedor = $this->route('proveedor');

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:200',
                Rule::unique('proveedores', 'nombre')->ignore($proveedor),
            ],
            'nit' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('proveedores', 'nit')->ignore($proveedor),
            ],
            'telefono' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s()]*$/',
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('proveedores', 'email')->ignore($proveedor),
            ],
            'direccion' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'ciudad' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'pais' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'contacto' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],
            'telefono_contacto' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s()]*$/',
            ],
            'categoria' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'observaciones' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'estado' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['activo', 'inactivo']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder 200 caracteres.',
            'nombre.unique' => 'Ya existe un proveedor con este nombre.',
            'nit.max' => 'El NIT no puede exceder 20 caracteres.',
            'nit.unique' => 'Ya existe un proveedor con este NIT.',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres.',
            'telefono.regex' => 'El formato del teléfono no es válido.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.max' => 'El correo no puede exceder 255 caracteres.',
            'email.unique' => 'Ya existe un proveedor con este correo.',
            'direccion.max' => 'La dirección no puede exceder 500 caracteres.',
            'ciudad.max' => 'La ciudad no puede exceder 100 caracteres.',
            'pais.max' => 'El país no puede exceder 100 caracteres.',
            'contacto.max' => 'El contacto no puede exceder 150 caracteres.',
            'telefono_contacto.max' => 'El teléfono de contacto no puede exceder 20 caracteres.',
            'telefono_contacto.regex' => 'El formato del teléfono no es válido.',
            'categoria.max' => 'La categoría no puede exceder 100 caracteres.',
            'observaciones.max' => 'Las observaciones no pueden exceder 1000 caracteres.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'nit' => 'NIT',
            'telefono' => 'teléfono',
            'email' => 'correo electrónico',
            'direccion' => 'dirección',
            'ciudad' => 'ciudad',
            'pais' => 'país',
            'contacto' => 'contacto',
            'telefono_contacto' => 'teléfono de contacto',
            'categoria' => 'categoría',
            'observaciones' => 'observaciones',
            'estado' => 'estado',
        ];
    }

    public function getValidatedData(): array
    {
        return $this->validated();
    }
}
