<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('update', $this->route('usuario'));
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'apellidos' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],
            'ci' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('usuarios', 'ci')->ignore($usuario),
            ],
            'direccion' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
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
                'required',
                'email',
                'max:255',
                Rule::unique('usuarios', 'email')->ignore($usuario),
            ],
            'password' => [
                'sometimes',
                'nullable',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols(),
            ],
            'rol_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:roles,id',
            ],
            'sucursal_id' => [
                'sometimes',
                'nullable',
                'uuid',
                'exists:sucursales,id',
            ],
            'sueldo' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'foto' => [
                'sometimes',
                'nullable',
                'string',
                'url',
                'max:500',
            ],
            'estado' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['activo', 'inactivo', 'suspendido']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.max' => 'Los apellidos no pueden exceder 150 caracteres.',
            'ci.required' => 'La cédula de identidad es obligatoria.',
            'ci.max' => 'La cédula no puede exceder 20 caracteres.',
            'ci.unique' => 'Ya existe un usuario con esta cédula.',
            'direccion.max' => 'La dirección no puede exceder 500 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres.',
            'telefono.regex' => 'El formato del teléfono no es válido.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.max' => 'El correo no puede exceder 255 caracteres.',
            'email.unique' => 'Ya existe un usuario con este correo.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'rol_id.required' => 'El rol es obligatorio.',
            'rol_id.uuid' => 'El ID del rol debe ser un UUID válido.',
            'rol_id.exists' => 'El rol seleccionado no existe.',
            'sucursal_id.uuid' => 'El ID de la sucursal debe ser un UUID válido.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
            'sueldo.numeric' => 'El sueldo debe ser un número.',
            'sueldo.min' => 'El sueldo no puede ser negativo.',
            'sueldo.max' => 'El sueldo no puede exceder 999,999.99.',
            'foto.url' => 'La foto debe ser una URL válida.',
            'estado.in' => 'El estado debe ser activo, inactivo o suspendido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'apellidos' => 'apellidos',
            'ci' => 'cédula de identidad',
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'password_confirmation' => 'confirmación de contraseña',
            'rol_id' => 'rol',
            'sucursal_id' => 'sucursal',
            'sueldo' => 'sueldo',
            'foto' => 'foto',
            'estado' => 'estado',
        ];
    }

    public function getValidatedData(): array
    {
        $data = $this->validated();

        if (isset($data['password']) && ! empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }
}
